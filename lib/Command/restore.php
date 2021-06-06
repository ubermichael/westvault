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
    protected function configure() : void {
        parent::configure();
        $this->setName('westvault:restore');
        $this->setDescription('Restore queued deposits from LOCKSS');
    }

    /**
     * Execute the command. Calls the RestoreService to do the heavy lifting.
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void {
        $app = new Application('westvault');
        $container = $app->getContainer();
        $restorer = $container->query('RestoreService');
        $restorer->run($output);
    }
}
