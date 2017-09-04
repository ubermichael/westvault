<?php

/* 
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Controller;

use OCA\WestVault\Service\WestVaultConfig;
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
    
    /**
     * @var WestVaultConfig
     */
    private $config;
        
    public function __construct($appName, IRequest $request, IUser $user, IGroupManager $groupManager, WestVaultConfig $config) {
        parent::__construct($appName, $request);
        $this->user = $user;
        $this->groupManager = $groupManager;
        $this->config = $config;
    }
    
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        
        $uuids = [];
        foreach($this->groupManager->getUserGroups($this->user) as $group) {
            $uuids[$group->getGID()] = $this->config->getGroupValue('uuid', $group->getGID());
        }
        
        $params = [
            'user' => $this->user,
            'isAdmin' => $this->groupManager->isAdmin($this->user->getUID()),
            'groups' => $this->groupManager->getUserGroups($this->user),
            'subAdminGroups' => $this->groupManager->getSubAdmin()->getSubAdminsGroups($this->user),
                        
            'pln_site_ignore' => "*.log\n.*",
            'pln_site_checksum_type' => 'sha1',
            'pln_site_url' => 'http://localhost/westvault/api/sword/2.0/sd-iri',
            'pln_site_terms' => array(),
            
            'pln_uuids' => $uuids,
            
            'pln_user_preserved_folder' => 'lockss-preserved',
            'pln_user_restored_folder' => 'lockss-restored',
            'pln_user_agreed' => false,
        ];
        return new TemplateResponse($this->appName, 'config', $params);  // templates/config.php        
    }
    
}
