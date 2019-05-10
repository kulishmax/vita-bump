<?php
namespace Hunters\Cms\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface {
	
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();
		
        $setup->getConnection()
            ->addColumn(
                'cms_block',
                'section_id',
                'int(10) unsigned not null default 0 comment "Section Id"'
            );
        
        $setup->getConnection()
            ->addColumn(
                'cms_block',
                'category_id',
                'int(10) unsigned not null default 0 comment "Category Id"'
            );
        
        $setup->endSetup();
    }
	
}
