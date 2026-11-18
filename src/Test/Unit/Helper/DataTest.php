<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Test\Unit\Helper;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Stores;
use Infrangible\Core\Helper\Url;
use Infrangible\Sitemap\Helper\Data;
use PHPUnit\Framework\TestCase;

/**
 * Tests the values that all sources share: change frequency, priority, dates and the exclusion of entities.
 */
class DataTest extends TestCase
{
    /**
     * Returns the helper with the configuration values.
     *
     * @param array $values
     * @phpstan-param array<string, mixed> $values
     *
     * @return Data
     */
    private function createHelper(array $values = []): Data
    {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStoreConfig')->willReturnCallback(
            fn(string $path, mixed $defaultValue = null): mixed => $values[ $path ] ?? $defaultValue
        );
        $storeHelper->method('getStoreConfigFlag')->willReturnCallback(
            fn(string $path, bool $defaultValue = false): bool => (bool)($values[ $path ] ?? $defaultValue)
        );

        return new Data(
            $storeHelper,
            $this->createStub(Instances::class),
            $this->createStub(Url::class),
            new Variables()
        );
    }

    /**
     * Tests that only change frequencies of the sitemap protocol are used.
     *
     * @return void
     */
    public function testChangeFrequency(): void
    {
        $helper = $this->createHelper(
            [
                'infrangible_sitemap/product/changeFrequency' => 'Weekly',
                'infrangible_sitemap/cms/changeFrequency'     => 'often'
            ]
        );

        $this->assertSame('weekly', $helper->getChangeFrequency('product', 1, 'daily'));
        $this->assertSame('yearly', $helper->getChangeFrequency('cms', 1, 'yearly'));
        $this->assertSame('monthly', $helper->getChangeFrequency('category', 1, 'monthly'));
    }

    /**
     * Tests that the priority is a number from 0.0 to 1.0.
     *
     * @return void
     */
    public function testPriority(): void
    {
        $helper = $this->createHelper(
            [
                'infrangible_sitemap/product/priority'  => '0.75',
                'infrangible_sitemap/category/priority' => '1',
                'infrangible_sitemap/cms/priority'      => '2'
            ]
        );

        $this->assertSame('0.8', $helper->getPriority('product', 1, '1.0'));
        $this->assertSame('1.0', $helper->getPriority('category', 1, '0.5'));
        $this->assertSame('0.5', $helper->getPriority('cms', 1, '0.5'));
    }

    /**
     * Tests the date of the last change; before, an entity without date got the date of 1970.
     *
     * @return void
     */
    public function testLastModified(): void
    {
        $helper = $this->createHelper();

        $this->assertSame('2026-11-17', $helper->getLastModified('2026-11-17 23:15:00'));
        $this->assertSame('', $helper->getLastModified(null));
        $this->assertSame('', $helper->getLastModified('not a date'));
    }

    /**
     * Tests the values of the attribute exclude_from_sitemap, as option or as plain value.
     *
     * @return void
     */
    public function testExcluded(): void
    {
        $helper = $this->createHelper();

        $this->assertTrue($helper->isExcluded(['id' => '1', 'value' => 'Yes']));
        $this->assertTrue($helper->isExcluded(1));
        $this->assertFalse($helper->isExcluded(['id' => '0', 'value' => 'No']));
        $this->assertFalse($helper->isExcluded(null));
    }

    /**
     * Tests the file name of the sitemap of a store.
     *
     * @return void
     */
    public function testFileName(): void
    {
        $this->assertSame('sitemap.xml', $this->createHelper()->getFileName(1));
        $this->assertSame(
            'maps/de.xml',
            $this->createHelper(['infrangible_sitemap/general/file_name' => ' /maps/de.xml '])->getFileName(1)
        );
    }

    /**
     * Tests that the admin store has no sitemap.
     *
     * @return void
     */
    public function testEnabled(): void
    {
        $helper = $this->createHelper(['infrangible_sitemap/general/export' => '1']);

        $this->assertTrue($helper->isEnabled(1));
        $this->assertFalse($helper->isEnabled(0));
        $this->assertFalse(
            $this->createHelper(['infrangible_sitemap/general/export' => '0'])->isEnabled(1)
        );
    }
}
