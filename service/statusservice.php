<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Service;

use DOMDocument;
use Exception;
use GuzzleHttp\Exception\ServerException;
use OC\Files\Node\Root;
use OCA\WestVault\Db\DepositFile;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Service\SwordClient;
use OCA\WestVault\Service\WestVaultConfig;
use OCA\WestVault\Util\Namespaces;
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

    public function run() {
        $files = $this->mapper->findNotChecked();
        if (count($files) === 0) {
            return;
        }
        foreach ($files as $file) {
            $user = $this->manager->get($file->getUserId());
            $states = $this->client->statement($user, $file->getPlnUrl());
            $file->setPlnStatus($states['pln']);
            $file->setLockssStatus($states['lockss']);
            $file->setDateChecked(time());
            $this->mapper->update($file);
        }
    }
}
