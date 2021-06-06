<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Service;

use OC\Files\Node\Root;
use OCA\WestVault\Db\DepositFileMapper;
use OCP\Files\NotFoundException;
use OCP\IURLGenerator;
use OCP\IUserManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Service to check the status of deposits in the staging server.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class StatusService {
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
     * Build the service.
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
     * Run the service.
     *
     * @todo Refactor this a bit.
     *
     * @param type $all
     */
    public function run($all = false, OutputInterface $output) {
        $deleted = false;
        $files = $this->mapper->findNotChecked($all);
        $output->writeln('Checking status of ' . count($files) . ' deposits.', OutputInterface::VERBOSITY_VERBOSE);
        if (0 === count($files)) {
            return;
        }
        foreach ($files as $depositFile) {
            $user = $this->manager->get($depositFile->getUserId());
            $states = $this->client->statement($user, $depositFile->getPlnUrl());
            $depositFile->setDateChecked(time());
            $depositFile->setPlnStatus($states['pln']);
            $depositFile->setLockssStatus($states['lockss']);
            $this->mapper->update($depositFile);
            if (('agreement' === $states['lockss']) && ('cleanup' === $this->config->getUserValue('pln_user_cleanup', $user->getUID(), 'b:0'))) {
                try {
                    $file = $this->root->get($depositFile->getPath());
                    $file->delete();
                    $deleted = true;
                } catch (NotFoundException $e) {
                    $output->writeln("File not found: {$e->getMessage()}");
                }
            }
        }
    }
}
