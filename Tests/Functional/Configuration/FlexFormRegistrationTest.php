<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The content element must resolve to its own FlexForm data structure, not to the core's
 * placeholder DS ("The Title:"), and use the core header fields like other content elements.
 */
final class FlexFormRegistrationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = ['pixelegg/pxe-counter'];

    #[Test]
    public function contentElementResolvesToItsOwnFlexFormDataStructure(): void
    {
        $flexFormTools = $this->get(FlexFormTools::class);
        // TYPO3 14 resolves the data structure through the TCA schema; TYPO3 13 ignores the argument.
        $schema = $this->get(TcaSchemaFactory::class)->get('tt_content');
        // list_type is read by the TYPO3 13 ds_pointerField "list_type,CType".
        $row = ['uid' => 1, 'pid' => 1, 'CType' => 'pxe_counter', 'list_type' => ''];

        $identifier = $flexFormTools->getDataStructureIdentifier(
            $GLOBALS['TCA']['tt_content']['columns']['pi_flexform'],
            'tt_content',
            'pi_flexform',
            $row,
            $schema,
        );
        $dataStructure = $flexFormTools->parseDataStructureByIdentifier($identifier, $schema);

        $fields = $dataStructure['sheets']['main']['ROOT']['el'] ?? [];
        foreach (['counter_first', 'counter_last', 'prefix', 'suffix'] as $field) {
            self::assertArrayHasKey($field, $fields, 'FlexForm field "' . $field . '" missing');
        }
        // Start and end value accept decimals (4.5); with the integer default DataHandler stores 4.
        foreach (['counter_first', 'counter_last'] as $field) {
            self::assertSame('number', $fields[$field]['config']['type'] ?? null, $field);
            self::assertSame('decimal', $fields[$field]['config']['format'] ?? null, $field);
        }
    }

    #[Test]
    public function contentElementUsesTheCoreHeadersPalette(): void
    {
        $showitem = (string)($GLOBALS['TCA']['tt_content']['types']['pxe_counter']['showitem'] ?? '');

        // The header is rendered in the frontend, so it must not be labelled "not visible".
        self::assertStringContainsString('--palette--;;headers,', $showitem);
        self::assertStringNotContainsString('html_formlabel', $showitem);
        self::assertStringContainsString('pi_flexform', $showitem);
    }
}
