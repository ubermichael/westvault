<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use PHPUnit\Framework\TestCase;

class PageControllerTest extends TestCase {
    private $controller;

    private $userId = 'john';

    public function testIndex() : void {
        $result = $this->controller->index();

        $this->assertSame(['user' => 'john'], $result->getParams());
        $this->assertSame('main', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }

    public function testEcho() : void {
        $result = $this->controller->doEcho('hi');
        $this->assertSame(['echo' => 'hi'], $result->getData());
    }

    public function setUp() : void {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();

        $this->controller = new PageController(
            'westvault',
            $request,
            $this->userId
        );
    }
}
