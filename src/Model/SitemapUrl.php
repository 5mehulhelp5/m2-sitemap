<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

/**
 * A URL of the sitemap.
 */
class SitemapUrl implements ISitemapUrl
{
    /** @var string */
    private string $url = '';

    /** @var string */
    private string $lastModified = '';

    /** @var string */
    private string $changeFrequency = '';

    /** @var string */
    private string $priority = '';

    /** @var ISitemapUrlImage[] */
    private array $images = [];

    /** @var ISitemapUrlDataObject[] */
    private array $dataObjects = [];

    /**
     * Returns the absolute URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Sets the absolute URL.
     *
     * @param string $url
     *
     * @return void
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Returns the date of the last change (Y-m-d), or an empty string if it is unknown.
     *
     * @return string
     */
    public function getLastModified(): string
    {
        return $this->lastModified;
    }

    /**
     * Sets the date of the last change (Y-m-d).
     *
     * @param string $lastModified
     *
     * @return void
     */
    public function setLastModified(string $lastModified): void
    {
        $this->lastModified = $lastModified;
    }

    /**
     * Returns the change frequency (always, hourly, daily, weekly, monthly, yearly or never).
     *
     * @return string
     */
    public function getChangeFrequency(): string
    {
        return $this->changeFrequency;
    }

    /**
     * Sets the change frequency (always, hourly, daily, weekly, monthly, yearly or never).
     *
     * @param string $changeFrequency
     *
     * @return void
     */
    public function setChangeFrequency(string $changeFrequency): void
    {
        $this->changeFrequency = $changeFrequency;
    }

    /**
     * Returns the priority (0.0 to 1.0).
     *
     * @return string
     */
    public function getPriority(): string
    {
        return $this->priority;
    }

    /**
     * Sets the priority (0.0 to 1.0).
     *
     * @param string $priority
     *
     * @return void
     */
    public function setPriority(string $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Returns the images of the page.
     *
     * @return ISitemapUrlImage[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Sets the images of the page.
     *
     * @param ISitemapUrlImage[] $images
     *
     * @return void
     */
    public function setImages(array $images): void
    {
        $this->images = $images;
    }

    /**
     * Adds an image of the page.
     *
     * @param ISitemapUrlImage $image
     *
     * @return void
     */
    public function addImage(ISitemapUrlImage $image): void
    {
        $this->images[] = $image;
    }

    /**
     * Returns the data objects of the PageMap of the page.
     *
     * @return ISitemapUrlDataObject[]
     */
    public function getDataObjects(): array
    {
        return $this->dataObjects;
    }

    /**
     * Sets the data objects of the PageMap of the page.
     *
     * @param ISitemapUrlDataObject[] $dataObjects
     *
     * @return void
     */
    public function setDataObjects(array $dataObjects): void
    {
        $this->dataObjects = $dataObjects;
    }

    /**
     * Adds a data object to the PageMap of the page.
     *
     * @param ISitemapUrlDataObject $dataObject
     *
     * @return void
     */
    public function addDataObject(ISitemapUrlDataObject $dataObject): void
    {
        $this->dataObjects[] = $dataObject;
    }

    /**
     * Tells whether the URL is set, URLs without are not written into the sitemap.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->url !== '';
    }
}
