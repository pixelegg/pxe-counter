<?php

declare(strict_types=1);

use Pixelegg\PxeCounter\Hub\CounterPanelProvider;
use Pixelegg\PxeHub\Panel\PanelProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    // Only register the hub panel when pxe_hub (and thus its interface) is installed.
    if (!interface_exists(PanelProvider::class)) {
        return;
    }

    $configurator->services()
        ->set(CounterPanelProvider::class)
        ->autowire()
        ->autoconfigure()
        ->tag('pxe.hub.panel');
};
