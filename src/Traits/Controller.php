<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Traits;

use Infrangible\Sitemap\Model\Collection;

/**
 * Settings of the list of sitemap files in the admin.
 */
trait Controller
{
    /**
     * Returns the ACL resource of the list.
     *
     * @return string
     */
    protected function getResourceKey(): string
    {
        return 'Infrangible_Sitemap::infrangible_sitemap';
    }

    /**
     * Returns the name of the module.
     *
     * @return string
     */
    protected function getModuleKey(): string
    {
        return 'Infrangible_Sitemap';
    }

    /**
     * Returns the name of the listed objects.
     *
     * @return string
     */
    protected function getObjectName(): string
    {
        return 'Sitemap';
    }

    /**
     * Returns the field that identifies a sitemap: its file name.
     *
     * @return string|null
     */
    protected function getObjectField(): ?string
    {
        return 'filename';
    }

    /**
     * Returns the collection of the sitemap files.
     *
     * @return string|null
     */
    protected function getCollectionClass(): ?string
    {
        return Collection::class;
    }

    /**
     * Returns the menu item of the list.
     *
     * @return string
     */
    protected function getMenuKey(): string
    {
        return 'Infrangible_Sitemap::infrangible_sitemap_manage';
    }

    /**
     * Returns the title of the list.
     *
     * @return string
     */
    protected function getTitle(): string
    {
        return __('Sitemap')->render();
    }

    /**
     * Sitemaps are not added in the admin, the configuration defines them.
     *
     * @return bool
     */
    protected function allowAdd(): bool
    {
        return false;
    }

    /**
     * Sitemaps are not edited in the admin.
     *
     * @return bool
     */
    protected function allowEdit(): bool
    {
        return false;
    }

    /**
     * Sitemaps have no view page, the list links the files.
     *
     * @return bool
     */
    protected function allowView(): bool
    {
        return false;
    }

    /**
     * Sitemaps are not deleted in the admin.
     *
     * @return bool
     */
    protected function allowDelete(): bool
    {
        return false;
    }

    /**
     * The list has no filters.
     *
     * @return bool
     */
    protected function showFiltersButton(): bool
    {
        return false;
    }

    /**
     * The list has no column selection.
     *
     * @return bool
     */
    protected function showColumnsButton(): bool
    {
        return false;
    }
}
