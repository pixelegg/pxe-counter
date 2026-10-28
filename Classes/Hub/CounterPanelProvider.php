<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Hub;

use Pixelegg\PxeCounter\Service\CounterUsageService;
use Pixelegg\PxeHub\Panel\PanelAction;
use Pixelegg\PxeHub\Panel\PanelProvider;
use Pixelegg\PxeHub\Panel\PanelStatus;
use TYPO3\CMS\Backend\Routing\UriBuilder;

/**
 * Gives Counter a card in pxe_hub (icon + usage metric + link to its module).
 *
 * Registered conditionally (only when pxe_hub is installed) via Configuration/Services.php,
 * so pxe_counter does not depend on pxe_hub.
 */
final readonly class CounterPanelProvider implements PanelProvider
{
    public function __construct(
        private CounterUsageService $usageService,
        private UriBuilder $uriBuilder,
    ) {}

    public function getExtensionKey(): string
    {
        return 'pxe_counter';
    }

    public function getLabel(): string
    {
        return 'Counter';
    }

    public function getIcon(): string
    {
        return 'content-pxe-counter';
    }

    public function getModuleLink(): ?string
    {
        return (string)$this->uriBuilder->buildUriFromRoute('pxe_counter');
    }

    public function getStatus(): PanelStatus
    {
        $count = count($this->usageService->getUsages());
        return $count === 0 ? PanelStatus::unknown('Not used yet') : PanelStatus::ok($count . ' elements');
    }

    /** @return array<int,\Pixelegg\PxeHub\Panel\Metric> */
    public function getMetrics(): array
    {
        // Count already shown inline in the status text ("N elements"), so no separate chip.
        return [];
    }

    /** @return PanelAction[] */
    public function getActions(): array
    {
        return [
            new PanelAction('Open module', (string)$this->uriBuilder->buildUriFromRoute('pxe_counter')),
        ];
    }
}
