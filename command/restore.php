<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OCA\WestVault\Command;

use OCA\WestVault\AppInfo\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of status
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class Restore extends Command {
    
    public function __construct($name = null) {
        parent::__construct($name);
    }

    protected function configure() {
        parent::configure();
        $this->setName('westvault:restore');
        $this->setDescription('Restore queued deposits from LOCKSS');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $app = new Application('westvault');
        $container = $app->getContainer();
        $restorer = $container->query('RestoreService');
        $restorer->run();
    }

}
