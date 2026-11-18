<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Task;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Registry;
use Infrangible\Core\Helper\Stores;
use Infrangible\SimpleMail\Model\MailFactory;
use Infrangible\Sitemap\Helper\Data;
use Infrangible\Sitemap\Model\ISitemapUrl;
use Infrangible\Sitemap\Model\Writer;
use Infrangible\Task\Model\ResourceModel\RunFactory as RunResourceFactory;
use Infrangible\Task\Model\RunFactory;
use Infrangible\Task\Task\Base;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Generates the sitemaps: the URLs of the sources of every store go into the file of the store
 * (infrangible_sitemap/general/file_name); stores with the same file share it. With several files, the main sitemap
 * (sitemap.xml) is the index of all files, if enabled.
 *
 * Without file name, the task generates the sitemaps of all stores (admin store) or of the store of the run; with a
 * file name (generate in the admin), the sitemap of the stores with this file.
 */
class Sitemap extends Base
{
    /** File of the main sitemap, the index of the files of the stores. */
    public const MAIN_FILE_NAME = 'sitemap.xml';

    /** @var string|null */
    private ?string $fileName = null;

    /** @var bool */
    private bool $emptyRun = true;

    /** @var array<string, ISitemapUrl[]> URLs by file name */
    private array $urls = [];

    /** @var array<string, int> the first store of every file, whose base URL the file uses */
    private array $fileStoreIds = [];

    /**
     * @param File $file
     * @param Registry $registryHelper
     * @param \Infrangible\Task\Helper\Data $helper
     * @param LoggerInterface $logging
     * @param DirectoryList $directoryList
     * @param RunFactory $runFactory
     * @param RunResourceFactory $runResourceFactory
     * @param MailFactory $mailFactory
     * @param Variables $variables
     * @param Stores $storeHelper
     * @param Data $sitemapHelper
     * @param Writer $writer
     */
    public function __construct(
        File $file,
        Registry $registryHelper,
        \Infrangible\Task\Helper\Data $helper,
        LoggerInterface $logging,
        DirectoryList $directoryList,
        RunFactory $runFactory,
        RunResourceFactory $runResourceFactory,
        MailFactory $mailFactory,
        protected Variables $variables,
        protected Stores $storeHelper,
        protected Data $sitemapHelper,
        protected Writer $writer
    ) {
        parent::__construct(
            $file,
            $registryHelper,
            $helper,
            $logging,
            $directoryList,
            $runFactory,
            $runResourceFactory,
            $mailFactory
        );
    }

