<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Service;

use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;
use OCA\WestVault\Util\Namespaces;
use OCP\IURLGenerator;
use OCP\IUser;
use SimpleXMLElement;

/**
 * Description of swordclient
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class SwordClient {

    /**
     * @var WestVaultConfig
     */
    private $config;

    /**
     *
     * @var Client
     */
    private $httpClient;

    /**
     * @var Namespaces
     */
    private $ns;

    /**
     *
     * @var IUrlGenerator
     */
    private $urlGenerator;

    /**
     * @param PlnConfig $config
     */
    public function __construct(WestVaultConfig $config, IURLGenerator $urlGenrator) {
        $this->config = $config;
        $this->ns = new Namespaces();
        $this->urlGenerator = $urlGenrator;
    }

    public function setClient(Client $client) {
        $this->httpClient = $client;
    }

    /**
     * @return Client
     */
    public function getClient() {
        if (!$this->httpClient) {
            $this->httpClient = new Client();
        }
        return $this->httpClient;
    }

    protected function fetchServiceDocument(IUser $user) {
        $sdIri = $this->config->getSystemValue('pln_site_url');
        if (!$sdIri) {
            return;
        }
        $uuid = $this->config->getUserValue('pln_user_uuid', $user->getUID());
        $client = $this->getClient();
        $headers = array(
            'On-Behalf-Of' => $uuid,
            'User Name' => $user->getDisplayName(),
        );

        $response = $client->get($sdIri, ['headers' => $headers]);
        $xml = $response->xml();
        $this->ns->registerNamespaces($xml);
        return $xml;
    }

    public function getServiceDocument(IUser $user) {
        $xml = $this->fetchServiceDocument($user);
        return $xml->serviceDocument;
    }
    
    public function isAccepting(IUser $user) {
        $serviceDocument = $this->getServiceDocument($user);
        if( ! $serviceDocument) {
            return false;
        }
        return $serviceDocument->path('//pkp:pln_accepting/@is_accepting')[0] == "Yes";
    }

    public function getTermsOfUse(IUser $user) {
        $serviceDocument = $this->getServiceDocument($user);
        if( ! $serviceDocument) {
            return;
        }
        $serviceDocument->xpath('//pkp:terms_of_use/pkp:*');
        $terms = array();
        foreach($termsXml as $xml) {
            $terms[] = array(
                'text' => "{$xml}",
                'id' => $xml->getName(),
                'updated' => (string)$xml['updated'],
            );
        }
        return $terms;
    }

    public function getColIri(IUser $user) {
        $this->requireServiceDocument($user);
        if (!$this->serviceDocument) {
            return null;
        }
        $href = $this->serviceDocument->xpath('//app:collection/@href');
        return (string) $href[0];
    }

    /**
     * 
     * @param DOMDocument $atom
     * @return SimpleXMLElement
     */
    public function createDeposit(DOMDocument $atom) {
        $colIri = $this->getColIri();
        $request = $this->httpClient->createRequest('POST', $colIri);
        $request->setBody(Stream::factory($atom->saveXML()));
        $response = $this->httpClient->send($request);
        $xml = $response->xml();
        $this->ns->registerNamespaces($xml);
        return $xml;
    }

    public function reciept() {
        
    }

    public function statement() {
        
    }

}
