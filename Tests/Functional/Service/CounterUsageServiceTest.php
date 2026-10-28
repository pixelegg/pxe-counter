<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use Pixelegg\PxeCounter\Service\CounterUsageService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CounterUsageServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = ['pixelegg/pxe-counter'];

    private function subject(): CounterUsageService
    {
        return $this->get(CounterUsageService::class);
    }

    private function importFixture(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/counter_content.csv');
    }

    #[Test]
    public function getUsagesReturnsAllNonDeletedCounters(): void
    {
        $this->importFixture();
        $usages = $this->subject()->getUsages();

        // 3 counters survive (uid 10, 11, 12); 13 wrong CType, 14 deleted.
        self::assertCount(3, $usages);
        $uids = array_column($usages, 'contentUid');
        self::assertContains(10, $uids);
        self::assertContains(11, $uids);
        self::assertContains(12, $uids);
        self::assertNotContains(13, $uids);
        self::assertNotContains(14, $uids);
    }

    #[Test]
    public function getUsagesMapsFieldsAndDetectsIncomplete(): void
    {
        $this->importFixture();
        $byUid = [];
        foreach ($this->subject()->getUsages() as $u) {
            $byUid[$u['contentUid']] = $u;
        }

        self::assertSame('Happy clients', $byUid[10]['header']);
        self::assertSame(1200.0, $byUid[10]['to']);
        self::assertFalse($byUid[10]['incomplete']);
        self::assertSame('Home', $byUid[10]['pageTitle']);

        // from === to → incomplete (won't animate)
        self::assertTrue($byUid[11]['incomplete']);

        // hidden flag mapped
        self::assertTrue($byUid[12]['hidden']);
    }

    #[Test]
    public function getStatisticsAggregatesCorrectly(): void
    {
        $this->importFixture();
        $usages = $this->subject()->getUsages();
        $stats = $this->subject()->getStatistics($usages);

        self::assertSame(3, $stats['total']);
        self::assertSame(2, $stats['pages']);     // pages 1 and 2
        self::assertSame(1, $stats['hidden']);    // uid 12
        self::assertSame(1, $stats['incomplete']); // uid 11
    }
}
