<?php

namespace Metabolism\WordpressBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
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
    private $filesystem;
    private $client;

    public function __construct(ContainerInterface $container, HttpClientInterface $client)
    {
        parent::__construct();

        $this->container = $container;
        $this->client = $client;

        $this->export_dir = $this->container->get('kernel')->getCacheDir().'/export';
        $this->base_url  = get_home_url();
        $this->filesystem = new Filesystem();

        if( !$this->filesystem->exists($this->export_dir) )
            $this->filesystem->mkdir($this->export_dir, 0755);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     * @throws Exception
     */
    public function execute (InputInterface $input, OutputInterface $output) {

        $this->output = $output;

        $output->writeln("<comment>Exporting posts</comment>");
        $this->exportPosts();

        $output->writeln("<comment>Exporting terms</comment>");
        $this->exportTerms();

        $output->writeln("<comment>Exporting theme</comment>");
        $this->exportTheme();

        return 1;
    }

    private function store($url){

        if( is_wp_error($url) )
            return;

        $this->output->writeln("<comment>- $url</comment>");

        $time_start = microtime(true);
        $response = wp_remote_get($this->base_url.$url.(isset($_SERVER['APP_PASSWORD'])?'?APP_PASSWORD='.$_SERVER['APP_PASSWORD']:''), ['timeout'=>30]);
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $time_end = microtime(true);

        if( $response_code == 200  && !empty($response_body) ){

            if( $url == '/' )
                $url = '/index';

            $filepath = $this->export_dir.$url.'.html';
            $path = dirname($filepath);

            if( !$this->filesystem->exists($path) )
                $this->filesystem->mkdir($path, 0755);

            $this->filesystem->dumpFile($filepath, $response_body);

            $this->output->writeln("<info>-> Ok in ".round($time_end*1000-$time_start*1000)."ms</info>");
        }
        else{

            $this->output->writeln("<error>-> ".$response_code."</error>");
        }
    }

    private function exportTheme(){

        $root_dir = $this->container->get('kernel')->getProjectDir();
        $public_dir = $root_dir.(is_dir($root_dir.'/public') ? '/public' : '/web');
        $theme_dir = $public_dir.'/theme';

        $this->filesystem->mirror($theme_dir, $this->export_dir.'/theme');
    }

    private function exportPosts(){

        global $wp_post_types;

        $home = get_option('page_on_front');

        $this->store('/');

        foreach ($wp_post_types as $post_type)
        {
            if( $post_type->public && ($post_type->publicly_queryable || $post_type->name == 'page') && !in_array($post_type->name, ['attachment']) ){

                $posts = get_posts(['post_type'=>$post_type->name, 'exclude'=>$home, 'posts_per_page'=>-1]);

                foreach ($posts as $post){

                    $url = get_permalink($post);
                    $this->store($url);
                }

                if( $post_type->has_archive ){

                    $url = get_post_type_archive_link($post_type->name);
                    $this->store($url);
                }
            }
        }
    }

    private function exportTerms(){

        global $wp_taxonomies;

        foreach ($wp_taxonomies as $taxonomy){

            //todo: better category handle
            if( $taxonomy->public && $taxonomy->publicly_queryable && !in_array($taxonomy->name, ['post_tag','post_format','category']) ){

                $terms = get_terms(['taxonomy'=>$taxonomy->name, 'number'=>0]);

                foreach ($terms as $term){

                    $url = get_term_link($term, $taxonomy->name);
                    $this->store($url);
                }
            }
        }
    }


    /**
     *
     */
    protected function configure () {

        $this->setName('wordpress:export');
        $this->setDescription("Export static site");
    }
}