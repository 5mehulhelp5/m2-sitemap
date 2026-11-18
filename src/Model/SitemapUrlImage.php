<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

/**
 * An image of a page in the sitemap.
 */
class SitemapUrlImage implements ISitemapUrlImage
{
    /** @var string */
    private string $url = '';

    /** @var string */
    private string $title = '';

    /** @var string */
    private string $caption = '';

    /**
     * Returns the absolute URL of the image.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Sets the absolute URL of the image.
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
     * Returns the title of the image.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title of the image.
     *
     * @param string $title
     *
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Returns the caption of the image.
     *
     * @return string
     */
    public function getCaption(): string
    {
        return $this->caption;
    }

    /**
     * Sets the caption of the image.
     *
     * @param string $caption
     *
     * @return void
     */
    public function setCaption(string $caption): void
    {
        $this->caption = $caption;
    }
}
