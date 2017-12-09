<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Description of DepositFile
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
     * @return boolean
     */
    public function sent() {
        return $this->plnUrl !== null;
    }

    /**
     * @return string
     */
    public function filename() {
        return basename($this->path);
    }

}
