<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedUserTsConfigEvent;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * When pxe_hub is present it hosts this extension's dashboard, so hide the standalone
 * top-level backend module from the menu (the hub still finds & links it).
 *
 * This used to be an `ExtensionManagementUtility::addUserTSConfig()` call in
 * `ext_localconf.php`. That method was removed in TYPO3 14, and it is deliberately *not*
 * replaced by a static `Configuration/user.tsconfig`: the `isLoaded('pxe_hub')` condition
 * is the whole point. A static file would also hide the module in installations that use
 * this extension *without* the hub, which would silently change behaviour there. The
 * event listener is the v14 way to add user TSconfig conditionally.
 */
#[AsEventListener(identifier: 'pxe_counter/hide-standalone-module-when-hub-present')]
final class HideStandaloneModuleWhenHubPresent
{
    public function __invoke(BeforeLoadedUserTsConfigEvent $event): void
    {
        if (!ExtensionManagementUtility::isLoaded('pxe_hub')) {
            return;
        }

        $event->addTsConfig('options.hideModules := addToList(pxe_counter)');
    }
}
