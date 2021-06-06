<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\AppInfo;

use OCA\WestVault\Controller\ConfigController;
use OCA\WestVault\Controller\PageController;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Hooks\FileHooks;
use OCA\WestVault\Hooks\UserHooks;
use OCA\WestVault\Service\DepositorService;
use OCA\WestVault\Service\Navigation;
use OCA\WestVault\Service\RestoreService;
use OCA\WestVault\Service\StatusService;
use OCA\WestVault\Service\SwordClient;
use OCA\WestVault\Service\WestVaultConfig;
use OCP\AppFramework\App;
use OCP\IContainer;

/**
 * Application is the central entry point for the plugin. It defines all the
 * container elements.
 */
class Application extends App {
    /**
     * Build the application and populate the container services.
     *
     * @param string $appName
     */
    public function __construct($appName = 'westvault', array $urlParams = []) {
        parent::__construct($appName, $urlParams);

        /**
         * IContainer.
         */
        $container = $this->getContainer();

        $container->registerService('Logger', fn ($c) => $c->query('ServerContainer')->getLogger());

        // User Session gets information about the logged in user. It must be
        // registered so that the User service can use it.
        $container->registerService('UserSession', fn ($c) => $c->query('ServerContainer')->getUserSession());

        // currently logged in user, userId can be gotten by calling this
        // service from the container.
        $container->registerService('User', fn ($c) => $c->query('UserSession')->getUser());

        // Group Manager makes sure files are only moved within a group.
        $container->registerService('GroupManager', fn (IContainer $c) => $c->query('ServerContainer')->getGroupManager());

        // Config manager for the plugin.
        $container->registerService('WestVaultConfig', function ($c) {
            return new WestVaultConfig(
                $c->query('ServerContainer')->getConfig(),
                $c->query('AppName')
            );
        });

        // Navigation manager.
        $container->registerService('WestVaultNavigation', fn ($c) => new Navigation($c->query('OCP\IURLGenerator')));

        // Page controller for non-config stuff.
        $container->registerService('PageController', function ($c) {
            return new PageController(
                $c->query('AppName'),
                $c->query('Request'),
                $c->query('User'),
                $c->query('WestVaultNavigation'),
                $c->query('WestVaultConfig'),
                $c->query('DepositFileMapper'),
                $c->query('ServerContainer')->getRootFolder()
            );
        });

        // Manage plugin configuration.
        $container->registerService('ConfigController', function ($c) {
            return new ConfigController(
                $c->query('AppName'),
                $c->query('Request'),
                $c->query('User'),
                $c->query('GroupManager'),
                $c->query('WestVaultConfig'),
                $c->query('WestVaultNavigation'),
                $c->query('SwordClient'),
                $c->query('ServerContainer')->getRootFolder()
            );
        });

        // Sword Client interacts with the staging server.
        $container->registerService('SwordClient', function ($c) {
            return new SwordClient(
                $c->query('WestVaultConfig'),
                $c->query('Logger')
            );
        });

        // Map deposit files from the database.
        $container->registerService('DepositFileMapper', fn ($c) => new DepositFileMapper($c->query('ServerContainer')->getDb()));

        // Hooks and events management for users.
        $container->registerService('UserHooks', function ($c) {
            return new UserHooks(
                $c->query('ServerContainer')->getUserManager(),
                $c->query('WestVaultConfig')
            );
        });

        // Hooks for file management.
        $container->registerService('FileHooks', function ($c) {
            return new FileHooks(
                $c->query('ServerContainer')->getUserManager(),
                $c->query('WestVaultConfig'),
                $c->query('ServerContainer')->getRootFolder(),
                $c->query('DepositFileMapper'),
                $c->query('Logger')
            );
        });

        // Depositor service sends things to the staging server.
        $container->registerService('DepositService', function ($c) {
            return new DepositorService(
                $c->query('WestVaultConfig'),
                $c->query('SwordClient'),
                $c->query('ServerContainer')->getRootFolder(),
                $c->query('DepositFileMapper'),
                $c->query('OCP\IURLGenerator'),
                $c->query('ServerContainer')->getUserManager(),
                $c->query('ServerContainer')->getGroupManager()
            );
        });

        // Restore service pulls things from the staging server.
        $container->registerService('RestoreService', function ($c) {
            return new RestoreService(
                $c->query('WestVaultConfig'),
                $c->query('SwordClient'),
                $c->query('ServerContainer')->getRootFolder(),
                $c->query('DepositFileMapper'),
                $c->query('OCP\IURLGenerator'),
                $c->query('ServerContainer')->getUserManager()
            );
        });

        // Status service figures out how the processing is going.
        $container->registerService('StatusService', function ($c) {
            return new StatusService(
                $c->query('WestVaultConfig'),
                $c->query('SwordClient'),
                $c->query('ServerContainer')->getRootFolder(),
                $c->query('DepositFileMapper'),
                $c->query('OCP\IURLGenerator'),
                $c->query('ServerContainer')->getUserManager()
            );
        });
    }
}
