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
 * Map DepositFile objects from the database. Mostly it's just a wrapper
 * around some SQL queries to get the DepositFile objects out of the database.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DepositFileMapper extends Mapper {
    
    /**
     * DB table name for the mapper - must not include the prefix for some 
     * reason.
     */
    const TABLE = 'westvault_depositfiles';

    /**
     * Prefixed version of the database table, for the queries. I dunno.
     */
    const TBL = '*PREFIX*westvault_depositfiles';
    
    /**
     * Build the mapper.
     * 
     * @param IDBConnection $db
     */
    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE);
    }
    
    /**
     * Find the DepositFile with the given ID.
     * 
     * @param string $id
     * @return DepositFile
     */
    public function find($id) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `id` = :id";
        return $this->findEntity($sql, array('id' => $id));
    }

    /**
     * Find a DepositFile corresponding to the fileId. May return null.
     * 
     * @param type $fileId
     * @return DepositFile|null
     */
    public function findByFileId($fileId) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `file_id` = :file_id";
        return $this->findEntity($sql, array('file_id' => $fileId));
    }
    
    /**
     * Find a DepositFile corresponding a UUID. May return null.
     * 
     * @param string $uuid
     * @return DepositFile|ull
     */
    public function findByUuid($uuid) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `uuid` = :uuid";
        return $this->findEntity($sql, array('uuid' => $uuid));
    }
    
    /**
     * Find a DepositFile corresponding a path. May return null.
     * 
     * @param string $path
     * @return DepositFile|ull
     */
    public function findByPath($path) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `path` = :path";
        return $this->findEntity($sql, array('path' => $path));        
    }
    
    /**
     * Find deposit files for a user.
     * 
     * @param IUser $user
     * @return DepositFile[]
     */
    public function findByUser(IUser $user) {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `user_id` = :user_id";
        return $this->findEntities($sql, array('user_id' => $user->getUID()));        
    }

    /**
     * Find files which have not been sent to the staging server yet.
     * @return DepositFile[]
     */
    public function findNotDeposited() {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `date_sent` IS NULL ORDER BY `id`";
        return $this->findEntities($sql);        
    }
    
    public function findNotChecked() {
        $sql = "SELECT * FROM " . self::TBL . " WHERE `date_checked` IS NULL OR `date_checked` < :past ORDER BY `id`";
        return $this->findEntities($sql, array('past' => time() - ( 24 * 60 * 60 )));        
    }
}
