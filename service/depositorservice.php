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
use OCP\IGroupManager;

/**
 * Description of depositorservice
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DepositorService {

    /**
     * @var WestVaultConfig
     */
    private $config;

    /**
     * @var SwordClient
     */
    private $client;

    /**
     * @var Root
     */
    private $root;

    /**
     * @var DepositFileMapper
     */
    private $mapper;

    /**
     * @var IURLGenerator
     */
    private $generator;

    /**
     * @var IUserManager
     */
    private $manager;

    /**
     * @var IGroupManager
     */
    private $groupManager;

    public function __construct(WestVaultConfig $config, SwordClient $client, Root $root, DepositFileMapper $mapper, IURLGenerator $generator, IUserManager $manager, IGroupManager $groupManager) {
        $this->config = $config;
        $this->client = $client;
        $this->root = $root;
        $this->mapper = $mapper;
        $this->generator = $generator;
        $this->manager = $manager;
        $this->groupManager = $groupManager;
    }

    public function run() {
        $files = $this->mapper->findNotDeposited();
        if (count($files) === 0) {
            return;
        }
        foreach ($files as $depositFile) {
            $user = $this->manager->get($depositFile->getUserId());
            try {
                $atom = $this->generateDepositXml($user, $depositFile);
                $response = $this->client->createDeposit($user, $atom);
                $location = $response['location'];
                $responseXml = $response['xml'];
                $depositFile->setDateSent(time());
                $depositFile->setPlnStatus((string)$responseXml->xpath('//atom:category[@label="Processing State"]/@term')[0]);
                $depositFile->setPlnUrl($location);
                $this->mapper->update($depositFile);
            } catch (ServerException $ex) {
                print $ex->getMessage() . "\n";
                print $ex->getResponse()->getBody() . "\n";
            } catch (Exception $ex) {
                print get_class($ex) . "\n" . $ex->getMessage() . "\n" ;
            }
        }
    }

    /**
     * @param DepositFile $depositFile
     * @return DOMDocument
     */
    protected function generateDepositXml(IUser $user, DepositFile $depositFile) {
        $userGroups = $this->groupManager->getUserGroups($user);
        if(count($userGroups) !== 1) {
            // throw a wibbily here.
        }
        // get the first group for the user.
        $groupKey = array_keys($userGroups)[0];

        $ns = new Namespaces();
        $atom = new DOMDocument('1.0', 'utf-8');
        $entry = $atom->createElementNS('http://www.w3.org/2005/Atom', 'entry');
        $atom->appendChild($entry);
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:pkp', $ns->getNamespace('pkp'));
        $entry->appendChild($atom->createElement('email', $user->getEMailAddress()));
        $entry->appendChild($atom->createElement('title', $depositFile->filename()));
        $entry->appendChild($atom->createElement('id', 'urn:uuid:' . $depositFile->getUuid()));
        $entry->appendChild($atom->createElement('updated', strftime("%FT%TZ")));
        $meta = $atom->createElementNS($ns->getNamespace('lom'), 'meta');
        $meta->setAttribute('name', 'institution');
        $meta->setAttribute('content', $groupKey);
        $entry->appendChild($meta);
        try {
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
        print $atom->saveXML();
        return $atom;
    }

}
