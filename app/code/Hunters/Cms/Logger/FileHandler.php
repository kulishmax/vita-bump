<?php
namespace Hunters\Cms\Logger;

use Magento\Framework\Logger\Handler\Base;
use Magento\Framework\Filesystem\DriverInterface;

class FileHandler extends Base {
	
    /**
     * FileHandler constructor.
     * @param DriverInterface $filesystem
     * @param string $filename
     */
    public function __construct(DriverInterface $filesystem, $filename) {
        $this->fileName = $filename;
        parent::__construct($filesystem);
    }
	
}
