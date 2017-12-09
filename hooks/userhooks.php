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
     * Build an object with the user hooks.
     * 
     * @param IUserManager $manager
     * @param WestVaultConfig $config
     */
    public function __construct(IUserManager $manager, WestVaultConfig $config) {
        $this->manager = $manager;
        $this->config = $config;
    }

    /**
     * Register all the hooks for the plugin.
     */
    public function register() {
        $this->manager->listen('\OC\User', 'postCreateUser', [$this, 'userRegister']);
    }

    /**
     * Callback for the user register hook.
     * 
     * @param IUser $user
     */
    public function userRegister(IUser $user) {
        $this->config->setUserValue('pln_user_uuid', $user->getUID(), Uuid::uuid4()->toString());
    }

}
