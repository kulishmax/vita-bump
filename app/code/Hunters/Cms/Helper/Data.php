<?php
namespace Hunters\Cms\Helper;

class Data {
	
    const COOKIE_NAME_VCIS = 'vb_cms_i_category';
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
	
    /**
     * @var \Hunters\Cms\Logger\Logger
     */
    private $logger;
	
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;
	
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    protected $request;
    
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;
    
    /** @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory */
    protected $cookieMetadataFactory;
    
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Hunters\Cms\Logger\LoggerFactory $loggerFactory,
        \Hunters\Cms\Logger\FileHandlerFactory $fileHandlerFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $loggerFactory->create();
        $this->dateTime = $dateTime;
        $this->resource = $resource;
        $this->_coreRegistry = $coreRegistry;
        $this->request = $request;
        $this->_cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        
        /* @var FileHandler $fileHandler */
        $fileHandler = $fileHandlerFactory->create([
            'filename' => '/var/log/' . $this->getLogFile(),
        ]);
		
        $this->logger->pushHandler($fileHandler);
    }
    
    /**
     * @return bool
     */
    public function isEnabled() {
        return !! $this->scopeConfig->getValue('hunters_cms/general/enabled');
    }
    
    /**
     * @return string
     */
    public function getUrlParam() {
        return (string) $this->scopeConfig->getValue('hunters_cms/general/url_param');
    }
    
    /**
     * @return string
     */
    public function getBlogRouter() {
        return (string) $this->scopeConfig->getValue('hunters_cms/general/blog_router');
    }
    
    /**
     * @return string
     */
    public function getCategory1() {
        $value = (int) $this->scopeConfig->getValue('hunters_cms/general/category_1');
        
        if($value > 0) {
            $value = $value * 60;
        }
        
        return max(0, $value);
    }
    
    /**
     * @return string
     */
    public function getCategory2() {
        $value = (int) $this->scopeConfig->getValue('hunters_cms/general/category_2');
        
        if($value > 0) {
            $value = $value * 60;
        }
        
        return max(0, $value);
    }
    
    /**
     * @return string
     */
    public function getCategory3() {
        $value = (int) $this->scopeConfig->getValue('hunters_cms/general/category_3');
        
        if($value > 0) {
            $value = $value * 60;
        }
        
        return max(0, $value);
    }
    
    /**
     * @return bool
     */
    public function isLogEnabled() {
        return !! $this->scopeConfig->getValue('hunters_cms/general/log');
    }
	
    /**
     * @return string
     */
    public function getLogFile() {
        return (string) $this->scopeConfig->getValue('hunters_cms/general/log_file');
    }
	
    /**
     * @param string $message
     * @param array $context
     * @param int $level
     */
    public function log($message, array $context = [], $level = \Hunters\Cms\Logger\Logger::DEBUG) {
        if($this->isLogEnabled()) {
            $this->logger->addRecord($level, $message, $context);
        }
    }
    
    public function shouldSetCookie() {
        $value = false;
        
        if($this->getUrlParam() && !empty($this->getUrlParam())) {
            if($this->request->getParam($this->getUrlParam())) {
                $value = true;
            }
        }
        
        return $value;
    }
    
    public function getValueFromCookie() {
        $value = (int) $this->_cookieManager->getCookie(self::COOKIE_NAME_VCIS);
        return max(0, $value);
    }
    
    public function setValueIntoCookie() {
        $value = $this->dateTime->gmtTimestamp();
        
        $sensitiveCookieMetadata = $this->cookieMetadataFactory->createSensitiveCookieMetadata(
            array(\Magento\Framework\Stdlib\Cookie\CookieMetadata::KEY_DURATION => 366*24*60*60)
        )->setPath('/');
        
        $this->_cookieManager->setSensitiveCookie(self::COOKIE_NAME_VCIS, $value, $sensitiveCookieMetadata);
        
        $this->log(sprintf('Set cookie %s', $value));
    }
    
    public function getCategoryId() {
        $value  = 1;
        $cookie = $this->getValueFromCookie();
        
        if($cookie > 0) {
            foreach(array(3, 2, 1) as $_category) {
                $method = sprintf('getCategory%s', $_category);
                $data = $this->$method();
                
                if($data > 0) {
                    if($this->dateTime->gmtTimestamp() > ($cookie + $data)) {
                        $value = $_category;
                        break;
                    }
                }
            }
        }
        
        return $value;
    }
    
}
