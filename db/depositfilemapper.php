<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;
use OCP\IUser;

/**
 * Description of DepositFileMapper
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DepositFileMapper extends Mapper {
    
    const TABLE = 'westvault_depositfiles';
    
    const TBL = '*PREFIX*westvault_depositfiles';
    
    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE);
    }
    
    public function find($id) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `id` = :id";
        return $this->findEntity($sql, array('id' => $id));
    }
    
    public function findByFileId($fileId) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `file_id` = :file_id";
        return $this->findEntity($sql, array('file_id' => $fileId));
    }
    
    public function findByUuid($uuid) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `uuid` = :uuid";
        return $this->findEntity($sql, array('uuid' => $uuid));
    }
    
    public function findByPath($path) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `path` = :path";
        return $this->findEntity($sql, array('path' => $path));        
    }
    
    public function findByUser(IUser $user) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `user_id` = :user_id";
        return $this->findEntities($sql, array('user_id' => $user->getUID()));        
    }
    
    public function findNotDeposited() {
        
    }
}
