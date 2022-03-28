<?php

namespace Metabolism\WordpressBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * Class StyleguideCommand
 *
 * @package Customer\Composer\Plugin\Command
 */
class StyleguideCommand extends Command
{

    private $assets_dir;
    private $public_dir;
    private $twig;
    private $filesystem;

    public function __construct(ContainerInterface $container, Environment $twig)
    {
        parent::__construct();

        $this->twig = $twig;
        $this->assets_dir = $container->get('kernel')->getProjectDir().'/assets';
        $this->public_dir = $container->get('kernel')->getProjectDir().'/public';

        $this->filesystem = new Filesystem();
    }

    /**
     * Command declaration
     */
    protected function configure()
    {
        $this->setName( 'styleguide:generate' );
        $this->setDescription("Generate a styleguide according to Sass informations and builder configuration file.");
        $this->setHelp( <<<EOT
        
        
<comment>----------------- COMPOSER STYLEGUIDE ----------------- </comment>

Generate a styleguide according to Sass informations and builder configuration file. \n
You can access the generated page by accessing with your favorite browser to /_styleguide/ URI.
The generated style will be automatically place on your web root.

EOT
        );
    }


    /**
     * Command function
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = [];

        $data['scss'] = $this->parseSCSS('style.scss');

        $html = $this->twig->render('@Wordpress/styleguide.html.twig', $data);

        // Preview HTML in the terminal
        $this->filesystem->dumpFile($this->public_dir.'/styleguide.html', $html);

        $output->writeln('<info>styleguide.html generated</info>');

        return 1;
    }


    private function parseSCSS($entry)
    {
        $data = [];

        $variables = $this->find([$this->assets_dir.'/config/*.scss'], '/^\$(.*?)\s*?:\s*?(.*?)\s*?;/sm');

        foreach($variables as $name=>$value)
        {
            // Colors
            if( strpos($value,'#') !== false or strpos($value,'rgb') !== false )
                $data['colors'][$name] = $value;

            // Fonts
            if( $name == 'fonts' or $name == 'font-families' )
            {
                $value = explode(',', $value);
                foreach($value as $font)
                {
                    $font = explode(' ', trim(preg_replace('/\s+/', ' ',$font)));
                    if( strtolower($font[0]) != 'icons' )
                        $data['fonts'][$font[0]][] = ['variant'=>str_replace('Italic','',$font[1]), 'weight'=>$font[2], 'style'=>$font[3], 'stretch'=>$font[4]];
                }
            }

            // Icons
            if( $name == 'icons' )
            {
                $value = explode(',', $value);
                foreach($value as $icon)
                {
                    $icon = explode(' ', trim(preg_replace('/\s+/', ' ',$icon)));
                    $data['icons'][] = $icon[0];
                }
            }

            // Breakpoints
            if( strpos($name,'screen-') !== false and strpos($name,'-height') === false )
                $data['breakpoints'][str_replace('screen-', '', $name)] = $value;
        }

        $data['text'] = $this->find([$this->public_dir.'/theme/*.css'], '/.text--([a-zA-Z0-9-_]*)/');
        $data['button'] = $this->find([$this->public_dir.'/theme/*.css'], '/.button--([a-zA-Z0-9-_]*)/');

        return $data;
    }


    private function find($files_patterns, $pattern)
    {
        $raw_data = [[],[]];
        $data = [];

        foreach ($files_patterns as $files_pattern)
        {
            $files = glob($files_pattern);
            foreach ($files as $file)
            {
                if( file_exists($file) )
                {
                    $content = file_get_contents($file);
                    preg_match_all($pattern, $content, $_data);

                    if( count($_data) >= 3 )
                    {
                        $raw_data[0] = array_merge($raw_data[0],$_data[1]);
                        $raw_data[1] = array_merge($raw_data[1],$_data[2]);
                    }
                    elseif( count($_data) >= 2 )
                    {
                        $raw_data[0] = array_merge($raw_data[0],$_data[1]);
                    }
                }
                else
                {
                    $this->io->writeError('<warning>'.$file.' not found</warning>');
                }

                if( !empty($raw_data[1]) )
                {
                    $i = 0;
                    foreach ($raw_data[0] as $key)
                    {
                        $data[$key] = $raw_data[1][$i];
                        $i++;
                    }
                }
                else
                {
                    $data = array_values(array_unique($raw_data[0]));
                }
            }
        }

        return $data;
    }
}
