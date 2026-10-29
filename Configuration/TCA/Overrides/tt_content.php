<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$ll = 'LLL:EXT:pxe_counter/Resources/Private/Language/Backend/locallang.xlf:';

$flexForm = 'FILE:EXT:pxe_counter/Configuration/FlexForms/ContentElements/Counter.xml';
$item = [
    $ll . 'pxe_counter_title',
    'pxe_counter',
    'content-pxe-counter',
    'default',
    $ll . 'pxe_counter_description',
];

// Register the Counter content element as a CType and bind its FlexForm.
if ((new Typo3Version())->getMajorVersion() >= 14) {
    // TYPO3 14: addPlugin(item, flexForm) stores the data structure in the type's columnsOverrides
    // (addPiFlexFormValue() is deprecated and removed in v15).
    ExtensionManagementUtility::addPlugin($item, $flexForm);
} else {
    // TYPO3 13.4: the data structure is looked up via ds_pointerField "list_type,CType";
    // a CType-based element has an empty list_type, hence the "*" key ("*,pxe_counter").
    ExtensionManagementUtility::addPlugin($item, 'CType', 'pxe_counter');
    ExtensionManagementUtility::addPiFlexFormValue('*', $flexForm, 'pxe_counter');
}

// Backend type icon for the CType.
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['pxe_counter'] = 'content-pxe-counter';

// Field layout for the CType. The header is rendered by the fluid_styled_content "Default" layout,
// so the element uses the core "headers" palette (header, layout, position, date, link, subheader)
// like the core content elements.
$GLOBALS['TCA']['tt_content']['types']['pxe_counter']['showitem']
    = '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,'
    . '--palette--;;general,'
    . '--palette--;;headers,'
    . 'pi_flexform,'
    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,'
    . '--palette--;;frames,--palette--;;appearanceLinks,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,--palette--;;language,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,--palette--;;hidden,--palette--;;access,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,categories,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,rowDescription,'
    . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,';
