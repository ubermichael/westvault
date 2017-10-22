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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of depositortask
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class Deposit extends Command {
    
    public function configure() {
        parent::configure();
        $this->setName('westvault:deposit');
        $this->setDescription('Send deposits to the staging server.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $app = new Application('westvault');
        $container = $app->getContainer();
        try {
            $container->query('DepositService')->run();
        } catch(\Exception $e) {
            $output->write($e->getMessage());
        }
    }

}
