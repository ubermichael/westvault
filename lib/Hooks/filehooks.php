<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Hooks;

use OC\Files\Node\File;
use OC\Files\Node\Root;
use OCA\WestVault\Db\DepositFile;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Service\WestVaultConfig;
use OCP\Files\FileInfo;
use OCP\Files\Node;
use OCP\ILogger;
use OCP\IUserManager;
use Ramsey\Uuid\Uuid;

/**
 * Collection of hooks for the plugin.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class FileHooks {
    /**
     * @var IUserManager
     */
    private $manager;

    /**
     * @var WestVaultConfig
     */
    private $config;

    /**
     * @var Root
     */
    private $root;

    /**
     * @var DepositFileMapper
     */
    private $mapper;

    /**
     * @var ILogger
     */
    private $logger;

    /**
     * Build an object with the file hooks.
     */
    public function __construct(IUserManager $manager, WestVaultConfig $config, Root $root, DepositFileMapper $mapper, ILogger $logger) {
        $this->manager = $manager;
        $this->config = $config;
        $this->root = $root;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    /**
     * Stream a file through a hashing function.
     *
     * @todo move this to a new service so it isn't duplicated everywhere.
     *
     * @param string $algorithm
     *
     * @return string
     */
    private function hash($algorithm, Node $file) {
        $context = hash_init($algorithm);
        $handle = fopen($this->config->getSystemValue('datadirectory') . $file->getPath(), 'r');
        if( ! $handle) {
            throw new \Exception("Cannot read " . $this->config->getSystemValue('datadirectory') . $file->getPath());
        }
        while (($data = fread($handle, 64 * 1024))) {
            hash_update($context, $data);
        }
        $hash = hash_final($context);
        fclose($handle);

        return $hash;
    }

    /**
     * Register all the hooks for the plugin.
     */
    public function register() : void {
        $this->root->listen('\OC\Files', 'postCreate', [$this, 'postCreate']);
        $this->root->listen('\OC\Files', 'postDelete', [$this, 'postDelete']);
        $this->root->listen('\OC\Files', 'postRename', [$this, 'postRename']);
    }

    /**
     * Callback for the post file create hook.
     */
    public function postCreate(Node $file) {
        if (FileInfo::TYPE_FILE !== $file->getType()) {
            return;
        }
        $uid = $file->getOwner()->getUID();
        if ( ! $this->config->getUserValue('pln_user_agreed', $uid, null)) {
            return;
        }
        if ( ! $this->config->getUserValue('pln_user_preserved_folder', $uid, null)) {
            return;
        }
        foreach ($this->config->getIgnoredPatterns($uid) as $pattern) {
            if (preg_match("/^{$pattern}$/", $file->getName())) {
                return;
            }
        }
        $watchFolder = $this->config->getUserValue('pln_user_preserved_folder', $uid);
        $userPath = $this->root->getUserFolder($uid)->getPath();
        $localPath = mb_substr($file->getPath(), mb_strlen($userPath) + 1);

        if (mb_substr($localPath, 0, mb_strlen($watchFolder)) !== $watchFolder) {
            return;
        }
        $checksumType = $this->config->getAppValue('pln_site_checksum_type', 'sha1');
        $depositFile = new DepositFile();
        $depositFile->setFileId($file->getId());
        $depositFile->setUserId($uid);
        $depositFile->setUuid(Uuid::uuid4());
        $depositFile->setPath($file->getPath());
        $depositFile->setChecksumType($checksumType);
        $depositFile->setChecksumValue($this->hash($checksumType, $file));
        $depositFile->setDateUploaded(time());
        $depositFile->setDateSent(null);
        $depositFile->setDateChecked(null);
        $this->mapper->insert($depositFile);
    }

    /**
     * Callback for the post file delete hook.
     */
    public function postDelete(Node $file) {
        $depositFile = $this->mapper->findByPath($file->getPath());
        if ( ! $depositFile || $depositFile->sent()) {
            return;
        }
        $this->mapper->delete($depositFile);
    }

    public function postRename(Node $source, Node $target) : void {
        $uid = $target->getOwner()->getUID();
        $depositFile = $this->mapper->findByPath($source->getPath());

        if ( ! $depositFile) {
            // Maybe moved into the preserved folder.
            $this->postCreate($target);

            return;
        }

        if ($depositFile->sent()) {
            // already sent to the PLN. Not sure what to do here.
            return;
        }

        foreach ($this->config->getIgnoredPatterns($uid) as $pattern) {
            if (preg_match("/^{$pattern}$/", $target->getName())) {
                // moved to an ignored file name.
                $this->mapper->delete($depositFile);

                return;
            }
        }
        $watchFolder = $this->config->getUserValue('pln_user_preserved_folder', $uid);
        $userPath = $this->root->getUserFolder($uid)->getPath();
        $localPath = mb_substr($target->getPath(), mb_strlen($userPath) + 1);

        if (mb_substr($localPath, 0, mb_strlen($watchFolder)) !== $watchFolder) {
            // moved out of the lockss preserved folder.
            $this->mapper->delete($depositFile);

            return;
        }

        $depositFile->setPath($target->getPath());
        $depositFile->setDateUploaded(time());
        $this->mapper->update($depositFile);
    }
}
