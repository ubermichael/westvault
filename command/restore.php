<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Command;

use OCA\WestVault\AppInfo\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Restore content from the staging server.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class Restore extends Command {

    public function __construct($name = null) {
        parent::__construct($name);
    }

    /**
     * Configure the command and set its options and arguments.
     */
    protected function configure() {
        parent::configure();
        $this->setName('westvault:restore');
        $this->setDescription('Restore queued deposits from LOCKSS');
    }

    /**
     * Execute the command. Calls the RestoreService to do the heavy lifting.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $app = new Application('westvault');
        $container = $app->getContainer();
        $restorer = $container->query('RestoreService');
        $restorer->run($output);
    }

}
