<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

use OCA\WestVault\Command\Deposit;
use OCA\WestVault\Command\Restore;
use OCA\WestVault\Command\Status;

/*
 * Register the plugin commands.
 */

/** @var $application Symfony\Component\Console\Application */
$application->add(new Deposit());
$application->add(new Restore());
$application->add(new Status());
