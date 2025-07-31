<?php
/**
 * Helper Class for additional functionality (Optional)
 * File: src/app/code/Formula/Shiprocket/Helper/Data.php
 */
namespace Formula\Shiprocket\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    /**
     * Constructor
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Format courier data for frontend
     *
     * @param array $courierData
     * @return array
     */
    public function formatCourierData($courierData)
    {
        $formattedData = [];
        
        if (isset($courierData['data']['available_courier_companies'])) {
            foreach ($courierData['data']['available_courier_companies'] as $courier) {
                $formattedData[] = [
                    'courier_name' => $courier['courier_name'] ?? '',
                    'rate' => $courier['rate'] ?? 0,
                    'estimated_delivery_days' => $courier['estimated_delivery_days'] ?? '',
                    'etd' => $courier['etd'] ?? '',
                    'cod_available' => $courier['cod'] ?? 0,
                    'rating' => $courier['rating'] ?? 0,
                    'delivery_performance' => $courier['delivery_performance'] ?? 0
                ];
            }
        }
        
        return $formattedData;
    }
}