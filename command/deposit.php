<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Command;

use Exception;
use OCA\WestVault\AppInfo\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Send content to the staging server.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class Deposit extends Command {
    /**
     * Construct the command.
     *
     * @param string $name
     */
    public function configure() : void {
        parent::configure();
        $this->setName('westvault:deposit');
        $this->setDescription('Send deposits to the staging server.');
    }

    /**
     * Execute the command. Calls the DepositService to do the heavy lifting.
     */
    public function execute(InputInterface $input, OutputInterface $output) : void {
        $app = new Application('westvault');
        $container = $app->getContainer();

        try {
            $container->query('DepositService')->run();
        } catch (Exception $e) {
            $output->write($e->getMessage());
        }
    }
}
