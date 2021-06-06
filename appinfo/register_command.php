<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

use OCA\WestVault\Command\Deposit;
use OCA\WestVault\Command\Restore;
use OCA\WestVault\Command\Status;

// Register the plugin commands.

// @var $application Symfony\Component\Console\Application
$application->add(new Deposit());
$application->add(new Restore());
$application->add(new Status());
