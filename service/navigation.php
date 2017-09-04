<?php

/* 
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2017 Michael Joyce <ubermichael@gmail.com>.
 */

namespace OCA\WestVault\Service;

use OCP\IURLGenerator;

class Navigation {
    
    /**
     * @var IURLGenerator
     */
    private $urlGenerator;

    public function __construct(IURLGenerator $urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }
    
    public function linkList() {
        
        return [
            [
                'id' => 'westvault_home',
                'name' => 'Preserved Files',
                'url' => $this->urlGenerator->linkToRoute('westvault.page.index'),
            ],
            [
                'id' => 'westvault_config',
                'name' => 'Settings',
                'url' => $this->urlGenerator->linkToRoute('westvault.config.index'),
            ],
        ];
        
    }
    
}