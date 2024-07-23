<?php

/**
 * This source file is subject to the MIT License.
 * It is also available through http://opensource.org/licenses/MIT
 *
 * @category  Meu Prime
 * @package   MeuPrime_Admin
 * @author    Meu Prime <contato@meuprime.com>
 * @copyright 2023 Meu Prime (http://meuprime.com)
 * @license   http://opensource.org/licenses/MIT MIT
 */

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

// Add volume to prduct attribute set
$attributeCode = 'taxonomy';
$config = array(
    'position' => 1,
    'required' => 0,
    'label'    => 'Taxonomia',
    'type'     => 'int',
    'input'    => 'select',
    'apply_to' => 'simple,bundle,grouped,configurable',
    'note'     => 'Último nível da Taxonomia do Google',
    'option' => array(
        'values' => array(
            'Adultos',
            'Armas',
            'Roupas',
        )
    ),
);

$setup->addAttribute('catalog_product', $attributeCode, $config);
$installer->endSetup();