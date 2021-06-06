<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use OC\Files\Node\Root;
use OCA\WestVault\Db\DepositFile;
use OCA\WestVault\Db\DepositFileMapper;
use OCP\IURLGenerator;
use OCP\IUserManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Service to restore content from the staging server.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class RestoreService {
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
     * Construct the service.
     */
    public function __construct(WestVaultConfig $config, SwordClient $client, Root $root, DepositFileMapper $mapper, IURLGenerator $generator, IUserManager $manager) {
        $this->config = $config;
        $this->client = $client;
        $this->root = $root;
        $this->mapper = $mapper;
        $this->generator = $generator;
        $this->manager = $manager;
    }

    /**
     * Fetch a deposit from the staging server and store it in a temporary file.
     * Returns the path to the temporary file.
     *
     * @param DepositFile $depositFile
     *
     * @return ?string
     */
    public function fetchFile(DepositFile $depositFile, OutputInterface $output) {
        $output->writeln($depositFile->filename(), OutputInterface::VERBOSITY_VERBOSE);
        $user = $this->manager->get($depositFile->getUserId());
        $url = $this->client->restoreUrl($user, $depositFile->getPlnUrl());
        $client = new Client();
        $filepath = tempnam(sys_get_temp_dir(), 'lom-cfs-');

        try {
            $client->get($url, [
                'save_to' => $filepath,
            ]);
        } catch (RequestException $e) {
            $output->writeln("Cannot download content from {$url}: {$e->getMessage()}");
            $depositFile->setPlnStatus('restore-error');
            $this->mapper->update($depositFile);

            return null;
        }

        return $filepath;
    }

    /**
     * @param DepositFile $depositFile
     * @param string $filepath
     * @param OutputInterface $output
     *
     * @return false|resource|null
     */
    public function verifyChecksum(DepositFile $depositFile, $filepath, OutputInterface $output) {
        $handle = fopen($filepath, 'rb');
        if( ! $handle) {
            throw new \Exception("Cannot read " . $this->config->getSystemValue('datadirectory') . $file->getPath());
        }
        $hashContext = hash_init($depositFile->getChecksumType());
        while ($data = fread($handle, 1024 * 64)) {
            hash_update($hashContext, $data);
        }
        $hash = hash_final($hashContext);
        if ($hash !== $depositFile->getChecksumValue()) {
            $depositFile->setPlnStatus('restore-error');
            $output->writeln("Hash mismatch. Expected {$depositFile->getChecksumValue()} got {$hash}");
            $this->mapper->update($depositFile);

            return null;
        }
        rewind($handle);

        return $handle;
    }

    /**
     * @param DepositFile $depositFile
     * @param resource $handle
     * @param OutputInterface $output
     *
     * @throws \OCP\Files\NotPermittedException
     * @throws \OC\User\NoUserException
     */
    public function writeFile(DepositFile $depositFile, $handle, OutputInterface $output) : void {
        $user = $this->manager->get($depositFile->getUserId());
        $userFolder = $this->root->getUserFolder($depositFile->getUserId());
        $restoreFolderName = $this->config->getUserValue('pln_user_restored_folder', $user->getUID());
        $path = $restoreFolderName . '/' . $depositFile->filename();
        $output->writeln("  restoring to {$path}", OutputInterface::VERBOSITY_VERBOSE);
        $file = $userFolder->newFile($path);
        $destHandle = $file->fopen('w');
        while ($data = fread($handle, 1024 * 64)) {
            fwrite($destHandle, $data);
        }
    }

    /**
     * @param OutputInterface $output
     *
     * @throws \OCP\Files\NotPermittedException
     * @throws \OC\User\NoUserException
     */
    public function run(OutputInterface $output) : void {
        $files = $this->mapper->findRestoreQueue();
        $output->writeln('restoring ' . count($files), OutputInterface::VERBOSITY_VERBOSE);
        foreach ($files as $depositFile) {
            $filepath = $this->fetchFile($depositFile, $output);
            if (null === $filepath) {
                continue;
            }
            $handle = $this->verifyChecksum($depositFile, $filepath, $output);
            if ( ! $handle) {
                continue;
            }
            $this->writeFile($depositFile, $handle, $output);
            $depositFile->setPlnStatus('restore-complete');
            $this->mapper->update($depositFile);
        }
    }
}
