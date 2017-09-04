<?php

/**
 * ownCloud - westvault
 *
 * This file is licensed under the MIT License version 3 or
 * later. See the COPYING file.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 * @copyright Michael Joyce 2017
 */
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\WestVault\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#do_echo', 'url' => '/echo', 'verb' => 'POST'],
        ['name' => 'config#index', 'url' => '/config', 'verb' => 'GET'],
        ['name' => 'config#saveUser', 'url' => '/config/save-user', 'verb' => 'POST'],
        ['name' => 'config#saveGroup', 'url' => '/config/save-group', 'verb' => 'POST'],
        ['name' => 'config#saveSite', 'url' => '/config/save-site', 'verb' => 'POST'],
        ['name' => 'config#refresh', 'url' => '/config/refresh', 'verb' => 'POST'],
    ]
];
