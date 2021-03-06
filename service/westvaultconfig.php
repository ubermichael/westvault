<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Service;

use OCP\IConfig;

/**
 * Configuration manager for the plugin.
 */
class WestVaultConfig {

    /**
     * @var IConfig
     */
    private $config;

    /**
     * @var string
     */
    private $appName;

    /**
     * Build the config manager.
     * 
     * @param IConfig $config
     * @param type $appName
     */
    public function __construct(IConfig $config, $appName) {
        $this->config = $config;
        $this->appName = $appName;
    }

    /**
     * Fetch a global system configuration value from config.php. Global settings 
     * like the staging server URI are system values.
     * 
     * @param string $key
     * @return string
     */
    public function getSystemValue($key, $default = '') {
        return $this->config->getSystemValue($key, $default);
    }

    /**
     * Set a global system value.
     * 
     * @param string $key
     * @param string $value
     * @return string
     */
    public function setSystemValue($key, $value) {
        $this->config->setSystemValue($key, $value);
    }

    /**
     * App values are stored in the database. Examples include the 
     * Terms of Service and the last TOS check.
     * 
     * @param string $key
     * @return string
     */
    public function getAppValue($key, $default = '') {
        return $this->config->getAppValue($this->appName, $key, $default);
    }

    /**
     * Set an app value.
     * 
     * @param string $key
     * @param string $value
     * @return string
     */
    public function setAppValue($key, $value) {
        $this->config->setAppValue($this->appName, $key, $value);
    }

    /**
     * Get a user value. Examples of user values include accepting the terms
     * of service.
     * 
     * @param string $key
     * @return string
     */
    public function getUserValue($key, $userId, $default = '') {
        return $this->config->getUserValue($userId, $this->appName, $key, $default);
    }

    /**
     * Get the ignored file patterns for a user.
     * 
     * @param string $userId
     */
    public function getIgnoredPatterns($userId) {
        $regexes = [];
        $ignoreStrings = $this->config->getUserValue($userId, $this->appName, 'pln_user_ignore', '') .
                "\n" .
                $this->config->getAppValue($this->appName, 'pln_site_ignore', $userId) .
                "\n";
        $ignorePatterns = explode("\n", $ignoreStrings);
        foreach ($ignorePatterns as $pattern) {
            $regexes[] = str_replace(['.', '*'], ['\\.', '.*'], trim($pattern));
        }
        return array_filter($regexes);
    }

    /**
     * Set a user value.
     * 
     * @param string $key
     * @return string
     */
    public function setUserValue($key, $userId, $value) {
        // never change the uuid.
        if ($key === 'uuid' && $this->config->getUserValue($userId, 'uuid', null) !== null) {
            return;
        }
        $this->config->setUserValue($userId, $this->appName, $key, $value);
    }

}
