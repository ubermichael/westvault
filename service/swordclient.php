<?php

namespace OCA\WestVault\Service;

use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;
use OCA\WestVault\Util\Namespaces;
use OCP\IURLGenerator;
use OCP\IUser;
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;

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
     * @param PlnConfig $config
     */
    public function __construct(WestVaultConfig $config) {
        $this->config = $config;
        $this->ns = new Namespaces();
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
            throw new \Exception("Cannot parse service document: " . implode("\n", libxml_get_errors()));
        }
        $this->ns->registerNamespaces($xml);
        $this->serviceDocument = $xml;
    }

    public function getServiceDocument(IUser $user) {
        if (!$this->serviceDocument) {
            $this->fetchServiceDocument($user);
        }
        return $this->serviceDocument;
    }
    
    public function getTermsUpdated(IUser $user) {
        $this->getServiceDocument($user);
        return (string)$this->serviceDocument->xpath('//pkp:terms_of_use/@updated')[0];    
    }

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

    public function getColIri() {
        $this->getServiceDocument();
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
