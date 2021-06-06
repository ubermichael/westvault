<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\AppInfo;

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\WestVault\Controller\PageController->index().
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
$application = new Application();
$application->registerRoutes($this, [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#restore', 'url' => '/restore', 'verb' => 'POST'],
        ['name' => 'page#fetch', 'url' => '/fetch/{uuid}', 'verb' => 'GET'],
        ['name' => 'page#fetchHead', 'url' => '/fetch/{uuid}', 'method' => 'HEAD'],
        ['name' => 'config#index', 'url' => '/config', 'verb' => 'GET'],
        ['name' => 'config#saveUser', 'url' => '/config/save-user', 'verb' => 'POST'],
        ['name' => 'config#saveSite', 'url' => '/config/save-site', 'verb' => 'POST'],
        ['name' => 'config#saveAgreement', 'url' => '/config/save-agreement', 'verb' => 'POST'],
        ['name' => 'config#refresh', 'url' => '/config/refresh', 'verb' => 'POST'],
    ],
]);
