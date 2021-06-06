<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
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
    public const TABLE = 'westvault_depositfiles';

    /**
     * Prefixed version of the database table, for the queries. I dunno.
     */
    public const TBL = '*PREFIX*westvault_depositfiles';

    /**
     * Number of seconds to hold a deposit before sending it off to the staging
     * server.
     */
    public const WAIT = 24 * 60 * 60;

    /**
     * Build the mapper.
     */
    public function __construct(IDBConnection $db) {
        parent::__construct($db, self::TABLE, DepositFile::class);
    }

    /**
     * Find the DepositFile with the given ID.
     *
     * @param string $id
     *
     * @return Entity|DepositFile
     */
    public function find($id) {
        $sql = 'SELECT * FROM ' . self::TBL . ' WHERE `id` = :id';

        return $this->findEntity($sql, ['id' => $id]);
    }

    /**
     * Find a DepositFile corresponding to the fileId. May return null.
     *
     * @param int $fileId
     *
     * @return Entity|DepositFile
     */
    public function findByFileId($fileId) {
        $sql = 'SELECT * FROM ' . self::TBL . ' WHERE `file_id` = :file_id';

        return $this->findEntity($sql, ['file_id' => $fileId]);
    }

    /**
     * Find a DepositFile corresponding a UUID. May return null.
     *
     * @param string $uuid
     * @return Entity|DepositFile|null
     */
    public function findByUuid($uuid) {
        $sql = 'SELECT * FROM ' . self::TBL . ' WHERE `uuid` = :uuid';

        return $this->findEntity($sql, ['uuid' => $uuid]);
    }

    /**
     * Find a DepositFile corresponding a path. May return null.
     *
     * @param string $path
     *
     * @return Entity|DepositFile|null
     */
    public function findByPath($path) {
        $sql = 'SELECT * FROM ' . self::TBL . ' WHERE `path` = :path';

        try {
            return $this->findEntity($sql, ['path' => $path]);
        } catch (DoesNotExistException $e) {
            return null;
        } catch (MultipleObjectsReturnedException $e) {
            return null;
        }
    }

    /**
     * Find deposit files for a user.
     *
     * @return Entity[]|DepositFile[]
     */
    public function findByUser(IUser $user) {
        $sql = 'SELECT * FROM ' . self::TBL . ' WHERE `user_id` = :user_id';

        return $this->findEntities($sql, ['user_id' => $user->getUID()]);
    }

    /**
     * Find files which have not been sent to the staging server yet.
     *
     * @return Entity[]|DepositFile[]
     */
    public function findNotDeposited() {
        $sql = 'SELECT * FROM ' . self::TBL . ' WHERE (`date_sent` IS NULL) AND (`date_uploaded` < :past) ORDER BY `id`';

        return $this->findEntities($sql, ['past' => time() - (self::WAIT)]);
    }

    /**
     * Find files which have not been checked recently.
     *
     * @param bool $all
     *
     * @return Entity[]|DepositFile[]
     */
    public function findNotChecked($all = false) {
        if ($all) {
            $sql = 'SELECT * FROM ' . self::TBL . " WHERE (`pln_status` is not null) AND (`lockss_status` is null OR `lockss_status` <> 'agreement') ORDER BY `id`";
        } else {
            $sql = 'SELECT * FROM ' . self::TBL . " WHERE (`pln_status` is not null) AND (`lockss_status` is null OR `lockss_status` <> 'agreement') AND (`date_checked` IS NULL OR `date_checked` < :past) ORDER BY `id`";
        }

        return $this->findEntities($sql, ['past' => time() - (self::WAIT)]);
    }

    /**
     * Find files which need to be restored.
     *
     * @return Entity[]|DepositFile[]
     */
    public function findRestoreQueue() {
        $sql = 'SELECT * FROM ' . self::TBL . " WHERE (`pln_status` IN ('restore', 'restore-error')) AND (`lockss_status` = 'agreement') ORDER BY `id`";

        return $this->findEntities($sql);
    }
}
