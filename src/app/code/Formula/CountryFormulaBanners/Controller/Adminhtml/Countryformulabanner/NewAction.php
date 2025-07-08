<?php
// app/code/Formula/CountryFormulaBanners/Controller/Adminhtml/CountryFormulaBanner/NewAction.php
namespace Formula\CountryFormulaBanners\Controller\Adminhtml\CountryFormulaBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;

class NewAction extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Formula_CountryFormulaBanners::save';

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * Create new banner
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}