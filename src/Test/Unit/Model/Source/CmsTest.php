<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Test\Unit\Model\Source;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Cms as CmsHelper;
use Infrangible\Core\Helper\Stores;
use Infrangible\Sitemap\Helper\Data;
use Infrangible\Sitemap\Model\SitemapUrl;
use Infrangible\Sitemap\Model\SitemapUrlFactory;
use Infrangible\Sitemap\Model\Source\Cms;
use Magento\Cms\Model\Page;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests the URLs of CMS pages: the home page of the store with its base URL, no URL for the 404 page.
 */
class CmsTest extends TestCase
{
    /**
     * Returns the source with the home page and the 404 page of the configuration of store 1.
     *
     * @param string $homePage
     *
     * @return Cms
     */
    private function createSource(string $homePage): Cms
    {
        $values = ['web/default/cms_home_page' => $homePage, 'web/default/cms_no_route' => 'no-route'];

        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStoreConfig')->willReturnCallback(
            fn(string $path, mixed $defaultValue = null, bool $isFlag = false, ?int $storeId = null): mixed =>
                $storeId === 1 ? ($values[ $path ] ?? $defaultValue) : $defaultValue
        );

        $sitemapHelper = $this->createStub(Data::class);
        $sitemapHelper->method('getBaseUrl')->willReturn('https://shop.test/');
        $sitemapHelper->method('getUrl')->willReturnCallback(fn(string $path): string => 'https://shop.test/' . $path);
        $sitemapHelper->method('getLastModified')->willReturn('2026-11-01');
        $sitemapHelper->method('getChangeFrequency')->willReturn('yearly');
        $sitemapHelper->method('getPriority')->willReturn('0.5');

        $sitemapUrlFactory = $this->createStub(SitemapUrlFactory::class);
        $sitemapUrlFactory->method('create')->willReturnCallback(fn(): SitemapUrl => new SitemapUrl());

        return new Cms(
            new Variables(),
            $storeHelper,
            $sitemapHelper,
            $this->createStub(CmsHelper::class),
            new NullLogger(),
            $sitemapUrlFactory,
            $this->createStub(ManagerInterface::class)
        );
    }

    /**
     * Returns a CMS page.
     *
     * @param int $id
     * @param string $identifier
     *
     * @return Page
     */
    private function createPage(int $id, string $identifier): Page
    {
        $page = $this->createStub(Page::class);
        $page->method('getId')->willReturn((string)$id);
        $page->method('getIdentifier')->willReturn($identifier);
        $page->method('getTitle')->willReturn($identifier);
        $page->method('getUpdateTime')->willReturn('2026-11-01 10:00:00');

        return $page;
    }

    /**
     * Returns the URL and priority of every written URL.
     *
     * @param Cms $source
     * @param Page[] $pages
     *
     * @return array<string, string>
     * @throws NoSuchEntityException
     */
    private function getUrls(Cms $source, array $pages): array
    {
        $urls = [];

        foreach ($source->transformStoreData(1, $pages) as $sitemapUrl) {
            $urls[ $sitemapUrl->getUrl() ] = $sitemapUrl->getPriority();
        }

        return $urls;
    }

    /**
     * Tests that the home page has the base URL and the 404 page no URL.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testHomeAndNoRoutePage(): void
    {
        $urls = $this->getUrls(
            $this->createSource('home'),
            [$this->createPage(1, 'home'), $this->createPage(2, 'no-route'), $this->createPage(3, 'about-us')]
        );

        $this->assertSame(['https://shop.test/' => '1.0', 'https://shop.test/about-us' => '0.5'], $urls);
    }

    /**
     * Tests the home page of the configuration with "identifier|page ID", which Magento stores if the identifier is
     * not unique; only the page with the ID is the home page.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testHomePageWithId(): void
    {
        $urls = $this->getUrls(
            $this->createSource('start|5'),
            [$this->createPage(4, 'start'), $this->createPage(5, 'start')]
        );

        $this->assertSame(['https://shop.test/start' => '0.5', 'https://shop.test/' => '1.0'], $urls);
    }
}
