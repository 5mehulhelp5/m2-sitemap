<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Helper;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Instances;
use Infrangible\Core\Helper\Stores;
use Infrangible\Core\Helper\Url;
use Infrangible\Sitemap\Model\Source\ISource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use RuntimeException;

/**
 * Configuration of the sitemap and the values that all sources share: URLs, dates, change frequency and priority.
 */
class Data
{
    /** Default file name of the sitemap of a store. */
    public const DEFAULT_FILE_NAME = 'sitemap.xml';

    /** Change frequencies of the sitemap protocol. */
    public const CHANGE_FREQUENCIES = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

    /**
     * @param Stores $storeHelper
     * @param Instances $instanceHelper
     * @param Url $urlHelper
     * @param Variables $variables
     */
    public function __construct(
        protected Stores $storeHelper,
        protected Instances $instanceHelper,
        protected Url $urlHelper,
        protected Variables $variables
    ) {
    }

    /**
     * Tells whether the sitemap of the store is generated; never for the admin store.
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isEnabled(int $storeId): bool
    {
        return $storeId > 0 &&
            $this->storeHelper->getStoreConfigFlag('infrangible_sitemap/general/export', true, $storeId);
    }

    /**
     * Returns the file name of the sitemap of the store, relative to the document root of the shop.
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getFileName(int $storeId): string
    {
        $fileName = $this->variables->stringValueOrDefault(
            $this->storeHelper->getStoreConfig('infrangible_sitemap/general/file_name', null, false, $storeId)
        );

        $fileName = ltrim(trim($fileName), '/');

        return $fileName === '' ? self::DEFAULT_FILE_NAME : $fileName;
    }

    /**
     * Returns the sources of the store by name, as configured in infrangible_sitemap/source.
     *
     * @param int $storeId
     *
     * @return array<string, ISource>
     * @throws LocalizedException
     */
    public function getSources(int $storeId): array
    {
        $sourceConfig = $this->storeHelper->getStoreConfig('infrangible_sitemap/source', [], false, $storeId);

        $sources = [];

        foreach (is_array($sourceConfig) ? $sourceConfig : [] as $sourceName => $sourceClassName) {
            $sourceClassName = $this->variables->stringValueOrDefault($sourceClassName);

            if ($sourceClassName === '') {
                continue;
            }

            if (! class_exists($sourceClassName)) {
                throw new LocalizedException(__('Could not find source class: %1', $sourceClassName));
            }

            try {
                $source = $this->instanceHelper->getSharedInstance($sourceClassName);
            } catch (RuntimeException $exception) {
                throw new LocalizedException(
                    __('Could not load source class: %1 because: %2', $sourceClassName, $exception->getMessage())
                );
            }

            if (! $source instanceof ISource) {
                throw new LocalizedException(
                    __('Source class: %1 does not implement %2', $sourceClassName, ISource::class)
                );
            }

            $sources[ (string)$sourceName ] = $source;
        }

        return $sources;
    }

    /**
     * Returns the change frequency of a group of the configuration (product, category, cms, ...).
     *
     * The default value is returned if the configured one is no change frequency of the sitemap protocol.
     *
     * @param string $group
     * @param int $storeId
     * @param string $defaultValue
     *
     * @return string
     */
    public function getChangeFrequency(string $group, int $storeId, string $defaultValue): string
    {
        $changeFrequency = strtolower(
            $this->variables->stringValueOrDefault(
                $this->storeHelper->getStoreConfig(
                    sprintf('infrangible_sitemap/%s/changeFrequency', $group),
                    null,
                    false,
                    $storeId
                )
            )
        );

        return in_array($changeFrequency, self::CHANGE_FREQUENCIES, true) ? $changeFrequency : $defaultValue;
    }

    /**
     * Returns the priority of a group of the configuration (product, category, cms, ...) from 0.0 to 1.0.
     *
     * The default value is returned if the configured one is no such number.
     *
     * @param string $group
     * @param int $storeId
     * @param string $defaultValue
     *
     * @return string
     */
    public function getPriority(string $group, int $storeId, string $defaultValue): string
    {
        $priority = $this->storeHelper->getStoreConfig(
            sprintf('infrangible_sitemap/%s/priority', $group),
            null,
            false,
            $storeId
        );

        return is_numeric($priority) && $priority >= 0 && $priority <= 1 ?
            number_format((float)$priority, 1, '.', '') : $defaultValue;
    }

    /**
     * Returns a date of the database as date of the last change (Y-m-d), or an empty string if it is empty or invalid.
     *
     * @param mixed $date
     *
     * @return string
     */
    public function getLastModified(mixed $date): string
    {
        $date = $this->variables->stringValueOrDefault($date);

        $timestamp = $date === '' ? false : strtotime($date);

        return $timestamp === false ? '' : gmdate('Y-m-d', $timestamp);
    }

    /**
     * Returns the absolute URL of a path of the shop in the store, with HTTPS if the store uses it in the frontend.
     *
     * @param string $path
     * @param int $storeId
     *
     * @return string
     */
    public function getUrl(string $path, int $storeId): string
    {
        return $this->urlHelper->getUrl('', $this->isSecure($storeId), ['_direct' => ltrim($path, '/')], $storeId);
    }

    /**
     * Returns the base URL of the shop in the store, with HTTPS if the store uses it in the frontend.
     *
     * @param int $storeId
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBaseUrl(int $storeId): string
    {
        $store = $this->storeHelper->getStore($storeId);

        return $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, $this->isSecure($storeId));
    }

    /**
     * Tells whether the store uses HTTPS in the frontend.
     *
     * The URLs of the sitemap do not depend on the request that generates it (cron, console or admin).
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isSecure(int $storeId): bool
    {
        try {
            return $this->storeHelper->getStore($storeId)->isFrontUrlSecure();
        } catch (NoSuchEntityException) {
            return false;
        }
    }

    /**
     * Tells whether a value of the attribute "exclude_from_sitemap" excludes the entity.
     *
     * The export helper of m2-core returns values of attributes with options as array with the ID of the option.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isExcluded(mixed $value): bool
    {
        if (is_array($value)) {
            $value = $value[ 'id' ] ?? null;
        }

        return is_scalar($value) && (int)$value === 1;
    }
}
