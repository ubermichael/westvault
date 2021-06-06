<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
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
     * @param string $name
     */
    public function __construct($name = null) {
        parent::__construct($name);
    }

    /**
     * Configure the command and set its options and arguments.
     */
    protected function configure() : void {
        parent::configure();
        $this->setName('westvault:status');
        $this->setDescription('Check the status of deposits in West Vault');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Check all deposits');
    }

    /**
     * Execute the command. Calls the StatusService to do the heavy lifting.
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void {
        $app = new Application('westvault');
        $all = $input->getOption('all');
        $container = $app->getContainer();
        $checker = $container->query('StatusService');
        $checker->run($all, $output);
    }
}
