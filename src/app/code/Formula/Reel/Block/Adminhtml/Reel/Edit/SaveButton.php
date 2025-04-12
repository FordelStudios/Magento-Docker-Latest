<?php
namespace Formula\Reel\Block\Adminhtml\Reel\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Psr\Log\LoggerInterface;

class SaveButton implements ButtonProviderInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'formula_reel_form.formula_reel_form',
                                'actionName' => 'save',
                                'params' => [
                                    false
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'sort_order' => 30,
        ];
        
        $this->logger->debug('SaveButton configuration', ['data' => $data]);
        return $data;
    }
}