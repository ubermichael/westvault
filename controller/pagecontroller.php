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

use OCA\WestVault\Db\DepositFileMapper;
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

    public function __construct($AppName, IRequest $request, IUser $user, Navigation $navigation, DepositFileMapper $mapper) {        
        parent::__construct($AppName, $request);
        $this->user = $user;
        $this->navigation = $navigation;
        $this->mapper = $mapper;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $params = [
            'navigation' => $this->navigation->linkList(),
            'user' => $this->user,
            'deposits' => $this->mapper->findByUser($this->user),
        ];
        return new TemplateResponse('westvault', 'main', $params);  // templates/main.php
    }
}
