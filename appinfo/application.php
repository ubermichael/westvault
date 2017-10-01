<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 * Copyright 2017 michael.
 */

namespace OCA\WestVault\AppInfo;

use OCA\WestVault\Controller\ConfigController;
use OCA\WestVault\Controller\PageController;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Hooks\UserHooks;
use OCA\WestVault\Service\DepositorService;
use OCA\WestVault\Service\Navigation;
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
     * @param array $urlParams
     */
    public function __construct($appName = 'westvault', array $urlParams = array()) {
        parent::__construct($appName, $urlParams);

        /**
         * IContainer
         */
        $container = $this->getContainer();

        $container->registerService('Logger', function($c) {
            return $c->query('ServerContainer')->getLogger();
        });

        // User Session gets information about the logged in user. It must be
        // registered so that the User service can use it.
        $container->registerService('UserSession', function($c) {
            return $c->query('ServerContainer')->getUserSession();
        });

        // currently logged in user, userId can be gotten by calling this
        // service from the container.
        $container->registerService('User', function($c) {
            return $c->query('UserSession')->getUser();
        });

        // Group Manager makes sure files are only moved within a group.
        $container->registerService('GroupManager', function(IContainer $c) {
            return $c->query('ServerContainer')->getGroupManager();
        });

        // Config manager for the plugin.
        $container->registerService('WestVaultConfig', function($c) {
            return new WestVaultConfig(
                    $c->query('ServerContainer')->getConfig(),
                    $c->query('AppName')
            );
        });
        
        // Navigation manager.
        $container->registerService('WestVaultNavigation', function($c){
            return new Navigation($c->query('OCP\IURLGenerator'));
        });

        // Page controller for non-config stuff.
        $container->registerService('PageController', function($c) {
            return new PageController(
                    $c->query('AppName'), 
                    $c->query('Request'), 
                    $c->query('User'), 
                    $c->query('WestVaultNavigation'),
                    $c->query('DepositFileMapper')
            );
        });
        
        // Manage plugin configuration.
        $container->registerService('ConfigController', function($c) {
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
        $container->registerService('SwordClient', function($c){
            return new SwordClient(
                $c->query('WestVaultConfig'),
                $c->query('OCP\IURLGenerator')
            );
        });
        
        // Map deposit files from the database.
        $container->registerService('DepositFileMapper', function($c){
            return new DepositFileMapper($c->query('ServerContainer')->getDb());
        });
        
        // Hooks and events management. 
        // @todo UserHooks should be called HooksManager or something.
        $container->registerService('UserHooks', function($c) {
            return new UserHooks(
                    $c->query('ServerContainer')->getUserManager(),
                    $c->query('WestVaultConfig'),        
                    $c->query('ServerContainer')->getRootFolder(),
                    $c->query('DepositFileMapper'),
                    $c->query('Logger')
            );
        });
        
        $container->registerService('DepositorService', function($c){
            return new DepositorService(
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
