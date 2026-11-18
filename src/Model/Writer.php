<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

use Exception;
use Infrangible\Core\Helper\Files;
use Infrangible\Core\Helper\Xml;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

/**
 * Writes sitemap files (urlset) and sitemap indexes (sitemapindex) into the document root of the shop.
 *
 * A sitemap with more URLs than allowed in one file (50,000) is split into numbered files (sitemap_001.xml, ...), and
 * the file of the sitemap becomes the index of these files.
 */
class Writer
{
    /** Namespaces of the sitemap protocol and of the image extension of Google. */
    public const NAMESPACE_SITEMAP = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    public const NAMESPACE_IMAGE = 'http://www.google.com/schemas/sitemap-image/1.1';
    public const NAMESPACE_PAGEMAP = 'http://www.google.com/schemas/sitemap-pagemap/1.0';

    /** Maximum number of URLs in one file of the sitemap protocol. */
    public const MAX_URLS = 50000;

    /**
     * @param Xml $xmlHelper
     * @param Files $fileHelper
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logging
     * @param int $maxUrls
     */
    public function __construct(
        protected Xml $xmlHelper,
        protected Files $fileHelper,
        protected DirectoryList $directoryList,
        protected LoggerInterface $logging,
        protected int $maxUrls = self::MAX_URLS
    ) {
    }

    /**
     * Writes the URLs into the file, each URL once.
     *
     * With more URLs than allowed in one file, they are split into numbered files with the file as their index.
     *
     * @param string $fileName file name relative to the document root, for example "sitemap.xml" or "de/sitemap.xml"
     * @param string $baseUrl base URL of the shop, for the URLs of the numbered files
     * @param ISitemapUrl[] $sitemapUrls
     * @param string $lastModified date of the numbered files in the index (Y-m-d)
     *
     * @return int number of written URLs
     * @throws Exception
     */
    public function writeSitemap(string $fileName, string $baseUrl, array $sitemapUrls, string $lastModified): int
    {
        $entries = [];

        foreach ($sitemapUrls as $sitemapUrl) {
            // the first URL wins, as the sources come in the configured order
            if ($sitemapUrl->isValid() && ! array_key_exists($sitemapUrl->getUrl(), $entries)) {
                $entries[ $sitemapUrl->getUrl() ] = $this->getEntry($sitemapUrl);
            }
        }

        $chunks = array_chunk(array_values($entries), max(1, $this->maxUrls));

        if (count($chunks) <= 1) {
            $this->logging->info(sprintf('Writing %d URL(s) into file: %s', count($entries), $fileName));

            $this->writeUrls($fileName, $chunks[ 0 ] ?? []);

            return count($entries);
        }

        $this->logging->info(
            sprintf('Splitting %d URL(s) of file: %s into %d files', count($entries), $fileName, count($chunks))
        );

        $sitemaps = [];

        foreach ($chunks as $number => $chunk) {
            $chunkFileName = $this->getChunkFileName($fileName, $number + 1);

            $this->logging->info(sprintf('Writing %d URL(s) into file: %s', count($chunk), $chunkFileName));

            $this->writeUrls($chunkFileName, $chunk);

            $sitemaps[] = ['loc' => $baseUrl . $chunkFileName, 'lastmod' => $lastModified];
        }

        $this->writeIndex($fileName, $sitemaps);

        return count($entries);
    }

    /**
     * Writes a sitemap index with the files.
     *
     * @param string $fileName file name relative to the document root
     * @param array $sitemaps
     *
     * @phpstan-param array<int, array{loc: string, lastmod: string}> $sitemaps
     *
     * @return void
     * @throws Exception
     */
    public function writeIndex(string $fileName, array $sitemaps): void
    {
        $this->logging->info(sprintf('Writing index of %d file(s) into file: %s', count($sitemaps), $fileName));

        $this->xmlHelper->write(
            $this->getFilePath($fileName, true),
            'sitemapindex',
            ['xmlns' => self::NAMESPACE_SITEMAP],
            ['sitemap' => $sitemaps]
        );
    }

