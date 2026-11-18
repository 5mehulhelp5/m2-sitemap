<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

/**
 * An attribute of a data object of the PageMap.
 */
class SitemapUrlDataObjectAttribute implements ISitemapUrlDataObjectAttribute
{
    /** @var string */
    private string $name = '';

    /** @var string */
    private string $value = '';

    /**
     * Returns the name of the attribute.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the attribute.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns the value of the attribute.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the value of the attribute.
     *
     * @param string $value
     *
     * @return void
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
