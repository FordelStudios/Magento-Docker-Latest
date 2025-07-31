<?php
namespace Formula\Reel\Ui\DataProvider\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Psr\Log\LoggerInterface;

class Video implements ModifierInterface
{
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function modifyData(array $data)
    {
        return $data;
    }
    
    public function modifyMeta(array $meta)
    {
        if (isset($meta['general']['children']['video'])) {
            $meta['general']['children']['video']['arguments']['data']['config']['required'] = true;
            $meta['general']['children']['video']['arguments']['data']['config']['validation'] = [
                'required-entry' => true
            ];
        }
        
        return $meta;
    }
}