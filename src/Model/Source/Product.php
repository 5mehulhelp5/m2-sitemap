<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model\Source;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Stores;
use Infrangible\Sitemap\Helper\Data;
use Infrangible\Sitemap\Model\ISitemapUrl;
use Infrangible\Sitemap\Model\ISitemapUrlDataObject;
use Infrangible\Sitemap\Model\ISitemapUrlDataObjectAttribute;
use Infrangible\Sitemap\Model\ISitemapUrlImage;
use Infrangible\Sitemap\Model\SitemapUrlDataObjectAttributeFactory;
use Infrangible\Sitemap\Model\SitemapUrlDataObjectFactory;
use Infrangible\Sitemap\Model\SitemapUrlFactory;
use Infrangible\Sitemap\Model\SitemapUrlImageFactory;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\App\EmulationFactory;
use Psr\Log\LoggerInterface;

/**
 * The products of the store that are visible, enabled and (unless out of stock products are shown) in stock, with
 * their images and a thumbnail in the PageMap.
 *
 * Events: infrangible_sitemap_attributes_product (attributes: data object with the attribute codes to load) and
 * infrangible_sitemap_transform_product (sitemap_url, product_data) for every URL.
 */
class Product implements ISource
{
    /** Value of an image attribute without image. */
    public const NO_SELECTION = 'no_selection';

    /** Image type of the URL of the images. */
    public const IMAGE_TYPE = 'product_page_image_large';

    /**
     * @param Variables $variables
     * @param LoggerInterface $logging
     * @param Stores $storeHelper
     * @param Database $databaseHelper
     * @param Export $exportHelper
     * @param Data $sitemapHelper
     * @param SitemapUrlFactory $sitemapUrlFactory
     * @param SitemapUrlImageFactory $sitemapUrlImageFactory
     * @param SitemapUrlDataObjectFactory $sitemapUrlDataObjectFactory
     * @param SitemapUrlDataObjectAttributeFactory $sitemapUrlDataObjectAttributeFactory
     * @param UrlBuilder $urlBuilder
     * @param EmulationFactory $appEmulationFactory
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        protected Variables $variables,
        protected LoggerInterface $logging,
        protected Stores $storeHelper,
        protected Database $databaseHelper,
        protected Export $exportHelper,
        protected Data $sitemapHelper,
        protected SitemapUrlFactory $sitemapUrlFactory,
        protected SitemapUrlImageFactory $sitemapUrlImageFactory,
        protected SitemapUrlDataObjectFactory $sitemapUrlDataObjectFactory,
        protected SitemapUrlDataObjectAttributeFactory $sitemapUrlDataObjectAttributeFactory,
        protected UrlBuilder $urlBuilder,
        protected EmulationFactory $appEmulationFactory,
        protected ManagerInterface $eventManager
    ) {
    }

    /**
     * Returns the data of the exportable products of the store by product ID.
     *
     * @param int $storeId
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     * @throws Exception
     */
    public function getStoreData(int $storeId): array
    {
        if (! $this->storeHelper->getStoreConfigFlag('infrangible_sitemap/product/export', true, $storeId)) {
            return [];
        }

        $data = new DataObject(['attributes' => ['name', 'url_path', 'updated_at', 'exclude_from_sitemap']]);

        $this->eventManager->dispatch('infrangible_sitemap_attributes_product', ['attributes' => $data]);

        $attributes = $data->getData('attributes');

        $showOutOfStock = $this->storeHelper->getStoreConfigFlag(
            'cataloginventory/options/show_out_of_stock',
            false,
            $storeId
        );

        $dbAdapter = $this->databaseHelper->getDefaultConnection();

        $lastProductId = 0;
        $productsData = [];

        while (true) {
            $productIds = $this->exportHelper->getExportableProductIds($storeId, ! $showOutOfStock, [], $lastProductId);

            if (count($productIds) === 0) {
                break;
            }

            $this->logging->debug(
                sprintf('Fetched %d product(s) after product ID: %d', count($productIds), $lastProductId)
            );

            // array_merge() would renumber the product IDs
            $productsData += $this->exportHelper->getProductsData(
                $dbAdapter,
                $productIds,
                $storeId,
                [],
                is_array($attributes) ? $attributes : []
            );

            $lastProductId = $this->variables->intValue(end($productIds));
        }

        return $productsData;
    }

