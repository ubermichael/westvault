<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Hooks;

use OCA\WestVault\Service\WestVaultConfig;
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

    public function __construct(IUserManager $manager, WestVaultConfig $config) {
        $this->manager = $manager;
        $this->config = $config;
    }
    
    public function register() {
        $callback = function(IUser $user) {
            $this->config->setUserValue('uuid', $user->getUID(), Uuid::uuid4()->toString());
        };
        $this->manager->listen('\OC\User', 'postCreateUser', $callback);
    }

}
