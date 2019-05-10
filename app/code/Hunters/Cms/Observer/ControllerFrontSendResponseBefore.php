<?php
namespace Hunters\Cms\Observer;

class ControllerFrontSendResponseBefore implements \Magento\Framework\Event\ObserverInterface {
	
    /**
     * @var \Hunters\Cms\Helper\Data
     */
    private $helper;
    
    /**
     * ControllerFrontSendResponseBefore constructor.
     * @param \Hunters\Cms\Helper\Data $helper
     */
    public function __construct(
        \Hunters\Cms\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }
	
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        if($this->helper->shouldSetCookie()) {
             $this->helper->setValueIntoCookie();
        }
    }
	
}
