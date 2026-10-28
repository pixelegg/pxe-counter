<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// Classic installation path (sys_template > Include static). Do not combine with the site set
// "pixelegg/counter" - both load the same setup; pick one.
ExtensionManagementUtility::addStaticFile(
    'pxe_counter',
    'Configuration/TypoScript',
    'Counter (animated number)'
);
