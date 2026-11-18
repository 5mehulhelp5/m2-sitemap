<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

/**
 * An image of a page in the sitemap (Google image sitemap extension).
 *
 * The name without suffix "Interface" is kept, the sources of other modules use it.
 */
interface ISitemapUrlImage // phpcs:ignore Magento2.NamingConvention.InterfaceName
{
    /**
     * Returns the absolute URL of the image.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Sets the absolute URL of the image.
     *
     * @param string $url
     *
     * @return void
     */
    public function setUrl(string $url): void;

    /**
     * Returns the title of the image.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Sets the title of the image.
     *
     * @param string $title
     *
     * @return void
     */
    public function setTitle(string $title): void;

    /**
     * Returns the caption of the image.
     *
     * @return string
     */
    public function getCaption(): string;

    /**
     * Sets the caption of the image.
     *
     * @param string $caption
     *
     * @return void
     */
    public function setCaption(string $caption): void;
}
