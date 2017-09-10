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
use OCA\CoppulPln\Util\Namespaces;
use OCP\IURLGenerator;
use SimpleXMLElement;

/**
 * Description of swordclient
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class SwordSlient {

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
     * @var SimpleXMLElement
     */
    private $serviceDocument;

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

    protected function fetchServiceDocument(\OCP\IGroup $group) {
        $uuid = $this->config->getGroupValue('pln_group_uuid', $group->getGID());
        $sdIri = $this->config->getAppValue('pln_site_url');
        if (!$uuid || !$sdIri) {
            return;
        }
        $client = $this->getClient();
        $headers = array(
            'On-Behalf-Of' => $uuid,
            'Group Name' => $group->getGID(),
        );

        $response = $client->get($sdIri, ['headers' => $headers]);
        $xml = $response->xml();
        $this->ns->registerNamespaces($xml);
        $this->serviceDocument = $xml;
    }

    protected function requireServiceDocument() {
        if (!$this->serviceDocument) {
            $this->fetchServiceDocument();
        }
    }

    public function getServiceDocument() {
        $this->requireServiceDocument();
        return $this->serviceDocument;
    }

    public function getTermsOfUse() {
        $this->requireServiceDocument();
        if (!$this->serviceDocument) {
            return null;
        }
        $termsXml = $this->serviceDocument->xpath('//pkp:terms_of_use/pkp:*');
        $terms = array();
        foreach ($termsXml as $xml) {
            $terms[] = array(
                'text' => "{$xml}",
                'id' => $xml->getName(),
                'updated' => (string) $xml['updated'],
            );
        }
        return $terms;
    }

    public function getColIri() {
        $this->requireServiceDocument();
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
