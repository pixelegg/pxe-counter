<?php

declare(strict_types=1);

use Pixelegg\PxeCounter\Controller\Backend\OverviewController;

// Web > Counter (TYPO3 14: Content > Counter): site-wide list of all Counter elements. The module
// does not depend on the selected page, so the page tree is not shown. Hidden automatically when
// pxe_hub hosts it.
return [
    'pxe_counter' => [
        'parent' => 'web',
        'position' => ['bottom'],
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'content-pxe-counter',
        'path' => '/module/web/pxe-counter',
        'labels' => 'LLL:EXT:pxe_counter/Resources/Private/Language/locallang_mod.xlf',
        'inheritNavigationComponentFromMainModule' => false,
        'routes' => [
            '_default' => ['target' => OverviewController::class . '::indexAction'],
        ],
    ],
];
