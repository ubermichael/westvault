<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Description of DepositFile.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DepositFile extends Entity {
    /**
     * @var int
     */
    protected $fileId;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $checksumType;

    /**
     * @var string
     */
    protected $checksumValue;

    /**
     * @var string
     */
    protected $plnStatus;

    /**
     * @var string
     */
    protected $plnUrl;

    /**
     * @var string
     */
    protected $lockssStatus;

    /**
     * @var float
     */
    protected $agreement;

    /**
     * @var int
     */
    protected $dateUploaded;

    /**
     * @var int
     */
    protected $dateSent;

    /**
     * @var int
     */
    protected $dateChecked;

    /**
     * Build a DepositFile. Does some very simple type hinting.
     */
    public function __construct() {
        $this->addType('fileId', 'int');
        $this->addType('agreement', 'float');
    }

    /**
     * Check if the file has been sent to the PLN.
     *
     * @return bool
     */
    public function sent() {
        return null !== $this->plnUrl;
    }

    /**
     * @return string
     */
    public function filename() {
        return basename($this->path);
    }
}
