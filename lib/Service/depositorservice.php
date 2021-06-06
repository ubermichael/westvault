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
use GuzzleHttp\Exception\ServerException;
use OC\Files\Node\Root;
use OCA\WestVault\Db\DepositFile;
use OCA\WestVault\Db\DepositFileMapper;
use OCA\WestVault\Util\Namespaces;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of depositorservice.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DepositorService {
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
     * @var IGroupManager
     */
    private $groupManager;

    public function __construct(WestVaultConfig $config, SwordClient $client, Root $root, DepositFileMapper $mapper, IURLGenerator $generator, IUserManager $manager, IGroupManager $groupManager) {
        $this->config = $config;
        $this->client = $client;
        $this->root = $root;
        $this->mapper = $mapper;
        $this->generator = $generator;
        $this->manager = $manager;
        $this->groupManager = $groupManager;
    }

    /**
     * @return DOMDocument
     */
    protected function generateDepositXml(IUser $user, DepositFile $depositFile) {
        $userGroups = $this->groupManager->getUserGroups($user);
        if (1 !== count($userGroups)) {
            throw new Exception('User ' . $user->getDisplayName() . ' belongs to multiple groups. Cannot process deposits.');
        }
        // get the first group for the user.
        $groupKey = array_keys($userGroups)[0];

        $ns = new Namespaces();
        $atom = new DOMDocument('1.0', 'utf-8');
        $entry = $atom->createElementNS('http://www.w3.org/2005/Atom', 'entry');
        $atom->appendChild($entry);
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:pkp', $ns->getNamespace('pkp'));
        $entry->appendChild($atom->createElement('email', $user->getEMailAddress() ?? ''));
        $entry->appendChild($atom->createElement('title', $depositFile->filename()));
        $entry->appendChild($atom->createElement('id', 'urn:uuid:' . $depositFile->getUuid()));
        $entry->appendChild($atom->createElement('updated', strftime('%FT%TZ')));
        $meta = $atom->createElementNS($ns->getNamespace('lom'), 'meta');
        $entry->appendChild($meta);

        try {
            $file = $this->root->get($depositFile->getPath());
        } catch (Exception $e) {
            exit($e->getMessage());
        }
        $content = $atom->createElementNS(
            $ns->getNamespace('pkp'),
            'pkp:content',
            $this->generator->linkToRouteAbsolute('westvault.page.fetch', [
                'uuid' => $depositFile->getUuid(),
            ])
        );
        $content->setAttribute('size', (string) $file->getSize());
        $content->setAttribute('checksumType', $depositFile->getChecksumType());
        $content->setAttribute('checksumValue', $depositFile->getChecksumValue());
        $content->setAttribute('institution', $groupKey);
        $entry->appendChild($content);

        return $atom;
    }

    public function run(OutputInterface $output) : void {
        $files = $this->mapper->findNotDeposited();
        if (0 === count($files)) {
            $output->writeln('Nothing to deposit.', OutputInterface::VERBOSITY_VERBOSE);

            return;
        }
        foreach ($files as $depositFile) {
            $user = $this->manager->get($depositFile->getUserId());
            $output->writeln('Deposit ' . $depositFile->getUuid(), OutputInterface::VERBOSITY_VERBOSE);

            try {
                $atom = $this->generateDepositXml($user, $depositFile);
                $response = $this->client->createDeposit($user, $atom);
                $location = $response['location'];
                $responseXml = $response['xml'];
                $depositFile->setDateSent(time());
                $depositFile->setPlnStatus((string) $responseXml->xpath('//atom:category[@label="Processing State"]/@term')[0]);
                $depositFile->setPlnUrl($location);
                $this->mapper->update($depositFile);
            } catch (ServerException $ex) {
                echo $ex->getMessage() . "\n";
                echo $ex->getResponse()->getBody() . "\n";
            } catch (Exception $ex) {
                echo get_class($ex) . "\n" . $ex->getMessage() . "\n";
            }
        }
    }
}
