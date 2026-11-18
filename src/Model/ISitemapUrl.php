<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

/**
 * A URL of the sitemap with its date of the last change, change frequency, priority, images and data objects (PageMap).
 * Sources (Infrangible\Sitemap\Model\Source\ISource) create them with Infrangible\Sitemap\Model\SitemapUrlFactory.
 *
 * The name without suffix "Interface" is kept, the sources of other modules use it.
 */
interface ISitemapUrl // phpcs:ignore Magento2.NamingConvention.InterfaceName
{
    /**
     * Returns the absolute URL.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Sets the absolute URL.
     *
     * @param string $url
     *
     * @return void
     */
    public function setUrl(string $url): void;

    /**
     * Returns the date of the last change (Y-m-d), or an empty string if it is unknown.
     *
     * @return string
     */
    public function getLastModified(): string;

    /**
     * Sets the date of the last change (Y-m-d).
     *
     * @param string $lastModified
     *
     * @return void
     */
    public function setLastModified(string $lastModified): void;

    /**
     * Returns the change frequency (always, hourly, daily, weekly, monthly, yearly or never).
     *
     * @return string
     */
    public function getChangeFrequency(): string;

    /**
     * Sets the change frequency (always, hourly, daily, weekly, monthly, yearly or never).
     *
     * @param string $changeFrequency
     *
     * @return void
     */
    public function setChangeFrequency(string $changeFrequency): void;

    /**
     * Returns the priority (0.0 to 1.0).
     *
     * @return string
     */
    public function getPriority(): string;

    /**
     * Sets the priority (0.0 to 1.0).
     *
     * @param string $priority
     *
     * @return void
     */
    public function setPriority(string $priority): void;

    /**
     * Returns the images of the page.
     *
     * @return ISitemapUrlImage[]
     */
    public function getImages(): array;

    /**
     * Sets the images of the page.
     *
     * @param ISitemapUrlImage[] $images
     *
     * @return void
     */
    public function setImages(array $images): void;

    /**
     * Adds an image of the page.
     *
     * @param ISitemapUrlImage $image
     *
     * @return void
     */
    public function addImage(ISitemapUrlImage $image): void;

    /**
     * Returns the data objects of the PageMap of the page.
     *
     * @return ISitemapUrlDataObject[]
     */
    public function getDataObjects(): array;

    /**
     * Sets the data objects of the PageMap of the page.
     *
     * @param ISitemapUrlDataObject[] $dataObjects
     *
     * @return void
     */
    public function setDataObjects(array $dataObjects): void;

    /**
     * Adds a data object to the PageMap of the page.
     *
     * @param ISitemapUrlDataObject $dataObject
     *
     * @return void
     */
    public function addDataObject(ISitemapUrlDataObject $dataObject): void;

    /**
     * Tells whether the URL is set, URLs without are not written into the sitemap.
     *
     * @return bool
     */
    public function isValid(): bool;
}
