<?php

namespace Metabolism\WordpressBundle\Command;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SetupCommand extends Command
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if( !isset($_SERVER['SERVER_NAME'] ) && (!isset($_SERVER['WP_INSTALLED']) || !$_SERVER['WP_INSTALLED']) ){
            $output->writeln('Please finish first your Wordpress installation or set the WP_INSTALLED variable to 1.');
            return Command::FAILURE;
        }

        $this->setupTheme('symfonywordpresstheme', 'symfonywordpresstheme', $output);
        return Command::SUCCESS;
    }

    protected function configure()
    {
        $this->setName('site:setup');
        $this->setDescription("Setup basics of Wordpress");
    }

    protected function setupTheme(string $template, string $stylesheet, OutputInterface $output): void
    {
        $output->writeln('Setting up Wordpress Theme');
        update_option('template', $template);
        update_option('stylesheet', $stylesheet);
    }
}