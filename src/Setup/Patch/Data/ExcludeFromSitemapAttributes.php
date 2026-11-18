<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Setup\Patch\Data;

use Infrangible\Core\Helper\Setup;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Validator\ValidateException;

/**
 * Adds the attribute "exclude_from_sitemap" to products and categories, in the default group of every attribute set.
 *
 * Installations of version 2 already have the attributes; the patch adds them to the attribute sets, which version 2
 * did for products only.
 */
class ExcludeFromSitemapAttributes implements DataPatchInterface
{
    /** Code of the attributes. */
    public const ATTRIBUTE_CODE = 'exclude_from_sitemap';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     * @param Setup $setupHelper
     */
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected CategorySetupFactory $categorySetupFactory,
        protected Setup $setupHelper
    ) {
    }

    /**
     * Adds the attributes, if they do not exist, and adds them to the attribute sets.
     *
     * @return $this
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function apply(): static
    {
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach ([Product::ENTITY => 'boolean', Category::ENTITY => 'select'] as $entityType => $input) {
            if (! $categorySetup->getAttributeId($entityType, self::ATTRIBUTE_CODE)) {
                $categorySetup->addAttribute(
                    $entityType,
                    self::ATTRIBUTE_CODE,
                    [
                        'label'                   => 'Exclude from Sitemap',
                        'type'                    => 'int',
                        'source'                  => Boolean::class,
                        'input'                   => $input,
                        'default'                 => '0',
                        'global'                  => ScopedAttributeInterface::SCOPE_STORE,
                        'visible'                 => true,
                        'required'                => false,
                        'user_defined'            => false,
                        'searchable'              => false,
                        'filterable'              => false,
                        'comparable'              => false,
                        'visible_on_front'        => false,
                        'used_in_product_listing' => false,
                        'used_for_sort_by'        => false,
                        'used_for_promo_rules'    => false,
                        'is_used_in_grid'         => false
                    ]
                );
            }

            $this->addToAttributeSets($categorySetup, $entityType);
        }

        return $this;
    }

    /**
     * Adds the attribute to the default group of every attribute set of the entity type.
     *
     * @param CategorySetup $categorySetup
     * @param string $entityType
     *
     * @return void
     * @throws LocalizedException
     */
    protected function addToAttributeSets(CategorySetup $categorySetup, string $entityType): void
    {
        $entityTypeId = $categorySetup->getEntityTypeId($entityType);

        foreach ($categorySetup->getAllAttributeSetIds($entityTypeId) as $attributeSetId) {
            $groupId = $categorySetup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

            $this->setupHelper->addAttributeToSetAndGroup(
                $categorySetup,
                $entityType,
                self::ATTRIBUTE_CODE,
                (string)$attributeSetId,
                (string)$groupId,
                100
            );
        }
    }

    /**
     * Returns the patches that have to run first: none.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Returns other names of the patch: none.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
