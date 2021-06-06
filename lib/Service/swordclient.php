<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Service;

use DOMDocument;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;
use OCA\WestVault\Util\Namespaces;
use OCP\ILogger;
use OCP\IUser;
use Ramsey\Uuid\Uuid;

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
     * @var Client
     */
    private $httpClient;

    /**
     * @var ILogger
     */
    private $logger;

    /**
     * @var Namespaces
     */
    private $ns;

    /**
     * Build the client.
     *
     * @param PlnConfig $config
     */
    public function __construct(WestVaultConfig $config, ILogger $logger) {
        $this->config = $config;
        $this->logger = $logger;
        $this->ns = new Namespaces();
    }

    /**
     * Fetch the service document from the staging server.
     *
     * @throws Exception if the server returns something that isn't XML.
     */
    protected function getServiceDocument(IUser $user) {
        $sdIri = $this->config->getSystemValue('pln_site_url');
        if ( ! $sdIri) {
            throw new Exception('Cannot find PLN Service Document URL.');
        }
        $uuid = $this->config->getUserValue('pln_user_uuid', $user->getUID());
        if ( ! $uuid) {
            $uuid = Uuid::NIL;
        }
        $client = $this->getClient();
        $headers = [
            'On-Behalf-Of' => $uuid,
            'Provider-Name' => $user->getUID(),
        ];

        $response = $client->get($sdIri, ['headers' => $headers]);
        $xml = simplexml_load_string($response->getBody());
        if (false === $xml) {
            throw new Exception('Cannot parse service document: ' . implode("\n", libxml_get_errors()));
        }
        $this->ns->registerNamespaces($xml);

        return $xml;
    }

    /**
     * Set the HTTP client. Useful for testing, useless otherwise.
     */
    public function setClient(Client $client) : void {
        $this->httpClient = $client;
    }

    /**
     * Get (or create) an HTTP client.
     *
     * @return Client
     */
    public function getClient() {
        if ( ! $this->httpClient) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    /**
     * Find when the term of use were most recently updated.
     *
     * @return string
     */
    public function getTermsUpdated(IUser $user) {
        $serviceDoc = $this->getServiceDocument($user);

        return (string) $serviceDoc->xpath('//pkp:terms_of_use/@updated')[0];
    }

    /**
     * Get the terms of use.
     *
     * @return array
     */
    public function getTermsOfUse(IUser $user) {
        $serviceDoc = $this->getServiceDocument($user);
        $termsXml = $serviceDoc->xpath('//pkp:terms_of_use/pkp:*');
        $terms = [];
        foreach ($termsXml as $xml) {
            $terms[] = [
                'text' => "{$xml}",
                'id' => $xml->getName(),
                'updated' => (string) $xml['updated'],
            ];
        }

        return $terms;
    }

    /**
     * Get the SWORD collection URI from the service document.
     *
     * @return string
     */
    public function getColIri(IUser $user) {
        $serviceDoc = $this->getServiceDocument($user);
        $href = $serviceDoc->xpath('//app:collection/@href');

        return (string) $href[0];
    }

    /**
     * Returns true if the PLN will accept deposits from the user.
     *
     * @return bool
     */
    public function isAccepting(IUser $user) {
        $serviceDoc = $this->getServiceDocument($user);
        $accepting = $serviceDoc->xpath('//pkp:pln_accepting/@is_accepting');

        return 'Yes' === (string) $accepting[0];
    }

    /**
     * Gets the server message.
     *
     * @return string
     */
    public function getMessage(IUser $user) {
        $serviceDoc = $this->getServiceDocument($user);
        $message = $serviceDoc->xpath('//pkp:pln_accepting/text()');

        return (string) $message[0];
    }

    /**
     * Create a deposit in the staging server. The returned array has keys
     * 'location' and 'xml' which are the location of the SWORD statement for
     * the deposit and the SimpleXML response body, respectively.
     *
     * @return array
     */
    public function createDeposit(IUser $user, DOMDocument $atom) {
        if ( ! $this->isAccepting($user)) {
            throw new Exception("The staging server is not accepting deposits from {$user->getUID()}.");
        }

        $colIri = $this->getColIri($user);
        $request = $this->httpClient->createRequest('POST', $colIri);
        $request->setBody(Stream::factory($atom->saveXML()));
        $response = $this->httpClient->send($request, []);
        $xml = simplexml_load_string($response->getBody());
        $location = $response->getHeader('Location');
        if (false === $xml) {
            throw new Exception('Cannot parse response document: ' . implode("\n", libxml_get_errors()));
        }
        $this->ns->registerNamespaces($xml);

        return ['location' => $location, 'xml' => $xml];
    }

    /**
     * Fetch the deposit statement from the server.
     *
     * @param string $plnUrl
     *
     * @throws Exception
     *
     * @return array
     */
    public function statement(IUser $user, $plnUrl) {
        if ( ! $this->isAccepting($user)) {
            throw new Exception("The staging server is not accepting deposits from {$user->getUID()}.");
        }
        $request = $this->httpClient->createRequest('GET', $plnUrl);
        $response = $this->httpClient->send($request, []);
        $xml = simplexml_load_string($response->getBody());
        if (false === $xml) {
            throw new Exception('Cannot parse response document: ' . implode("\n", libxml_get_errors()));
        }
        $this->ns->registerNamespaces($xml);
        $plnStatus = (string) $xml->xpath('//atom:category[@label="Processing State"]/@term')[0];
        $lockssStatus = (string) $xml->xpath('//atom:category[@label="PLN State"]/@term')[0];

        return [
            'pln' => $plnStatus,
            'lockss' => $lockssStatus,
        ];
    }

    /**
     * Use the deposit statement to get the restoration URL from the
     * staging server.
     *
     * @param string $plnUrl
     *
     * @throws Exception
     *
     * @return string
     */
    public function restoreUrl(IUser $user, $plnUrl) {
        if ( ! $this->isAccepting($user)) {
            throw new Exception("The staging server is not accepting deposits from {$user->getUID()}.");
        }
        $request = $this->httpClient->createRequest('GET', $plnUrl);
        $response = $this->httpClient->send($request, []);
        $xml = simplexml_load_string($response->getBody());
        if (false === $xml) {
            throw new Exception('Cannot parse response document: ' . implode("\n", libxml_get_errors()));
        }
        $this->ns->registerNamespaces($xml);
        $elements = $xml->xpath('//sword:originalDeposit');
        if ( ! $elements || count($elements) < 1) {
            return;
        }
        if (count($elements) > 1) {
            throw new Exception('Multiple content items in deposits are not supported.');
        }

        return $elements[0]['href'];
    }
}
