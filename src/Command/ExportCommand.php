<?php

namespace Metabolism\WordpressBundle\Command;

use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ExportCommand  extends Command{

    private $base_url;
    private $export_dir;
    private $container;
    private $output;
    private $input;
    private $filesystem;
    private $errors;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        if( !isset($_SERVER['SERVER_NAME'] ) && (!isset($_SERVER['WP_INSTALLED']) || !$_SERVER['WP_INSTALLED']) )
            return;

        $this->container = $container;
        $this->errors = [];

        $this->export_dir = $this->container->get('kernel')->getCacheDir().'/export';
        $this->base_url  = get_home_url();
        $this->filesystem = new Filesystem();

        if( $this->filesystem->exists($this->export_dir) )
            $this->filesystem->remove($this->export_dir);

        $this->filesystem->mkdir($this->export_dir, 0755);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    public function execute (InputInterface $input, OutputInterface $output) {

        $this->output = $output;
        $this->input = $input;

        $time_start = microtime(true);

        $output->writeln("<comment>Loading sitemaps</comment>");
        $this->loadUrlsFromSitemap($this->base_url);

        $output->writeln("<comment>Copying files and folders</comment>");
        $this->copyFilesFolders();

        if( $input->getArgument('zip') && $input->getArgument('write') ){

            $output->writeln("<comment>Creating zip file</comment>");
            $this->createZip();
        }

        $time_end = microtime(true);
        $this->output->writeln("<question>==> Export completed in ".round($time_end-$time_start)."s with ".count($this->errors)." error(s)</question>");

        return 1;
    }


    /**
     * @param $base_url
     * @return void
     */
    public function loadUrlsFromSitemap($base_url) {

        $urls = $this->getSitemapUrls($base_url);

        $this->output->writeln("<comment>Loading urls</comment>");

        foreach ($urls as $url)
            $this->store($url);

        $this->output->writeln("<comment>Writing sitemap</comment>");

    }

    /**
     * @param $filename
     * @param $content
     * @return void
     */
    private function dumpFile($filename, $content){

        if( $this->input->getArgument('write') )
            $this->filesystem->dumpFile($filename, $content);
    }


    /**
     * @param $origin_path
     * @param $target_path
     * @return void
     */
    private function mirror($origin_path, $target_path){

        if( $this->input->getArgument('write') )
            $this->filesystem->mirror($origin_path, $target_path);
    }


    /**
     * @return array|\WP_Error
     */
    private function getSitemapUrls($base_url) {

        $robots = $this->getRobots($base_url);

        if( !$robots || !isset($robots['Sitemap']) )
            return new \WP_Error('export', 'Sitemap not found');

        $sitemaps = (array)$robots['Sitemap'];

        $urls = [];

        foreach ($sitemaps as $sitemap_url){

            $sitemap = $this->loadSitemap($sitemap_url);
            $urls = array_merge($urls, $this->parseSitemap($sitemap));
        }

        if( count($urls) )
            $this->output->writeln("<info>=> Found ".count($urls)." urls</info>");
        else
            $this->output->writeln("<error>=> No urls found</error>");

        return $urls;
    }


    /**
     * @param $url
     * @return string|void|\WP_Error
     */
    private function loadSitemap($url){

        $this->output->writeln("<info>- ".$url."</info>");

        $sitemap = $this->remoteGet($url);

        if( is_wp_error($sitemap) )
            return $sitemap;

        $sitemap_xml = simplexml_load_string($sitemap);

        if( !$sitemap_xml )
            return new \WP_Error('export', 'Error: Cannot create object');

        $filename = str_replace($this->base_url, '', $url);
        $this->dumpFile($this->export_dir.'/'.$filename, $this->process($sitemap));

        return json_decode(json_encode($sitemap_xml),1);
    }

    /**
     * @param $sitemap
     * @return array
     */
    private function parseSitemap($sitemap){

        $urls = [];

        if( isset($sitemap['sitemap']) ){

            foreach( $sitemap['sitemap'] as $_sitemap ){

                $_sitemap = $this->loadSitemap($_sitemap['loc']);
                $urls = array_merge($urls, $this->parseSitemap($_sitemap));
            }
        }
        elseif( isset($sitemap['url']) ){

            if( isset($sitemap['url']['loc']) ){

                $urls[] = $sitemap['url']['loc'];
            }
            else{

                foreach( $sitemap['url'] as $url )
                    $urls[] = $url['loc']??'';
            }
        }

        return $urls;
    }

    /**
     * @return array|false
     */
    private function getRobots($base_url) {

        $robots = $this->remoteGet($base_url.'/robots.txt');

        if( is_wp_error($robots) ){

            $this->output->writeln("<error>Can't get robots.txt ->".$robots->get_error_message()."</error>");
            return false;
        }

        $this->dumpFile($this->export_dir.'/robots.txt', $this->process($robots));

        $robots_lines = explode("\n", $robots);
        $robots = [];

        foreach ($robots_lines as $robots_line){

            $robots_line = explode(': ', $robots_line);

            if( count($robots_line) == 2 ){

                if( isset($robots[$robots_line[0]]) ){

                    if( is_string($robots[$robots_line[0]]) )
                        $robots[$robots_line[0]] = [$robots[$robots_line[0]]];

                    $robots[$robots_line[0]][] = trim($robots_line[1]);
                }
                else{

                    $robots[$robots_line[0]] = trim($robots_line[1]);
                }
            }
        }

        return $robots;
    }


    /**
     * @param $file
     * @param $unit
     * @return string
     */
    private function getFileSize($file, $unit="") {

        $size = filesize($file);

        if( (!$unit && $size >= 1<<30) || $unit == "GB")
            return number_format($size/(1<<30),2)."GB";
        if( (!$unit && $size >= 1<<20) || $unit == "MB")
            return number_format($size/(1<<20),2)."MB";
        if( (!$unit && $size >= 1<<10) || $unit == "KB")
            return number_format($size/(1<<10),2)."KB";
        return number_format($size)." bytes";
    }


    /**
     * @return void
     */
    private function createZip(){

        $filename = "export-".(new \DateTime())->getTimestamp().".zip";
        $file_path = $this->export_dir.'/'.$filename;

        $status = wp_backup($this->export_dir, $file_path);

        if( !is_wp_error($status) )
            $this->output->writeln("<info>Zip created (".$this->getFileSize($file_path).")</info>");
        else
            $this->output->writeln("<error>".$status->get_error_message()."</error>");
    }


    /**
     * @param $html
     * @return array|mixed|string|string[]
     */
    private function process($html){

        $ssl = substr($this->input->getArgument('domain'), 0, 5) == 'https';

        $domain = strtok(preg_replace('/https?:\/\//', '', $this->input->getArgument('domain')),':');
        $current_domain = strtok(preg_replace('/https?:\/\//', '', home_url('')),':');

        if( $ssl && !is_ssl() ) {

            $html = str_replace('http://'.$current_domain, 'https://'.$current_domain, $html);
            $html = str_replace(json_encode('http://'.$current_domain), json_encode('https://'.$current_domain), $html);
        }

        if( $domain ){

            $html = str_replace($current_domain, $domain, $html);
            $html = str_replace(json_encode($current_domain), json_encode($domain), $html);
        }

        return $html;
    }


    /**
     * @param $url
     * @return string|\WP_Error
     */
    private function remoteGet($url){

        $response = wp_remote_get($url, ['timeout'=>30]);
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if( $response_code == 200  && !empty($response_body) )
            return $response_body;

        $this->errors[] = $url;

        return new \WP_Error('remove_get', $response_code);
    }


    /**
     * @param $url
     * @return void
     */
    private function store($url){

        if( is_wp_error($url) )
            return;

        $url = str_replace($this->base_url, '', $url);

        $this->output->writeln("<comment>- $url</comment>");

        $time_start = microtime(true);
        $response = $this->remoteGet($this->base_url.$url);
        $time_end = microtime(true);

        if( !is_wp_error($response) ){

            if( substr($url, -1) == '/' )
                $url .= 'index';

            $filepath = $this->export_dir.$url.'.html';
            $path = dirname($filepath);

            if( !$this->filesystem->exists($path) )
                $this->filesystem->mkdir($path, 0755);

            $this->dumpFile($filepath, $this->process($response));

            $this->output->writeln("<info>-> rendered in ".round($time_end*1000-$time_start*1000)."ms</info>");
        }
        else{

            $this->output->writeln("<error>-> ".$response->get_error_message()."</error>");
        }
    }


    /**
     * @return void
     */
    private function copyFilesFolders(){

        global $_config;
        $export_options = $_config->get('export', []);


        $root_dir = $this->container->get('kernel')->getProjectDir();

        if( !isset($export_options['copy']) )
            return;

        foreach ($export_options['copy'] as $origin=>$target){

            $origin_path = $root_dir.$origin;
            $target_path = $this->export_dir.$target;

            if( $this->filesystem->exists($origin_path) ){

                $this->mirror($origin_path, $target_path);
                $this->output->writeln("<info>- ".$origin." -> ".$target."</info>");
            }
            else{

                $this->output->writeln("<error>".$origin_path." does not exists</error>");
            }
        }

        $export_dir = $this->container->get('kernel')->locateResource('@WordpressBundle/samples/public/export');

        $this->filesystem->copy($export_dir.'/.htaccess', $this->export_dir.'/.htaccess');
        $this->output->writeln("<info>- .htaccess</info>");
    }


    /**
     *
     */
    protected function configure () {

        $this->setName('site:export');
        $this->setDescription("Export static site");

        $this->addArgument('write', InputArgument::OPTIONAL, 'Write file to disk, set false to use as warmup');
        $this->addArgument('domain', InputArgument::OPTIONAL, 'Target domain');
        $this->addArgument('zip', InputArgument::OPTIONAL, 'Create zip from export folder');
        $this->addArgument('compare', InputArgument::OPTIONAL, 'Compare with other domain');
    }
}