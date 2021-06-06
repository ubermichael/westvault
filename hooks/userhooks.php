<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
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
     */
    public function __construct(IUserManager $manager, WestVaultConfig $config) {
        $this->manager = $manager;
        $this->config = $config;
    }

    /**
     * Register all the hooks for the plugin.
     */
    public function register() : void {
        $this->manager->listen('\OC\User', 'postCreateUser', [$this, 'userRegister']);
    }

    /**
     * Callback for the user register hook.
     */
    public function userRegister(IUser $user) : void {
        $this->config->setUserValue('pln_user_uuid', $user->getUID(), Uuid::uuid4()->toString());
    }
}
