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
use Ramsey\Uuid\Uuid;

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
    
    public function __construct(WestVaultConfig $config, SwordClient $client, Root $root, DepositFileMapper $mapper ) {
        $this->config = $config;
        $this->client = $client;
        $this->root = $root;
        $this->mapper = $mapper;
    }

    public function run() {
        $entities = $this->mapper->findNotDeposited();
        if(count($entities) === 0) {
            return;
        }
        foreach($entities as $entity) {
            print $entity->getPath() . "\n";
          try {
                $this->processFile($entity);
            } catch (ServerException $ex) {
                print $ex->getMessage() . "\n";
                print $ex->getResponse()->getBody() . "\n";
            } catch (Exception $ex) {
                print get_class($ex) . "\n" . $ex->getMessage();
            }
        }
    }
    
    protected function generateDepositXml(DepositFile $depositFile) {
        $atom = new DOMDocument('1.0', 'utf-8');
        $entry = $atom->createElementNS('http://www.w3.org/2005/Atom', 'entry');
        $atom->appendChild($entry);
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:pkp', $this->ns->getNamespace('pkp'));
        $entry->appendChild($atom->createElement('email', 'example@example.com'));
        $entry->appendChild($atom->createElement('title', 'Deposit title'));
        $entry->appendChild($atom->createElement('id', 'urn:uuid:' . $depositFile->getUuid()));
        $entry->appendChild($atom->createElement('updated', strftime("%FT%TZ")));
        try {
            // $this->storage->getById() doesn't work here. no idea why.
            $file = $this->storage->get($depositFile->getPath());
        } catch (Exception $e) {
            die($e->getMessage());
        }
        $content = $atom->createElementNS(
            $this->ns->getNamespace('pkp'), 'pkp:content', $this->urlGenerator->linkToRouteAbsolute('coppulpln.pln.fetch', array(
                'depositUuid' => $depositFile->getUuid()
            ))
        );
        $content->setAttribute('size', $file->getSize());
        $content->setAttribute('checksumType', $depositFile->getChecksumType());
        $content->setAttribute('checksumValue', $depositFile->getChecksumValue());
        $entry->appendChild($content);
        return $atom;
    }

}
