<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Hooks;

use OC\Files\Node\Root;
use OC\Files\Node\File;
use OCA\WestVault\Db\DepositFile;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Service\WestVaultConfig;
use OCP\Files\FileInfo;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IUserManager;
use Ramsey\Uuid\Uuid;

/**
 * Description of userhooks
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

    public function __construct(IUserManager $manager, WestVaultConfig $config, Root $root, DepositFileMapper $mapper) {
        $this->manager = $manager;
        $this->config = $config;
        $this->root = $root;
        $this->mapper = $mapper;
    }

    public function register() {
        $this->manager->listen('\OC\User', 'postCreateUser', [$this, 'userRegister']);
        $this->root->listen('\OC\Files', 'postCreate', [$this, 'postCreate']);
        $this->root->listen('\OC\Files', 'postDelete', [$this, 'postDelete']);
    }

    public function userRegister(IUser $user) {
        $this->config->setUserValue('pln_user_uuid', $user->getUID(), Uuid::uuid4()->toString());
    }

    public function postCreate(Node $file) {
        if ($file->getType() !== FileInfo::TYPE_FILE) {
            return;
        }
        
        $uid = $file->getOwner()->getUID();
        $watchFolder = $this->config->getUserValue('pln_user_preserved_folder', $uid);        
        $userPath = $this->root->getUserFolder($uid)->getPath();
        $localPath = substr($file->getPath(), strlen($userPath)+1);
        if(strncmp($localPath, $watchFolder, strlen($watchFolder) !== 0)) {
            return;
        }

        $depositFile = new DepositFile();
        $depositFile->setFileId($file->getId());
        $depositFile->setUserId($uid);
        $depositFile->setUuid(Uuid::uuid4());
        $depositFile->setPath($file->getPath());
        $depositFile->setChecksumType('sha1');
        $depositFile->setChecksumValue(hash('sha1', $file->getContent()));
        $depositFile->setDateSent(null);
        $depositFile->setDateChecked(null);
        $this->mapper->insert($depositFile);
    }
    
    public function postDelete(File $file) {
        if ($file->getType() !== FileInfo::TYPE_FILE) {
            return;
        }
        $depositFile = $this->mapper->findByFileId($file->getId());
        if( ! $depositFile) {
            return;
        }
        $this->mapper->delete($depositFile);
    }

}
