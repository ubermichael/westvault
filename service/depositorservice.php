<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Service;

use DOMDocument;
use Exception;
use GuzzleHttp\Exception\ServerException;
use OC\Files\Node\Root;
use OCA\WestVault\Db\DepositFile;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Service\SwordClient;
use OCA\WestVault\Service\WestVaultConfig;
use OCA\WestVault\Util\Namespaces;
use OCP\IURLGenerator;
use OCP\IUserManager;

/**
 * Description of depositorservice
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DepositorService {

    private $config;
    private $client;
    private $root;
    private $mapper;
    private $generator;
    private $manager;

    public function __construct(WestVaultConfig $config, SwordClient $client, Root $root, DepositFileMapper $mapper, IURLGenerator $generator, IUserManager $manager) {
        $this->config = $config;
        $this->client = $client;
        $this->root = $root;
        $this->mapper = $mapper;
        $this->generator = $generator;
        $this->manager = $manager;
    }

    public function run() {
        $files = $this->mapper->findNotDeposited();
        if (count($files) === 0) {
            return;
        }
        foreach ($files as $file) {
            $user = $this->manager->get($file->getUserId());
            try {
                $atom = $this->generateDepositXml($user, $file);                
                $responseXml = $this->client->createDeposit($user, $atom);                
                print $responseXml->asXML();
            } catch (ServerException $ex) {
                print $ex->getMessage() . "\n";
                print $ex->getResponse()->getBody() . "\n";
            } catch (Exception $ex) {
                print get_class($ex) . "\n" . $ex->getMessage();
            }
        }
    }

    /**
     * @param DepositFile $depositFile
     * @return DOMDocument
     */
    protected function generateDepositXml(IUser $user, DepositFile $depositFile) {
        $ns = new Namespaces();
        $atom = new DOMDocument('1.0', 'utf-8');
        $entry = $atom->createElementNS('http://www.w3.org/2005/Atom', 'entry');
        $atom->appendChild($entry);
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:pkp', $ns->getNamespace('pkp'));
        $entry->appendChild($atom->createElement('email', $user->getEMailAddress()));
        $entry->appendChild($atom->createElement('title', $depositFile->filename()));
        $entry->appendChild($atom->createElement('id', 'urn:uuid:' . $depositFile->getUuid()));
        $entry->appendChild($atom->createElement('updated', strftime("%FT%TZ")));
        try {
            // $this->storage->getById() doesn't work here. no idea why.
            $file = $this->root->get($depositFile->getPath());
        } catch (Exception $e) {
            die($e->getMessage());
        }
        $content = $atom->createElementNS(
                $ns->getNamespace('pkp'), 'pkp:content', $this->generator->linkToRouteAbsolute('westvault.page.fetch', array(
                    'uuid' => $depositFile->getUuid()
                ))
        );
        $content->setAttribute('size', $file->getSize());
        $content->setAttribute('checksumType', $depositFile->getChecksumType());
        $content->setAttribute('checksumValue', $depositFile->getChecksumValue());
        $entry->appendChild($content);
        return $atom;
    }

}