    /**
     * Returns the absolute path of a file of the document root.
     *
     * The document root is pub/, unless the shop runs with the root directory of Magento as document root.
     *
     * @param string $fileName
     * @param bool $makeDirectory creates the directory of the file if it does not exist
     *
     * @return string
     * @throws Exception
     */
    public function getFilePath(string $fileName, bool $makeDirectory = false): string
    {
        $publicUrlPath = $this->directoryList->getUrlPath(DirectoryList::PUB);

        return $this->fileHelper->determineFilePath(
            $publicUrlPath === DirectoryList::PUB ? $fileName : sprintf('%s/%s', DirectoryList::PUB, $fileName),
            null,
            $makeDirectory
        );
    }

    /**
     * Returns the name of a numbered file: sitemap.xml becomes sitemap_001.xml, de/sitemap.xml de/sitemap_001.xml.
     *
     * @param string $fileName
     * @param int $number
     *
     * @return string
     */
    public function getChunkFileName(string $fileName, int $number): string
    {
        $separator = strrpos($fileName, '/');
        $directory = $separator === false ? '' : substr($fileName, 0, $separator + 1);
        $baseName = $separator === false ? $fileName : substr($fileName, $separator + 1);

        $dot = strrpos($baseName, '.');
        $name = $dot === false ? $baseName : substr($baseName, 0, $dot);
        $extension = $dot === false ? '' : substr($baseName, $dot);

        return sprintf('%s%s_%03d%s', $directory, $name, $number, $extension);
    }

    /**
     * Returns the entry of the URL in the sitemap; values that are not set are left out.
     *
     * @param ISitemapUrl $sitemapUrl
     *
     * @return array
     * @phpstan-return array<string, mixed>
     */
    public function getEntry(ISitemapUrl $sitemapUrl): array
    {
        $entry = $this->withoutEmptyValues(
            [
                'loc'        => $sitemapUrl->getUrl(),
                'lastmod'    => $sitemapUrl->getLastModified(),
                'changefreq' => $sitemapUrl->getChangeFrequency(),
                'priority'   => $sitemapUrl->getPriority()
            ]
        );

        foreach ($sitemapUrl->getImages() as $image) {
            if ($image->getUrl() !== '') {
                $entry[ 'image:image' ][] = $this->withoutEmptyValues(
                    [
                        'image:loc'     => $image->getUrl(),
                        'image:title'   => $image->getTitle(),
                        'image:caption' => $image->getCaption()
                    ]
                );
            }
        }

        $dataObjects = [];

        foreach ($sitemapUrl->getDataObjects() as $dataObject) {
            $attributes = [];

            foreach ($dataObject->getAttributes() as $attribute) {
                $attributes[] = ['@name' => $attribute->getName(), '@value' => $attribute->getValue()];
            }

            $dataObjects[] = ['@type' => $dataObject->getType(), 'Attribute' => $attributes];
        }

        if (count($dataObjects) > 0) {
            $entry[ 'PageMap' ] = ['@xmlns' => self::NAMESPACE_PAGEMAP, 'DataObject' => $dataObjects];
        }

        return $entry;
    }

    /**
     * Returns the values that are not empty strings.
     *
     * @param array $values
     * @phpstan-param array<string, string> $values
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    protected function withoutEmptyValues(array $values): array
    {
        return array_filter($values, fn(string $value): bool => $value !== '');
    }

    /**
     * Writes the entries into a sitemap file.
     *
     * @param string $fileName
     * @param array $entries
     *
     * @phpstan-param array<int, array<string, mixed>> $entries
     *
     * @return void
     * @throws Exception
     */
    protected function writeUrls(string $fileName, array $entries): void
    {
        $this->xmlHelper->write(
            $this->getFilePath($fileName, true),
            'urlset',
            ['xmlns' => self::NAMESPACE_SITEMAP, 'xmlns:image' => self::NAMESPACE_IMAGE],
            ['url' => $entries]
        );
    }
}
