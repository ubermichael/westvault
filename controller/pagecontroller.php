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

namespace OCA\WestVault\Controller;

use OCA\WestVault\Service\Navigation;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IUser;

class PageController extends Controller {

    /**
     * @var IUser
     */
    private $user;
    
    /**
     * @var Navigation
     */
    private $navigation;

    public function __construct($AppName, IRequest $request, IUser $user, Navigation $navigation) {        
        parent::__construct($AppName, $request);
        $this->user = $user;
        $this->navigation = $navigation;
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $params = [
            'navigation' => $this->navigation->linkList(),
        ];
        return new TemplateResponse('westvault', 'main', $params);  // templates/main.php
    }
}
