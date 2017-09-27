<?php

namespace OCA\WestVault\Service;

use DOMDocument;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;
use OCA\WestVault\Util\Namespaces;
use OCP\IUser;
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;

/**
 * Sword Client to interact with the staging server with the SWORD protocol.
 * 
 * Don't use the sword protocol.
 */
class SwordClient {

    /**
     * @var PlnConfig
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
     * Build the client.
     * 
     * @param PlnConfig $config
     */
    public function __construct(WestVaultConfig $config) {
        $this->config = $config;
        $this->ns = new Namespaces();
    }

    /**
     * Set the HTTP client. Useful for testing, useless otherwise.
     * 
     * @param Client $client
     */
    public function setClient(Client $client) {
        $this->httpClient = $client;
    }

    /**
     * Get (or create) an HTTP client.
     * 
     * @return Client
     */
    public function getClient() {
        if (!$this->httpClient) {
            $this->httpClient = new Client();
        }
        return $this->httpClient;
    }

    /**
     * Fetch the service document from the staging server.
     * 
     * @param IUser $user
     * @throws Exception if the server returns something that isn't XML.
     */
    protected function fetchServiceDocument(IUser $user) {
        $sdIri = $this->config->getSystemValue('pln_site_url');
        if (!$sdIri) {
            throw new Exception("Cannot find PLN Service Document URL");
        }
        $uuid = $this->config->getUserValue('pln_user_uuid', $user->getUID());
        if( ! $uuid) {
            $uuid = Uuid::NIL;
        }
        $client = $this->getClient();
        $headers = array(
            'On-Behalf-Of' => $uuid,
            'Provider-Name' => $user->getUID(),
        );

        $response = $client->get($sdIri, ['headers' => $headers]);
        $xml = simplexml_load_string($response->getBody());
        if($xml === false) {
            throw new Exception("Cannot parse service document: " . implode("\n", libxml_get_errors()));
        }
        $this->ns->registerNamespaces($xml);
        $this->serviceDocument = $xml;
    }

    /**
     * Gets the service document and caches it for reuse.
     * 
     * @param IUser $user
     * @return SimpleXMLElement
     */
    public function getServiceDocument(IUser $user) {
        if (!$this->serviceDocument) {
            $this->fetchServiceDocument($user);
        }
        return $this->serviceDocument;
    }
    
    /**
     * Find when the term of use were most recently updated.
     * 
     * @param IUser $user
     * @return string
     */
    public function getTermsUpdated(IUser $user) {
        $this->getServiceDocument($user);
        return (string)$this->serviceDocument->xpath('//pkp:terms_of_use/@updated')[0];    
    }

    /**
     * Get the terms of use.
     * 
     * @param IUser $user
     * @return array
     */
    public function getTermsOfUse(IUser $user) {
        $this->getServiceDocument($user);
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

    /**
     * Get the SWORD collection URI from the service document.
     * 
     * @return string
     */
    public function getColIri(IUser $user) {
        $this->getServiceDocument($user);
        if (!$this->serviceDocument) {
            return null;
        }
        $href = $this->serviceDocument->xpath('//app:collection/@href');
        return (string)$href[0];
    }

    /**
     * Create a deposit in the staging server.
     * 
     * @param DOMDocument $atom
     * @return SimpleXMLElement
     */
    public function createDeposit(IUser $user, DOMDocument $atom) {
        $colIri = $this->getColIri($user);
        $request = $this->httpClient->createRequest('POST', $colIri);
        $request->setBody(Stream::factory($atom->saveXML()));        
        $response = $this->httpClient->send($request, []);
        $xml = simplexml_load_string($response->getBody());
        if($xml === false) {
            throw new Exception("Cannot parse response document: " . implode("\n", libxml_get_errors()));
        }
        $this->ns->registerNamespaces($xml);
        
        return $xml;
    }

    public function reciept() {
        
    }

    public function statement() {
        
    }

}
