<?php

/* 
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;

class ConfigController extends Controller {
    
    /**
     * @var IUser
     */
    private $user;
    
    /** 
     * @var IGroupManager 
     */
    private $groupManager;
        
    public function __construct($appName, IRequest $request, IUser $user, IGroupManager $groupManager) {
        parent::__construct($appName, $request);
        $this->user = $user;
        $this->groupManager = $groupManager;
    }
    
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $params = [
            'user' => $this->user,
            'isAdmin' => $this->groupManager->isAdmin($this->user->getUID()),
            'groups' => $this->groupManager->getUserGroups($this->user),
        ];
        return new TemplateResponse($this->appName, 'config', $params);  // templates/config.php        
    }
    
}
