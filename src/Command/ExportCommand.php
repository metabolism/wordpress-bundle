<?php

namespace Metabolism\WordpressBundle\Command;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ExportCommand  extends Command{

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     * @throws Exception
     */
    public function execute (InputInterface $input, OutputInterface $output) {

        $output->writeln("<info>Alert email sent</info>");
    }


    /**
     *
     */
    protected function configure () {

        $this->setName('wordpress:export');
        $this->setDescription("Export static site");
    }
}