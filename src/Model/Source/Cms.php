<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model\Source;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Stores;
use Infrangible\Sitemap\Helper\Data;
use Infrangible\Sitemap\Model\ISitemapUrl;
use Infrangible\Sitemap\Model\SitemapUrlFactory;
use Magento\Cms\Model\Page;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * The active CMS pages of the store, the home page with the base URL of the store and priority 1.0, without the page
 * of the 404 error.
 *
 * Event: infrangible_sitemap_transform_cms (sitemap_url, cms_page) for every page; URLs without URL are not written.
 */
class Cms implements ISource
{
    /** Configuration of the home page and the 404 page: the identifier, or "identifier|page ID" if it is not unique. */
    public const XML_PATH_HOME_PAGE = 'web/default/cms_home_page';
    public const XML_PATH_NO_ROUTE_PAGE = 'web/default/cms_no_route';

    /**
     * @param Variables $variables
     * @param Stores $storeHelper
     * @param Data $sitemapHelper
     * @param \Infrangible\Core\Helper\Cms $cmsHelper
     * @param LoggerInterface $logging
     * @param SitemapUrlFactory $sitemapUrlFactory
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        protected Variables $variables,
        protected Stores $storeHelper,
        protected Data $sitemapHelper,
        protected \Infrangible\Core\Helper\Cms $cmsHelper,
        protected LoggerInterface $logging,
        protected SitemapUrlFactory $sitemapUrlFactory,
        protected ManagerInterface $eventManager
    ) {
    }

    /**
     * Returns the active CMS pages of the store that are not excluded from the sitemap.
     *
     * @param int $storeId
     *
     * @return Page[]
     */
    public function getStoreData(int $storeId): array
    {
        if (! $this->storeHelper->getStoreConfigFlag('infrangible_sitemap/cms/export', true, $storeId)) {
            return [];
        }

        $collection = $this->cmsHelper->getCmsPageCollection();
        $collection->addStoreFilter($storeId);
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToFilter('exclude_from_sitemap', 0);

        $pages = [];

        foreach ($collection->getItems() as $page) {
            if ($page instanceof Page) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /**
     * Transforms the CMS pages into sitemap URLs.
     *
     * @param int $storeId
     * @param array $storeData
     *
     * @phpstan-param array<int|string, mixed> $storeData
     *
     * @return ISitemapUrl[]
     * @throws NoSuchEntityException
     */
    public function transformStoreData(int $storeId, array $storeData): array
    {
        $changeFrequency = $this->sitemapHelper->getChangeFrequency('cms', $storeId, 'yearly');
        $priority = $this->sitemapHelper->getPriority('cms', $storeId, '0.5');

        $urls = [];

        foreach ($storeData as $cmsPage) {
            if (! $cmsPage instanceof Page) {
                continue;
            }

            $sitemapUrl = $this->createSitemapUrlModel();

            $cmsPageData = $this->getCmsPageData($cmsPage, $storeId, $changeFrequency, $priority);

            if (count($cmsPageData) > 0) {
                $this->populateSitemapUrl($sitemapUrl, $cmsPageData);
            }

            $this->eventManager->dispatch(
                'infrangible_sitemap_transform_cms',
                ['sitemap_url' => $sitemapUrl, 'cms_page' => $cmsPage]
            );

            if ($sitemapUrl->isValid()) {
                $urls[] = $sitemapUrl;
            }
        }

        return $urls;
    }

    /**
     * Returns URL, date of the last change, change frequency and priority of the page; none for the 404 page.
     *
     * @param Page $cmsPage
     * @param int $storeId
     * @param string $changeFrequency
     * @param string $priority
     *
     * @return string[]
     * @throws NoSuchEntityException
     */
    protected function getCmsPageData(Page $cmsPage, int $storeId, string $changeFrequency, string $priority): array
    {
        if ($this->isConfiguredPage($cmsPage, self::XML_PATH_NO_ROUTE_PAGE, $storeId)) {
            return [];
        }

        $lastModified = $this->sitemapHelper->getLastModified($cmsPage->getUpdateTime());

        if ($this->isConfiguredPage($cmsPage, self::XML_PATH_HOME_PAGE, $storeId)) {
            $this->logging->debug(
                sprintf('Exporting CMS home page with title: %s in store with ID: %d', $cmsPage->getTitle(), $storeId)
            );

            return [$this->sitemapHelper->getBaseUrl($storeId), $lastModified, $changeFrequency, '1.0'];
        }

        $this->logging->debug(
            sprintf(
                'Exporting CMS page with identifier: %s, title: %s in store with ID: %d',
                $cmsPage->getIdentifier(),
                $cmsPage->getTitle(),
                $storeId
            )
        );

        return [
            $this->sitemapHelper->getUrl((string)$cmsPage->getIdentifier(), $storeId),
            $lastModified,
            $changeFrequency,
            $priority
        ];
    }

    /**
     * Tells whether the page is the one of the configuration path in the store (home page or 404 page).
     *
     * The value is the identifier, or "identifier|page ID" if the identifier is not unique.
     *
     * @param Page $cmsPage
     * @param string $configPath
     * @param int $storeId
     *
     * @return bool
     */
    protected function isConfiguredPage(Page $cmsPage, string $configPath, int $storeId): bool
    {
        $value = $this->variables->stringValueOrDefault(
            $this->storeHelper->getStoreConfig($configPath, null, false, $storeId)
        );

        if ($value === '') {
            return false;
        }

        $parts = explode('|', $value, 2);

        if ($parts[ 0 ] !== $cmsPage->getIdentifier()) {
            return false;
        }

        return ! isset($parts[ 1 ]) || $parts[ 1 ] === (string)$cmsPage->getId();
    }

    /**
     * Returns a new sitemap URL.
     *
     * @return ISitemapUrl
     */
    public function createSitemapUrlModel(): ISitemapUrl
    {
        return $this->sitemapUrlFactory->create();
    }

    /**
     * Sets URL, date of the last change, change frequency and priority.
     *
     * @param ISitemapUrl $sitemapUrl
     * @param string[] $cmsPageData URL, date of the last change, change frequency and priority
     *
     * @return void
     */
    public function populateSitemapUrl(ISitemapUrl $sitemapUrl, array $cmsPageData): void
    {
        [$url, $lastModified, $changeFrequency, $priority] = $cmsPageData;

        $sitemapUrl->setUrl($url);
        $sitemapUrl->setLastModified($lastModified);
        $sitemapUrl->setChangeFrequency($changeFrequency);
        $sitemapUrl->setPriority($priority);
    }
}