    /**
     * Returns the file name whose sitemap is generated, or null for the sitemaps of the store of the run.
     *
     * @return string|null
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * Sets the file name whose sitemap is generated, or null for the sitemaps of the store of the run.
     *
     * @param string|null $fileName
     *
     * @return void
     */
    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName === '' ? null : $fileName;
    }

    /**
     * Resets the URLs of a previous run.
     *
     * @return void
     */
    protected function prepare(): void
    {
        $this->urls = [];
        $this->fileStoreIds = [];
        $this->emptyRun = true;
    }

    /**
     * Collects the URLs of the stores and writes the sitemaps, if the URLs of all stores could be collected.
     *
     * @return bool
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function runTask(): bool
    {
        $result = true;

        foreach ($this->getStoreIds() as $storeId) {
            $result = $this->collectStoreSitemapUrls($storeId) && $result;
        }

        if ($result) {
            $this->writeSitemaps();
        }

        return $result;
    }

    /**
     * Returns the IDs of the stores whose sitemaps are generated.
     *
     * @return int[]
     * @throws NoSuchEntityException
     */
    protected function getStoreIds(): array
    {
        $fileName = $this->getFileName();

        if ($fileName === null && $this->getStoreCode() !== 'admin') {
            return [$this->variables->intValue($this->storeHelper->getStore($this->getStoreCode())->getId())];
        }

        $storeIds = [];

        foreach ($this->storeHelper->getStores() as $store) {
            $storeId = $this->variables->intValue($store->getId());

            if ($fileName === null || $this->sitemapHelper->getFileName($storeId) === $fileName) {
                $storeIds[] = $storeId;
            }
        }

        return $storeIds;
    }

    /**
     * Collects the URLs of the sources of the store.
     *
     * @param int $storeId
     *
     * @return bool false if a source failed or the file name of the store is invalid
     */
    protected function collectStoreSitemapUrls(int $storeId): bool
    {
        if (! $this->sitemapHelper->isEnabled($storeId)) {
            return true;
        }

        $fileName = $this->sitemapHelper->getFileName($storeId);

        if (! $this->isValidFileName($fileName)) {
            $this->logging->error(
                sprintf('Invalid file name: %s of the sitemap of the store with ID: %d', $fileName, $storeId)
            );

            return false;
        }

        $this->logging->info(sprintf('Collecting sitemap URLs of the store with ID: %d', $storeId));

        try {
            $sources = $this->sitemapHelper->getSources($storeId);
        } catch (LocalizedException $exception) {
            $this->logging->error(
                sprintf(
                    'Could not initialize the sources of the store with ID: %d because: %s',
                    $storeId,
                    $exception->getMessage()
                )
            );

            return false;
        }

        $this->fileStoreIds[ $fileName ] ??= $storeId;
        $this->urls[ $fileName ] ??= [];

        foreach ($sources as $sourceName => $source) {
            try {
                $storeData = $source->getStoreData($storeId);
                $sitemapUrls = $source->transformStoreData($storeId, $storeData);
            } catch (Exception $exception) {
                $this->logging->error(
                    sprintf(
                        'Could not get the URLs of the source: %s in the store with ID: %d because: %s',
                        $sourceName,
                        $storeId,
                        $exception->getMessage()
                    )
                );

                return false;
            }

            $this->logging->info(
                sprintf(
                    'Found %d URL(s) of the source: %s in the store with ID: %d',
                    count($sitemapUrls),
                    $sourceName,
                    $storeId
                )
            );

            array_push($this->urls[ $fileName ], ...$sitemapUrls);
        }

        return true;
    }

    /**
     * Writes the collected URLs into their files and, with several files, the main sitemap as their index.
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function writeSitemaps(): void
    {
        if (count($this->urls) === 0) {
            return;
        }

        $writeMainSitemap = count($this->urls) > 1 &&
            $this->storeHelper->getDefaultConfigFlag('infrangible_sitemap/general/main', true);

        if ($writeMainSitemap && array_key_exists(self::MAIN_FILE_NAME, $this->urls)) {
            throw new LocalizedException(
                __(
                    'If the stores have several files, no store may use the file %1, which becomes their index.',
                    self::MAIN_FILE_NAME
                )
            );
        }

        $this->setEmptyRun(false);

        $lastModified = gmdate('Y-m-d');

        $sitemaps = [];

        foreach ($this->urls as $fileName => $fileUrls) {
            $baseUrl = $this->sitemapHelper->getBaseUrl($this->fileStoreIds[ $fileName ]);

            $count = $this->writer->writeSitemap($fileName, $baseUrl, $fileUrls, $lastModified);

            $this->logging->info(sprintf('Wrote %d unique URL(s) into the sitemap: %s', $count, $fileName));

            $sitemaps[] = ['loc' => $baseUrl . $fileName, 'lastmod' => $lastModified];
        }

        if ($writeMainSitemap) {
            $this->writer->writeIndex(self::MAIN_FILE_NAME, $sitemaps);
        }
    }

    /**
     * Tells whether the file name is a relative path of an XML file in the document root, without "..".
     *
     * @param string $fileName
     *
     * @return bool
     */
    protected function isValidFileName(string $fileName): bool
    {
        return preg_match('/^(?:[a-z0-9_-]+\/)*[a-z0-9_.-]+\.xml$/i', $fileName) === 1 &&
            ! in_array('..', explode('/', $fileName), true);
    }

    /**
     * Does nothing after the run.
     *
     * @param bool $success
     *
     * @return void
     */
    protected function dismantle(bool $success): void // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
        // the files are written at the end of the run, there is nothing to clean up
    }

    /**
     * Tells whether the run wrote no sitemap.
     *
     * @return bool
     */
    public function isEmptyRun(): bool
    {
        return $this->emptyRun;
    }

    /**
     * Sets whether the run wrote no sitemap.
     *
     * @param bool $emptyRun
     *
     * @return void
     */
    public function setEmptyRun(bool $emptyRun): void
    {
        $this->emptyRun = $emptyRun;
    }
}
