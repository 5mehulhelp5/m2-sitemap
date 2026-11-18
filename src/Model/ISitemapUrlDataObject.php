<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

/**
 * A data object of the PageMap of a page in the sitemap, for example the thumbnail of a product.
 *
 * The name without suffix "Interface" is kept, the sources of other modules use it.
 */
interface ISitemapUrlDataObject // phpcs:ignore Magento2.NamingConvention.InterfaceName
{
    /**
     * Returns the type of the data object, for example "thumbnail".
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Sets the type of the data object, for example "thumbnail".
     *
     * @param string $type
     *
     * @return void
     */
    public function setType(string $type): void;

    /**
     * Returns the attributes of the data object.
     *
     * @return ISitemapUrlDataObjectAttribute[]
     */
    public function getAttributes(): array;

    /**
     * Sets the attributes of the data object.
     *
     * @param ISitemapUrlDataObjectAttribute[] $attributes
     *
     * @return void
     */
    public function setAttributes(array $attributes): void;

    /**
     * Adds an attribute to the data object.
     *
     * @param ISitemapUrlDataObjectAttribute $attribute
     *
     * @return void
     */
    public function addAttribute(ISitemapUrlDataObjectAttribute $attribute): void;
}
