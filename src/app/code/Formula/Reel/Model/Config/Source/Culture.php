<?php
namespace Formula\Reel\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;

class Culture implements OptionSourceInterface
{
    /**
     * @var CountryCollectionFactory
     */
    protected $countryCollectionFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param CountryCollectionFactory $countryCollectionFactory
     */
    public function __construct(
        CountryCollectionFactory $countryCollectionFactory
    ) {
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [];

            // Add empty option
            $this->options[] = [
                'value' => '',
                'label' => __('-- Please Select --')
            ];

            $collection = $this->countryCollectionFactory->create()
                ->loadByStore()
                ->toOptionArray(false);

            foreach ($collection as $country) {
                if (!empty($country['value'])) {
                    $this->options[] = [
                        'value' => $country['value'],
                        'label' => $country['label']
                    ];
                }
            }
        }

        return $this->options;
    }
}
