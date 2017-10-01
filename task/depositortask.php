<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Task;

use OCA\WestVault\AppInfo\Application;

/**
 * Description of depositortask
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class DepositorTask {

    public static function run() {
        $app = new Application('westvault');
        $container = $app->getContainer();
        $container->query('DepositorService')->run();
    }

}
