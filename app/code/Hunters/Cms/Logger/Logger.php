<?php
namespace Hunters\Cms\Logger;

class Logger extends \Monolog\Logger {
	
    public function __construct(array $handlers = [], array $processors = []) {
        parent::__construct('hunters_cms', $handlers, $processors);
    }
	
}
