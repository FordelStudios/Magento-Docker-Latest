<?php
namespace Formula\Blog\Model\ResourceModel\Blog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\Blog\Model\Blog as Model;
use Formula\Blog\Model\ResourceModel\Blog as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'blog_id';

    protected function _construct()
    {
        $this->_init(
            \Formula\Blog\Model\Blog::class,
            \Formula\Blog\Model\ResourceModel\Blog::class
        );
    }
}