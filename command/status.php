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
 * Check the status of the deposits in the staging server.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class Status extends Command {

    /**
     * Construct the command.
     * 
     * @param String $name
     */
    public function __construct($name = null) {
        parent::__construct($name);
    }

    /**
     * Configure the command and set its options and arguments.
     */
    protected function configure() {
        parent::configure();
        $this->setName('westvault:status');
        $this->setDescription('Check the status of deposits in West Vault');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Check all deposits');
    }

    /**
     * Execute the command. Calls the StatusService to do the heavy lifting.
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $app = new Application('westvault');
        $all = $input->getOption('all');
        $container = $app->getContainer();
        $checker = $container->query('StatusService');
        $checker->run($all, $output);
    }

}
