<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Hooks;

use OC\Files\Node\File;
use OC\Files\Node\Root;
use OCA\WestVault\Db\DepositFile;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Service\WestVaultConfig;
use OCP\Files\FileInfo;
use OCP\Files\Node;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use Ramsey\Uuid\Uuid;

/**
 * Collection of hooks for the plugin.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class UserHooks {

    /**
     * @var IUserManager
     */
    private $manager;

    /**
     * @var WestVaultConfig
     */
    private $config;

    /**
     * @var Root
     */
    private $root;

    /**
     * @var DepositFileMapper
     */
    private $mapper;

    /**
     * @var ILogger
     */
    private $logger;

    /**
     * Build an object with the user hooks.
     * 
     * @param IUserManager $manager
     * @param WestVaultConfig $config
     * @param Root $root
     * @param DepositFileMapper $mapper
     */
    public function __construct(IUserManager $manager, WestVaultConfig $config, Root $root, DepositFileMapper $mapper, ILogger $logger) {
        $this->manager = $manager;
        $this->config = $config;
        $this->root = $root;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    /**
     * Register all the hooks for the plugin.
     */
    public function register() {
        $this->manager->listen('\OC\User', 'postCreateUser', [$this, 'userRegister']);
        $this->root->listen('\OC\Files', 'postCreate', [$this, 'postCreate']);
        $this->root->listen('\OC\Files', 'postDelete', [$this, 'postDelete']);
    }

    /**
     * Callback for the user register hook.
     * 
     * @param IUser $user
     */
    public function userRegister(IUser $user) {
        $this->config->setUserValue('pln_user_uuid', $user->getUID(), Uuid::uuid4()->toString());
    }

    /**
     * Callback for the post file create hook.
     * 
     * @param Node $file
     * @return null
     */
    public function postCreate(Node $file) {
        $this->logger->warning("pc: {$file->getId()}:{$file->getPath()}");
        if ($file->getType() !== FileInfo::TYPE_FILE) {
            return;
        }
        $uid = $file->getOwner()->getUID();
        if (!$this->config->getUserValue('pln_user_agreed', $uid, null)) {
            return;
        }
        if (!$this->config->getUserValue('pln_user_preserved_folder', $uid, null)) {
            return;
        }
        foreach ($this->config->getIgnoredPatterns($uid) as $pattern) {
            if (preg_match("/^{$pattern}$/", $file->getName())) {
                return;
            }
        }
        $watchFolder = $this->config->getUserValue('pln_user_preserved_folder', $uid);
        $userPath = $this->root->getUserFolder($uid)->getPath();
        $localPath = substr($file->getPath(), strlen($userPath) + 1);

        if(substr($localPath, 0, strlen($watchFolder)) !== $watchFolder) {
            return;
        }        
        $checksumType = $this->config->getAppValue('pln_site_checksum_type', 'sha1');
        $depositFile = new DepositFile();
        $depositFile->setFileId($file->getId());
        $depositFile->setUserId($uid);
        $depositFile->setUuid(Uuid::uuid4());
        $depositFile->setPath($file->getPath());
        $depositFile->setChecksumType($checksumType);
        $depositFile->setChecksumValue(hash($checksumType, $file->getContent()));
        $depositFile->setDateSent(null);
        $depositFile->setDateChecked(null);
        $this->mapper->insert($depositFile);
    }

    /**
     * Callback for the post file delete hook.
     * 
     * @param Node $file
     * @return null
     */
    public function postDelete(Node $file) {
        $depositFile = $this->mapper->findByPath($file->getPath());
        if( ! $depositFile || $depositFile->sent()) {
            return;
        }
        $this->mapper->delete($depositFile);
    }

}
