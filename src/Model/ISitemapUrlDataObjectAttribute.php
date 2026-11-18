<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

/**
 * An attribute of a data object of the PageMap, for example the name of a product.
 *
 * The name without suffix "Interface" is kept, the sources of other modules use it.
 */
interface ISitemapUrlDataObjectAttribute // phpcs:ignore Magento2.NamingConvention.InterfaceName
{
    /**
     * Returns the name of the attribute.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Sets the name of the attribute.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void;

    /**
     * Returns the value of the attribute.
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Sets the value of the attribute.
     *
     * @param string $value
     *
     * @return void
     */
    public function setValue(string $value): void;
}
