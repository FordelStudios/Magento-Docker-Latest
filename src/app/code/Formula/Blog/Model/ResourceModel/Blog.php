<?php
declare(strict_types=1);

namespace Formula\Blog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Blog extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'blog_details';
    
    /**
     * @var string
     */
    private const PRIMARY_KEY = 'blog_id';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}