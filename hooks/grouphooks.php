<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Hooks;

use OC\Group\Group;
use OC\Group\Manager;
use OCA\WestVault\Service\WestVaultConfig;
use Ramsey\Uuid\Uuid;

/**
 * Description of grouphooks
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class GroupHooks {
    
    /**
     * @var Manager 
     */
    private $groupManager;

    /**
     * @var WestVaultConfig
     */
    private $config;
    
    public function __construct(Manager $groupManager, WestVaultConfig $config) {
        error_log('constructing');
        $this->groupManager = $groupManager;
        $this->config = $config;
    }
    
    public function register() {
        error_log('registering');
        $callback = function(Group $group) {
            error_log('callback called');
            $this->config->setGroupValue('pln_group_uuid', $group->getGID(), Uuid::uuid4()->toString());
        };
        $this->groupManager->listen('\OC\Group', 'postCreate', $callback);
    }
    
}