    /**
     * Transforms the products into sitemap URLs, in the environment of the store for the URLs of the images.
     *
     * @param int $storeId
     * @param array $storeData
     *
     * @phpstan-param array<int|string, mixed> $storeData
     *
     * @return ISitemapUrl[]
     */
    public function transformStoreData(int $storeId, array $storeData): array
    {
        // an own emulation: the admin and the console already emulate the store with the shared one, whose
        // environment would be overwritten by a nested emulation
        $appEmulation = $this->appEmulationFactory->create();
        $appEmulation->startEnvironmentEmulation($storeId);

        try {
            $urls = [];

            foreach ($storeData as $productData) {
                if (! is_array($productData) ||
                    $this->sitemapHelper->isExcluded($productData[ 'exclude_from_sitemap' ] ?? null)) {
                    continue;
                }

                $sitemapUrl = $this->createSitemapUrlModel();

                $this->populateSitemapUrl($sitemapUrl, $storeId, $productData);
                $this->addImages($sitemapUrl, $productData);

                $this->eventManager->dispatch(
                    'infrangible_sitemap_transform_product',
                    ['sitemap_url' => $sitemapUrl, 'product_data' => $productData]
                );

                if ($sitemapUrl->isValid()) {
                    $urls[] = $sitemapUrl;
                }
            }

            return $urls;
        } finally {
            $appEmulation->stopEnvironmentEmulation();
        }
    }

    /**
     * Adds the images of the gallery of the product and the first image as thumbnail of the PageMap.
     *
     * @param ISitemapUrl $sitemapUrl
     * @param array $productData
     *
     * @phpstan-param array<int|string, mixed> $productData
     *
     * @return void
     */
    protected function addImages(ISitemapUrl $sitemapUrl, array $productData): void
    {
        $galleryImages = $productData[ 'gallery_images' ] ?? [];

        foreach (is_array($galleryImages) ? $galleryImages : [] as $galleryImageData) {
            if (! is_array($galleryImageData) || $this->getImagePath($galleryImageData) === '') {
                continue;
            }

            $sitemapUrlImage = $this->createSitemapUrlImageModel();

            $this->populateSitemapUrlImage($sitemapUrlImage, $productData, $galleryImageData);

            $sitemapUrl->addImage($sitemapUrlImage);

            if (count($sitemapUrl->getDataObjects()) === 0) {
                $thumbnail = $this->createSitemapUrlDataObjectModel();
                $thumbnail->setType('thumbnail');

                $thumbnailName = $this->createSitemapUrlDataObjectAttributeModel();
                $this->populateDataObjectAttributeThumbnailName($thumbnailName, $productData);
                $thumbnail->addAttribute($thumbnailName);

                $thumbnailSource = $this->createSitemapUrlDataObjectAttributeModel();
                $this->populateDataObjectAttributeThumbnailSource($thumbnailSource, $galleryImageData);
                $thumbnail->addAttribute($thumbnailSource);

                $sitemapUrl->addDataObject($thumbnail);
            }
        }
    }

    /**
     * Returns the URL of the product: the URL rewrite without category, or the URL path, or an empty string.
     *
     * @param int $storeId
     * @param array $productData
     *
     * @phpstan-param array<int|string, mixed> $productData
     *
     * @return string
     */
    protected function getProductUrl(int $storeId, array $productData): string
    {
        $urlRewrites = $productData[ 'url_rewrites' ] ?? [];
        $path = is_array($urlRewrites) ? $this->variables->stringValueOrDefault($urlRewrites[ 0 ] ?? null) : '';

        if ($path === '') {
            $path = $this->variables->stringValueOrDefault($productData[ 'url_path' ] ?? null);
        }

        return $path === '' ? '' : $this->sitemapHelper->getUrl($path, $storeId);
    }

