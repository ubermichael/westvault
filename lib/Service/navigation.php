<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace OCA\WestVault\Service;

use OCP\IURLGenerator;

/**
 * Build all the navigational elements for the plugin.
 */
class Navigation {
    /**
     * @var IURLGenerator
     */
    private $urlGenerator;

    /**
     * Build the navigation manager.
     */
    public function __construct(IURLGenerator $urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Get the list of links for the navigation.
     *
     * @return array
     */
    public function linkList() {
        return [
            [
                'id' => 'westvault_home',
                'name' => 'Preserved Files',
                'url' => $this->urlGenerator->linkToRoute('westvault.page.index'),
            ], [
                'id' => 'westvault_config',
                'name' => 'Settings',
                'url' => $this->urlGenerator->linkToRoute('westvault.config.index'),
            ],
        ];
    }
}
