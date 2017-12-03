<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Service;

use OC\Files\Node\Root;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Service\SwordClient;
use OCA\WestVault\Service\WestVaultConfig;
use OCP\IURLGenerator;
use OCP\IUserManager;

/**
 * Description of depositorservice
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class StatusService {

    /**
     * @var WestVaultConfig
     */
    private $config;
    
    /**
     * @var SwordClient
     */
    private $client;
    
    /**
     * @var Root
     */
    private $root;
    
    /**
     * @var DepositFileMapper
     */
    private $mapper;
    
    /**
     * @var IURLGenerator
     */
    private $generator;
    
    /**
     * @var IUserManager
     */
    private $manager;

    public function __construct(WestVaultConfig $config, SwordClient $client, Root $root, DepositFileMapper $mapper, IURLGenerator $generator, IUserManager $manager) {
        $this->config = $config;
        $this->client = $client;
        $this->root = $root;
        $this->mapper = $mapper;
        $this->generator = $generator;
        $this->manager = $manager;
    }

    public function run($all = false) {
        $deleted = false;
        $files = $this->mapper->findNotChecked($all);
        if (count($files) === 0) {
            return;
        }
        foreach ($files as $depositFile) {
            $user = $this->manager->get($depositFile->getUserId());
            $states = $this->client->statement($user, $depositFile->getPlnUrl());
            if(($states['lockss'] === 'agreement') && ($this->config->getUserValue('pln_user_cleanup', $user->getUID(), 'b:0') === 'cleanup')) {
                try {
                    $file = $this->root->get($depositFile->getPath());
                    $file->delete();
                    $deleted = true;
                } catch (\Exception $e) {
                    print $e->getMessage() . "\n";
                }
            }
            $depositFile->setDateChecked(time());
            $this->mapper->update($depositFile);
        }
        if($deleted) {
            print "Remember to run files:scan --all next.\n";
        }
    }
}
