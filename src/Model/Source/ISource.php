<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model\Source;

use Exception;
use Infrangible\Sitemap\Model\ISitemapUrl;

/**
 * A source of sitemap URLs, configured in infrangible_sitemap/source/<name> (config.xml) with its class name.
 *
 * The task reads the data of a store with getStoreData() and transforms it into sitemap URLs with
 * transformStoreData(); an exception of a source fails the run.
 *
 * The name without suffix "Interface" is kept, the sources of other modules use it.
 */
interface ISource // phpcs:ignore Magento2.NamingConvention.InterfaceName
{
    /**
     * Returns the data of the store, for example the products; an empty array if the source is not exported.
     *
     * @param int $storeId
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     * @throws Exception
     */
    public function getStoreData(int $storeId): array;

    /**
     * Transforms the data of the store into sitemap URLs.
     *
     * @param int $storeId
     * @param array $storeData
     *
     * @phpstan-param array<int|string, mixed> $storeData
     *
     * @return ISitemapUrl[]
     * @throws Exception
     */
    public function transformStoreData(int $storeId, array $storeData): array;
}
