<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\AppInfo;

require_once __DIR__ . '/autoload.php';

$app = new Application('westvault');
$container = $app->getContainer();
$container->query('UserHooks')->register();
$container->query('FileHooks')->register();

$container->query('OCP\INavigationManager')->add(function () use ($container) {
    $urlGenerator = $container->query('OCP\IURLGenerator');
    $l10n = $container->query('OCP\IL10N');

    return [
        // the string under which your app will be referenced in owncloud
        'id' => 'westvault',
        // sorting weight for the navigation. The higher the number, the higher
        // will it be listed in the navigation
        'order' => 10,
        // the route that will be shown on startup
        'href' => $urlGenerator->linkToRoute('westvault.page.index'),
        // the icon that will be shown in the navigation
        // this file needs to exist in img/
        'icon' => $urlGenerator->imagePath('westvault', 'app.svg'),
        // the title of your application. This will be used in the
        // navigation or on the settings page of your app
        'name' => $l10n->t('West Vault'),
    ];
});
