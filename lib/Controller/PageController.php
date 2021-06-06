<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Controller;

use OC\Files\Node\Root;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Service\Navigation;
use OCA\WestVault\Service\WestVaultConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IUser;

/**
 * All the non-configuration pages and routes correspond to the
 * page controller.
 */
class PageController extends Controller {
    /**
     * @var IUser
     */
    private $user;

    /**
     * @var Navigation
     */
    private $navigation;

    /**
     * @var Root
     */
    private $root;

    /**
     * @var DepositFileMapper
     */
    private $mapper;

    /**
     * @var WestVaultConfig
     */
    private $config;

    /**
     * Build the controller.
     *
     * @param type $AppName
     */
    public function __construct($AppName, IRequest $request, IUser $user, Navigation $navigation, WestVaultConfig $config, DepositFileMapper $mapper, Root $root) {
        parent::__construct($AppName, $request);
        $this->user = $user;
        $this->navigation = $navigation;
        $this->config = $config;
        $this->mapper = $mapper;
        $this->root = $root;
    }

    /**
     * The index page shows a list of deposits and their status.
     *
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

    /**
     * The index page shows a list of deposits and their status.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function restore() {
        $uuid = $this->request->getParam('uuid', 0);
        $depositFile = $this->mapper->findByUuid($uuid);
        if ( ! $depositFile) {
            return new DataResponse([
                'status' => 'error',
                'message' => 'The requested file does not exist.',
            ]);
        }
        if ($this->user->getUID() !== $depositFile->getUserId()) {
            return new DataResponse([
                'status' => 'error',
                'message' => 'Only the owner of a deposit may initiate restore.',
            ]);
        }
        $depositFile->setPlnStatus('restore');
        $this->mapper->update($depositFile);

        return new DataResponse([
            'status' => 'success',
            'message' => 'The deposit has been added to the restore queue.',
        ]);
    }

    /**
     * Let the PLN download a deposit.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     *
     * @param mixed $uuid
     */
    public function fetch($uuid) {
        $depositFile = $this->mapper->findByUuid($uuid);
        $file = $this->root->get($depositFile->getPath());

        $response = new StreamResponse($this->config->getSystemValue('datadirectory') . $file->getPath());
        $response->addHeader('Content-Type', $file->getMimeType());
        $response->addHeader('Content-Length', $file->getSize());

        return $response;
    }
}
