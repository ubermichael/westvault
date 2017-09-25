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
    
    protected $fileId;
    
    protected $userId;
    
    protected $uuid;
    
    protected $path;
    
    protected $checksumType;
    
    protected $checksumValue;
    
    protected $plnStatus;
    
    protected $lockssStatus;
    
    protected $agreement;
    
    protected $dateSent;
    
    protected $dateChecked;
    
}
