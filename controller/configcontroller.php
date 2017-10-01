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
use OC\Files\Node\Root;
use OCA\WestVault\Service\DepositorService;
use OCA\WestVault\Service\Navigation;
use OCA\WestVault\Service\SwordClient;
use OCA\WestVault\Service\WestVaultConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\NotFoundException;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;

/**
 * The Config Controller handles all teh configuration related functionality
 * and refreshing the terms of use.
 */
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

    /**
     * @var Navigation
     */
    private $navigation;

    /**
     * @var SwordClient
     */
    private $client;
    
    /**
     * @var Root
     */
    private $root;
    
    /**
     * Build the controller.
     * 
     * @param string $appName
     * @param IRequest $request
     * @param IUser $user
     * @param IGroupManager $groupManager
     * @param WestVaultConfig $config
     * @param Navigation $navigation
     * @param SwordClient $client
     * @param Root $root
     */
    public function __construct($appName, IRequest $request, IUser $user, IGroupManager $groupManager, WestVaultConfig $config, Navigation $navigation, SwordClient $client, Root $root) {
        parent::__construct($appName, $request);
        $this->user = $user;
        $this->groupManager = $groupManager;
        $this->config = $config;
        $this->navigation = $navigation;
        $this->client = $client;
        $this->root = $root;
    }

    /**
     * Show the configuration forms.
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @return TemplateResponse
     */
    public function index() {
        $params = [
            'navigation' => $this->navigation->linkList(),
            'user' => $this->user,
            'isAdmin' => $this->groupManager->isAdmin($this->user->getUID()),
            
            'pln_accepting' => $this->client->isAccepting($this->user),
            'pln_message' => $this->client->getMessage($this->user),
            
            'pln_site_ignore' => $this->config->getAppValue('pln_site_ignore'),
            'pln_site_checksum_type' => $this->config->getAppValue('pln_site_checksum_type', 'sha1'),
            'pln_site_url' => $this->config->getSystemValue('pln_site_url'),
            'pln_user_terms' => unserialize($this->config->getUserValue('pln_terms_of_use', $this->user->getUID(), 'N;')),
            'pln_user_terms_checked' => $this->config->getUserValue('pln_terms_of_use_updated', $this->user->getUID(), ''),
            'pln_user_uuid' => $this->config->getUserValue('pln_user_uuid', $this->user->getUID()),
            'pln_user_email' => $this->config->getUserValue('pln_user_email', $this->user->getUID()),
            'pln_user_ignore' => $this->config->getUserValue('pln_user_ignore', $this->user->getUID()),
            'pln_user_preserved_folder' => $this->config->getUserValue('pln_user_preserved_folder', $this->user->getUID(), 'lockss-preserved'),
            'pln_user_restored_folder' => $this->config->getUserValue('pln_user_restored_folder', $this->user->getUID(), 'lockss-restored'),
            'pln_user_cleanup' => $this->config->getUserValue('pln_user_cleanup', $this->user->getUID(), true),
            'pln_user_agreed' => unserialize($this->config->getUserValue('pln_user_agreed', $this->user->getUID(), 'N;')),
        ];
        return new TemplateResponse($this->appName, 'config', $params);  // templates/config.php        
    }

    /**
     * Refresh the terms of use.
     * 
     * @NoAdminRequired
     * 
     * @return DataResponse
     */
    public function refresh() {
        if (!$this->client) {
            return new DataResponse(array(
                'status' => 'failure',
                'result' => 'Cannot create sword client.',
            ));
        }
        try {
            $newTerms = $this->client->getTermsOfUse($this->user);
            $updated = $this->client->getTermsUpdated($this->user);
            $result = 'The terms of service have not changed since the last time they were checked.';
            if($updated !== $this->config->getUserValue('pln_terms_of_use_updated', $this->user->getUID(), '')) {
                $result = 'The terms of service have been updated.';
                $this->config->setUserValue('pln_terms_of_use', $this->user->getUID(), serialize($newTerms));
                $this->config->setUserValue('pln_terms_of_use_updated', $this->user->getUID(), $updated);
            }
        } catch (Exception $ex) {
            return new DataResponse(array(
                'status' => 'failure',
                'result' => 'Error refreshing the terms of use: ' . $ex->getMessage() . ' at ' . $ex->getFile() . '#' . $ex->getLine(),
            ));
        }
        return new DataResponse(array(
            'status' => 'success',
            'result' => $result,
            'terms' => $newTerms,
            'updated' => $updated,
        ));
    }

    /**
     * Save site configuration settings.
     * 
     * @return DataResponse
     */
    public function saveSite() {
        try {
            $this->config->setAppValue('pln_site_ignore', $this->request->getParam('pln_site_ignore'));
            $this->config->setSystemValue('pln_site_checksum_type', $this->request->getParam('pln_site_checksum_type'));
            $this->config->setSystemValue('pln_site_url', $this->request->getParam('pln_site_url'));
        } catch (Exception $e) {
            return new DataResponse([
                'message' => "Error saving settings. Some settings may not have been saved. \n" . $e->getMessage(),
            ]);
        }
        return new DataResponse([
            'message' => 'The settings have been saved.'
        ]);
    }

    /**
     * Save the user terms of use agreement.
     * 
     * @NoAdminRequired
     * 
     * @return DataResponse
     */
    public function saveAgreement() {
        try {
            if ($this->request->getParam('pln_user_agreed') === 'agree') {
                $this->config->setUserValue('pln_user_agreed', $this->user->getUID(), serialize(new DateTime()));
            } else {
                $this->config->setUserValue('pln_user_agreed', $this->user->getUID(), serialize(null));
            }
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
	 * Check that a user folder exists and create it if necessary.
	 * 
	 * @param string $name
	 */
	protected function checkUserFolder($name) {
		$userFolder = $this->root->getUserFolder($this->user->getUID());
		try {
			$userFolder->get($name);
		} catch (NotFoundException $e) {
			$userFolder->newFolder($name);
		}
	}

    /**
     * Save the user settings and create the preservation and restoration 
     * folders if necessary.
     * 
     * @NoAdminRequired
     * 
     * @return DataResponse
     */
    public function saveUser() {
        try {
            $this->config->setUserValue('pln_user_email', $this->user->getUID(), $this->request->getParam('pln_user_email'));
            $this->config->setUserValue('pln_user_ignore', $this->user->getUID(), $this->request->getParam('pln_user_ignore'));
            $this->config->setUserValue('pln_user_preserved_folder', $this->user->getUID(), $this->request->getParam('pln_user_preserved_folder'));
            $this->config->setUserValue('pln_user_restored_folder', $this->user->getUID(), $this->request->getParam('pln_user_restored_folder'));
            $this->config->setUserValue('pln_user_cleanup', $this->user->getUID(), serialize('agreed' === $this->request->getParam('pln_user_cleanup')));
            $this->checkUserFolder($this->config->getUserValue('pln_user_preserved_folder', $this->user->getUID()));
            $this->checkUserFolder($this->config->getUserValue('pln_user_restored_folder', $this->user->getUID()));

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
