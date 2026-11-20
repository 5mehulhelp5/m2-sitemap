<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Test\Unit\Model;

use Exception;
use Infrangible\Core\Helper\Files;
use Infrangible\Core\Helper\Xml;
use Infrangible\Sitemap\Model\SitemapUrl;
use Infrangible\Sitemap\Model\SitemapUrlImage;
use Infrangible\Sitemap\Model\Writer;
use Magento\Framework\App\Filesystem\DirectoryList;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests the sitemap files: one entry per URL, values that are not set are left out, and sitemaps with more URLs than
 * allowed in one file are split into numbered files with an index.
 */
class WriterTest extends TestCase
{
    /**
     * @var array
     * @phpstan-var array<int, array{file: string, root: string, data: array<string, mixed>}>
     */
    private array $writtenFiles = [];

    /**
     * Returns a writer that records the written files, with the document root /shop/pub.
     *
     * @param int $maxUrls
     *
     * @return Writer
     */
    private function createWriter(int $maxUrls = Writer::MAX_URLS): Writer
    {
        $xmlHelper = $this->createStub(Xml::class);
        $xmlHelper->method('write')->willReturnCallback(
            function (string $fileName, string $rootElement, array $attributes, array $data): void {
                $this->writtenFiles[] = ['file' => $fileName, 'root' => $rootElement, 'data' => $data];
            }
        );

        $fileHelper = $this->createStub(Files::class);
        $fileHelper->method('determineFilePath')->willReturnCallback(fn(string $path): string => '/shop/' . $path);

        // the shop runs with pub/ as document root
        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getUrlPath')->willReturn('');

        return new Writer($xmlHelper, $fileHelper, $directoryList, new NullLogger(), $maxUrls);
    }

    /**
     * Returns a sitemap URL.
     *
     * @param string $url
     * @param string $lastModified
     *
     * @return SitemapUrl
     */
    private function createSitemapUrl(string $url, string $lastModified = '2026-11-01'): SitemapUrl
    {
        $sitemapUrl = new SitemapUrl();
        $sitemapUrl->setUrl($url);
        $sitemapUrl->setLastModified($lastModified);
        $sitemapUrl->setChangeFrequency('daily');
        $sitemapUrl->setPriority('1.0');

        return $sitemapUrl;
    }

    /**
     * Tests that every URL is written once (the first one wins) and that URLs without URL are left out.
     *
     * @return void
     * @throws Exception
     */
    public function testUniqueUrls(): void
    {
        $count = $this->createWriter()->writeSitemap(
            'sitemap.xml',
            'https://shop.test/',
            [
                $this->createSitemapUrl('https://shop.test/a.html'),
                $this->createSitemapUrl('https://shop.test/a.html', '2026-11-02'),
                new SitemapUrl(),
                $this->createSitemapUrl('https://shop.test/b.html')
            ],
            '2026-11-18'
        );

        $this->assertSame(2, $count);
        $this->assertCount(1, $this->writtenFiles);
        $this->assertSame('/shop/pub/sitemap.xml', $this->writtenFiles[ 0 ][ 'file' ]);
        $this->assertSame('urlset', $this->writtenFiles[ 0 ][ 'root' ]);
        $this->assertSame(
            [
                ['loc' => 'https://shop.test/a.html', 'lastmod' => '2026-11-01', 'changefreq' => 'daily',
                 'priority' => '1.0'],
                ['loc' => 'https://shop.test/b.html', 'lastmod' => '2026-11-01', 'changefreq' => 'daily',
                 'priority' => '1.0']
            ],
            $this->writtenFiles[ 0 ][ 'data' ][ 'url' ]
        );
    }

    /**
     * Tests that a sitemap with more URLs than allowed is split into numbered files, and that the index links them
     * with the base URL of the shop; before, the index contained the paths of the files on the server.
     *
     * @return void
     * @throws Exception
     */
    public function testSplitIntoFiles(): void
    {
        $sitemapUrls = [];

        foreach (['a', 'b', 'c', 'd', 'e'] as $page) {
            $sitemapUrls[] = $this->createSitemapUrl(sprintf('https://shop.test/%s.html', $page));
        }

        $count = $this->createWriter(2)->writeSitemap('maps/de.xml', 'https://shop.test/', $sitemapUrls, '2026-11-18');

        $this->assertSame(5, $count);
        $this->assertSame(
            [
                '/shop/pub/maps/de_001.xml',
                '/shop/pub/maps/de_002.xml',
                '/shop/pub/maps/de_003.xml',
                '/shop/pub/maps/de.xml'
            ],
            array_column($this->writtenFiles, 'file')
        );
        $lastChunk = $this->writtenFiles[ 2 ][ 'data' ][ 'url' ];
        $this->assertIsArray($lastChunk);
        $this->assertCount(1, $lastChunk);
        $this->assertSame('sitemapindex', $this->writtenFiles[ 3 ][ 'root' ]);
        $this->assertSame(
            [
                ['loc' => 'https://shop.test/maps/de_001.xml', 'lastmod' => '2026-11-18'],
                ['loc' => 'https://shop.test/maps/de_002.xml', 'lastmod' => '2026-11-18'],
                ['loc' => 'https://shop.test/maps/de_003.xml', 'lastmod' => '2026-11-18']
            ],
            $this->writtenFiles[ 3 ][ 'data' ][ 'sitemap' ]
        );
    }

    /**
     * Tests that values that are not set are left out of an entry, instead of empty elements.
     *
     * @return void
     */
    public function testEntryWithoutValues(): void
    {
        $sitemapUrl = new SitemapUrl();
        $sitemapUrl->setUrl('https://shop.test/a.html');

        $image = new SitemapUrlImage();
        $image->setUrl('https://shop.test/media/a.jpg');
        $image->setTitle('A');
        $sitemapUrl->addImage($image);

        $this->assertSame(
            [
                'loc'         => 'https://shop.test/a.html',
                'image:image' => [['image:loc' => 'https://shop.test/media/a.jpg', 'image:title' => 'A']]
            ],
            $this->createWriter()->getEntry($sitemapUrl)
        );
    }

    /**
     * Tests the names of the numbered files.
     *
     * @return void
     */
    public function testChunkFileName(): void
    {
        $writer = $this->createWriter();

        $this->assertSame('sitemap_001.xml', $writer->getChunkFileName('sitemap.xml', 1));
        $this->assertSame('maps/de_012.xml', $writer->getChunkFileName('maps/de.xml', 12));
    }

    /**
     * Tests the paths of the files: in pub/ unless the root of Magento is the document root.
     *
     * @return void
     * @throws Exception
     */
    public function testFilePath(): void
    {
        $this->assertSame('/shop/pub/sitemap.xml', $this->createWriter()->getFilePath('sitemap.xml'));
    }
}
