<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

/**
 * A data object of the PageMap of a page in the sitemap.
 */
class SitemapUrlDataObject implements ISitemapUrlDataObject
{
    /** @var string */
    private string $type = '';

    /** @var ISitemapUrlDataObjectAttribute[] */
    private array $attributes = [];

    /**
     * Returns the type of the data object, for example "thumbnail".
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the type of the data object, for example "thumbnail".
     *
     * @param string $type
     *
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Returns the attributes of the data object.
     *
     * @return ISitemapUrlDataObjectAttribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Sets the attributes of the data object.
     *
     * @param ISitemapUrlDataObjectAttribute[] $attributes
     *
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Adds an attribute to the data object.
     *
     * @param ISitemapUrlDataObjectAttribute $attribute
     *
     * @return void
     */
    public function addAttribute(ISitemapUrlDataObjectAttribute $attribute): void
    {
        $this->attributes[] = $attribute;
    }
}
