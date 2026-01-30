<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SpecialOffer extends AbstractDb
{
    public const TABLE_NAME = 'formula_special_offer';
    public const PRIMARY_KEY = 'entity_id';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}