    /**
     * Returns the path of an image of the gallery, or an empty string if the gallery entry has no image.
     *
     * @param array $galleryImageData
     *
     * @phpstan-param array<int|string, mixed> $galleryImageData
     *
     * @return string
     */
    protected function getImagePath(array $galleryImageData): string
    {
        $imagePath = $this->variables->stringValueOrDefault($galleryImageData[ 'value' ] ?? null);

        return $imagePath === self::NO_SELECTION ? '' : $imagePath;
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
     * Sets the URL, the date of the last change, the change frequency and the priority of the product.
     *
     * @param ISitemapUrl $sitemapUrl
     * @param int $storeId
     * @param array $productData
     *
     * @phpstan-param array<int|string, mixed> $productData
     *
     * @return void
     */
    public function populateSitemapUrl(ISitemapUrl $sitemapUrl, int $storeId, array $productData): void
    {
        $sitemapUrl->setUrl($this->getProductUrl($storeId, $productData));
        $sitemapUrl->setLastModified($this->sitemapHelper->getLastModified($productData[ 'updated_at' ] ?? null));
        $sitemapUrl->setChangeFrequency($this->sitemapHelper->getChangeFrequency('product', $storeId, 'daily'));
        $sitemapUrl->setPriority($this->sitemapHelper->getPriority('product', $storeId, '1.0'));
    }

    /**
     * Returns a new image of a sitemap URL.
     *
     * @return ISitemapUrlImage
     */
    public function createSitemapUrlImageModel(): ISitemapUrlImage
    {
        return $this->sitemapUrlImageFactory->create();
    }

    /**
     * Sets the URL, the title (name of the product) and the caption (label of the image) of an image.
     *
     * @param ISitemapUrlImage $sitemapUrlImage
     * @param array $productData
     * @param array $galleryImageData
     *
     * @phpstan-param array<int|string, mixed> $productData
     * @phpstan-param array<int|string, mixed> $galleryImageData
     *
     * @return void
     */
    public function populateSitemapUrlImage(
        ISitemapUrlImage $sitemapUrlImage,
        array $productData,
        array $galleryImageData
    ): void {
        $sitemapUrlImage->setUrl($this->urlBuilder->getUrl($this->getImagePath($galleryImageData), self::IMAGE_TYPE));
        $sitemapUrlImage->setTitle($this->variables->stringValueOrDefault($productData[ 'name' ] ?? null));
        $sitemapUrlImage->setCaption($this->variables->stringValueOrDefault($galleryImageData[ 'label' ] ?? null));
    }

    /**
     * Returns a new data object of a sitemap URL.
     *
     * @return ISitemapUrlDataObject
     */
    public function createSitemapUrlDataObjectModel(): ISitemapUrlDataObject
    {
        return $this->sitemapUrlDataObjectFactory->create();
    }

    /**
     * Returns a new attribute of a data object.
     *
     * @return ISitemapUrlDataObjectAttribute
     */
    public function createSitemapUrlDataObjectAttributeModel(): ISitemapUrlDataObjectAttribute
    {
        return $this->sitemapUrlDataObjectAttributeFactory->create();
    }

    /**
     * Sets the attribute "name" of the thumbnail to the name of the product.
     *
     * @param ISitemapUrlDataObjectAttribute $dataObjectAttributeThumbnailName
     * @param array $productData
     *
     * @phpstan-param array<int|string, mixed> $productData
     *
     * @return void
     */
    public function populateDataObjectAttributeThumbnailName(
        ISitemapUrlDataObjectAttribute $dataObjectAttributeThumbnailName,
        array $productData
    ): void {
        $dataObjectAttributeThumbnailName->setName('name');
        $dataObjectAttributeThumbnailName->setValue(
            $this->variables->stringValueOrDefault($productData[ 'name' ] ?? null)
        );
    }

    /**
     * Sets the attribute "src" of the thumbnail to the URL of the image.
     *
     * @param ISitemapUrlDataObjectAttribute $dataObjectAttributeThumbnailSource
     * @param array $galleryImageData
     *
     * @phpstan-param array<int|string, mixed> $galleryImageData
     *
     * @return void
     */
    public function populateDataObjectAttributeThumbnailSource(
        ISitemapUrlDataObjectAttribute $dataObjectAttributeThumbnailSource,
        array $galleryImageData
    ): void {
        $dataObjectAttributeThumbnailSource->setName('src');
        $dataObjectAttributeThumbnailSource->setValue(
            $this->urlBuilder->getUrl($this->getImagePath($galleryImageData), self::IMAGE_TYPE)
        );
    }
}
