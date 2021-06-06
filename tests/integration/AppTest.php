<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

use OCP\AppFramework\App;
use Test\TestCase;

/**
 * This test shows how to make a small Integration Test. Query your class
 * directly from the container, only pass in mocks if needed and run your tests
 * against the database.
 */
class AppTest extends TestCase {
    private $container;

    public function testAppInstalled() : void {
        $appManager = $this->container->query('OCP\App\IAppManager');
        $this->assertTrue($appManager->isInstalled('westvault'));
    }

    public function setUp() : void {
        parent::setUp();
        $app = new App('westvault');
        $this->container = $app->getContainer();
    }
}
