<?php
namespace Hunters\Cms\Block;

class Block extends \Magento\Framework\View\Element\AbstractBlock implements \Magento\Framework\DataObject\IdentityInterface {
    
    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;
    
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    /**
     * Block factory
     *
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $_blockFactory;
    
    /**
     * @var \Hunters\Cms\Helper\Data
     */
    private $helper;
    
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;
	
    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param \Hunters\Cms\Helper\Data $helper
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        \Hunters\Cms\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_filterProvider = $filterProvider;
        $this->_storeManager = $storeManager;
        $this->_blockFactory = $blockFactory;
        $this->helper = $helper;
        $this->resource = $resource;
    }
    
    /**
     * Prepare Content HTML
     *
     * @return string
     */
    protected function _toHtml() {
        $html = '';
        $type = $this->getType();
        
        switch($type) {
            case 'cms':
                $html = $this->_getHtmlCms();
                break;
            
            case 'wordpress':
                $html = $this->_getHtmlWordpress();
                break;
        }
        
        return $html;
    }
    
    protected function _getHtmlCms() {
        $html           = '';
        $blockId        = $this->getBlockId();
        $category_id    = $this->helper->getCategoryId();
        
        if($blockId) {
            $blockId = $blockId . '_' . $category_id;
            $storeId = $this->_storeManager->getStore()->getId();
            /** @var \Magento\Cms\Model\Block $block */
            $block = $this->_blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            
            if($block->isActive()) {
                $html = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
            }
        }
        
        return $html;
    }
    
    protected function _getHtmlWordpress() {
        $html           = '';
        $blockId        = $this->getBlockId();
        $post_id        = $this->getPostId();
        $section_id     = $this->getSectionId();
        $limit          = (int) $this->getLimit() > 0 ? (int) $this->getLimit() : 1;
        $category_id    = $this->helper->getCategoryId();
        
        if($blockId) {
            $blockId = $blockId . '_' . $category_id;
            $storeId = $this->_storeManager->getStore()->getId();
            /** @var \Magento\Cms\Model\Block $block */
            $block = $this->_blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            
            if($block->isActive()) {
                $posts = $this->_getPosts($post_id, $section_id, $category_id, $limit);
                
                if(count($posts)) {
                    $i          = 1;
                    $html       = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
                    
                    foreach($posts as $_post) {
                        $html = $this->_filterPostContent($html, $_post, $i++);
                    }
                    
                    $html = $this->_filterHtml($html);
                }
            }
        }
        
        return $html;
    }
    
	protected function _getPosts($post_id = null, $section_id = null, $category_id = null, $limit = 1) {
        $data       = null;
		$collection = array();
		$connection = $this->resource->getConnection('wordpress');
        
        if($post_id && (int) $post_id > 0) {
            $data = $connection->fetchAll(
                sprintf(
                    'SELECT main_table.ID, main_table.post_title, main_table.post_content
                    FROM %s AS main_table
                    WHERE %s',
                    $this->resource->getTableName('wp_posts'),
                    $connection->quoteInto('main_table.ID = ?', (int) $post_id)
                )
            );
        } else if($section_id && (int) $section_id > 0 && $category_id && (int) $category_id > 0) {
            $data = $connection->fetchAll(
                sprintf(
                    'SELECT
                        main_table.ID,
                        main_table.post_name,
                        meta3.meta_value AS title,
                        meta4.meta_value AS content,
                        image.guid AS image,
                        meta6.meta_value AS button_label
                    FROM %s AS main_table
                    INNER JOIN %s AS meta1 ON meta1.post_id = main_table.ID AND %s
                    INNER JOIN %s AS meta2 ON meta2.post_id = main_table.ID AND %s
                    LEFT JOIN wp_postmeta AS meta3 ON meta3.post_id = main_table.ID AND (meta3.meta_key = "title")
                    LEFT JOIN wp_postmeta AS meta4 ON meta4.post_id = main_table.ID AND (meta4.meta_key = "content")
                    LEFT JOIN wp_postmeta AS meta5 ON meta5.post_id = main_table.ID AND (meta5.meta_key = "image")
                    LEFT JOIN wp_postmeta AS meta6 ON meta6.post_id = main_table.ID AND (meta6.meta_key = "button_label")
                    LEFT JOIN wp_posts AS image ON image.ID = meta5.meta_value AND image.post_type = "attachment"
                    WHERE main_table.post_status = "publish" AND main_table.post_type = "post"
                    ORDER BY RAND()
                    LIMIT %s',
                    $this->resource->getTableName('wp_posts'),
                    $this->resource->getTableName('wp_postmeta'),
                    $connection->quoteInto('(meta1.meta_key = "section_id" AND meta1.meta_value = ?)', (int) $section_id),
                    $this->resource->getTableName('wp_postmeta'),
                    $connection->quoteInto('(meta2.meta_key = "category_id" AND meta2.meta_value = ?)', (int) $category_id),
                    (int) $limit
                )
            );
        }
        
		if($data && is_array($data) && count($data)) {
			foreach($data as $_item) {
				if(isset($_item['ID'])) {
					$collection[$_item['ID']] = $_item;
				}
			}
		}
		
		return $collection;
	}
	
    protected function _filterHtml($html) {
        $html = str_replace(
            array(
                '{{blog_url}}'
            ),
            array(
                $this->getUrl('', array('_direct' => $this->helper->getBlogRouter()))
            ),
            $html
        );
        
        return $html;
    }
    
    protected function _filterPostContent($html, $post, $i) {
        preg_match(sprintf('#\{\{\s*?item_%s\s*?\}\}(.*?)\{\{\/item\}\}#s', $i), $html, $matches);
        
        if(isset($matches[0]) && isset($matches[1])) {
            $item_html = '';
            
            if(
                isset($post['title'])
                && isset($post['content'])
                && isset($post['image'])
                && isset($post['button_label'])
                && isset($post['post_name'])
            ) {
                $item_html = str_replace(
                    array(
                        '{{title}}',
                        '{{content}}',
                        '{{image}}',
                        '{{button_label}}',
                        '{{button_url}}'
                    ),
                    array(
                        $post['title'],
                        $post['content'],
                        $post['image'],
                        $post['button_label'],
                        $this->getUrl('', array('_direct' => sprintf('%s/%s', $this->helper->getBlogRouter(), $post['post_name'])))
                    ),
                    $matches[1]
                );
            }
            
            $html = str_replace($matches[0], $item_html, $html);
        }
        
        return $html;
    }
    
    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities() {
        return [
            \Magento\Cms\Model\Block::CACHE_TAG
            . '_' . $this->getBlockId()
            . '_' . $this->getPostId()
            . '_' . $this->getSectionId()
            . '_' . $this->helper->getCategoryId()
        ];
    }
    
}
