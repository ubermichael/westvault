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
class RestoreService {

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
    
    public function run() {
        $files = $this->mapper->findRestoreQueue();
        foreach ($files as $depositFile) {            
            $user = $this->manager->get($depositFile->getUserId());
            $userFolder = $this->root->getUserFolder($depositFile->getUserId());
            $restoreFolderName = $this->config->getUserValue('pln_user_restored_folder', $user->getUID());
            $path = $restoreFolderName . '/' . $depositFile->filename();
            $file = $userFolder->newFile($path); 
            $handle = $file->fopen('w');
            
            $url = $this->client->restoreUrl($user, $depositFile->getPlnUrl());
            // @todo use a proper http client for this.
            $remote = fopen($url, 'r');
            
            // read the file 64kb at a time.
            while($data = fread($remote, 1024 * 64)) {
                fwrite($handle, $data);
            }
            fclose($remote);
            fclose($handle);
            $depositFile->setPlnStatus('restore-complete');
            $this->mapper->update($depositFile);
        }
    }
}
