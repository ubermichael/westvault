<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Controller;

use DateTime;
use Exception;
use OCA\WestVault\Service\WestVaultConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
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
        foreach ($this->groupManager->getUserGroups($this->user) as $group) {
            $uuids[$group->getGID()] = $this->config->getGroupValue('pln_group_uuid', $group->getGID());
        }

        $params = [
            'user' => $this->user,
            'isAdmin' => $this->groupManager->isAdmin($this->user->getUID()),
            'groups' => $this->groupManager->getUserGroups($this->user),
            'subAdminGroups' => $this->groupManager->getSubAdmin()->getSubAdminsGroups($this->user),
            'pln_site_ignore' => $this->config->getAppValue('pln_site_ignore'),
            'pln_site_checksum_type' => $this->config->getAppValue('pln_site_checksum_type', 'sha1'),
            'pln_site_url' => $this->config->getAppValue('pln_site_url'),
            'pln_site_terms' => unserialize($this->config->getAppValue('terms_of_use', 'N;')),
            'pln_site_terms_checked' => unserialize($this->config->getAppValue('terms_of_use_updated', 'N;')),
            'pln_uuids' => $uuids,
            'pln_user_email' => $this->config->getUserValue('pln_user_email', $this->user->getUID()),
            'pln_user_ignore' => $this->config->getUserValue('pln_user_ignore', $this->user->getUID()),
            'pln_user_preserved_folder' => $this->config->getUserValue('pln_user_preserved_folder', $this->user->getUID(), 'lockss-preserved'),
            'pln_user_restored_folder' => $this->config->getUserValue('pln_user_restored_folder', $this->user->getUID(), 'lockss-restored'),
            'pln_user_cleanup' => $this->config->getUserValue('pln_user_cleanup', $this->user->getUID(), true),
            'pln_user_agreed' => unserialize($this->config->getUserValue('pln_user_agreed', $this->user->getUID(), 'N;')),
        ];
        return new TemplateResponse($this->appName, 'config', $params);  // templates/config.php        
    }

    public function refresh() {
//            'pln_site_terms' => unserialize($this->config->getAppValue('terms_of_use', 'N;')),
//            'pln_site_terms_checked' => unserialize($this->config->getAppValue('terms_of_use_updated', 'N;')),
    }

    public function saveSite() {
        try {
            $this->config->setAppValue('pln_site_ignore', $this->request->getParam('pln_site_ignore'));
            $this->config->setSystemValue('pln_site_checksum_type', $this->request->getParam('pln_site_checksum_type'));
            $this->config->setSystemValue('pln_site_url', $this->request->getParam('pln_site_url'));
            return new DataResponse([
                'message' => 'The settings have been saved.'
            ]);
        } catch (Exception $e) {
            return new DataResponse([
                'message' => "Error saving settings. Some settings may not have been saved. \n" . $e->getMessage(),
            ]);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function saveGroup() {
        try {
            $this->config->setGroupValue('pln_group_uuid', $this->request->getParam('group_gid'), $this->request->getParam('pln_group_uuid'));
            return new DataResponse([
                'message' => 'The settings have been saved.'
            ]);
        } catch (Exception $e) {
            return new DataResponse([
                'message' => "Error saving settings. Some settings may not have been saved. \n" . $e->getMessage(),
            ]);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function saveUser() {
        try {
            $this->config->setUserValue('pln_user_email', $this->user->getUID(), $this->request->getParam('pln_user_email'));
            $this->config->setUserValue('pln_user_ignore', $this->user->getUID(), $this->request->getParam('pln_user_ignore'));
            $this->config->setUserValue('pln_user_preserved_folder', $this->user->getUID(), $this->request->getParam('pln_user_preserved_folder'));
            $this->config->setUserValue('pln_user_restored_folder', $this->user->getUID(), $this->request->getParam('pln_user_restored_folder'));
            if ($this->request->getParam('pln_user_agreed') === 'agree') {
                $this->config->setUserValue('pln_user_agreed', $this->user->getUID(), serialize(new DateTime()));
            } else {
                $this->config->setUserValue('pln_user_agreed', $this->user->getUID(), serialize(null));
            }
            $this->config->setUserValue('pln_user_cleanup', $this->user->getUID(), serialize('agreed' === $this->request->getParam('pln_user_cleanup')));
            return new DataResponse([
                'message' => 'The settings have been saved.'
            ]);
        } catch (Exception $e) {
            return new DataResponse([
                'message' => "Error saving settings. Some settings may not have been saved. \n" . $e->getMessage(),
            ]);
        }
    }

}
