<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Util;

use ReflectionClass;
use SimpleXMLElement;

/**
 * Simplify handling namespaces for SWORD XML documents.
 */
class Namespaces {
    public const DCTERMS = 'http://purl.org/dc/terms/';

    public const SWORD = 'http://purl.org/net/sword/';

    public const ATOM = 'http://www.w3.org/2005/Atom';

    public const LOM = 'http://lockssomatic.info/SWORD2';

    public const RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

    public const APP = 'http://www.w3.org/2007/app';

    public const PKP = 'http://pkp.sfu.ca/SWORD';

    /**
     * Get the FQDN for the prefix, in a case-insensitive
     * fashion.
     *
     * @param string $prefix
     *
     * @return string
     */
    public function getNamespace($prefix) {
        $constant = __CLASS__ . '::' . mb_strtoupper($prefix);
        if ( ! defined($constant)) {
            return;
        }

        return constant($constant);
    }

    /**
     * Register all the known namespaces in a SimpleXMLElement.
     */
    public function registerNamespaces(SimpleXMLElement $xml) : void {
        $refClass = new ReflectionClass(__CLASS__);
        $constants = $refClass->getConstants();
        foreach (array_keys($constants) as $key) {
            $prefix = mb_strtolower($key);
            $xml->registerXPathNamespace($prefix, $this->getNamespace($prefix));
        }
    }
}
