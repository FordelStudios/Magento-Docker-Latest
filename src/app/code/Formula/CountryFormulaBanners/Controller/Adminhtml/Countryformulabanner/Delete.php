<?php
// app/code/Formula/CountryFormulaBanners/Controller/Adminhtml/CountryFormulaBanner/Delete.php
namespace Formula\CountryFormulaBanners\Controller\Adminhtml\CountryFormulaBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\CountryFormulaBanners\Model\CountryFormulaBannerRepository;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Formula_CountryFormulaBanners::delete';

    /**
     * @var CountryFormulaBannerRepository
     */
    protected $bannerRepository;

    /**
     * @param Context $context
     * @param CountryFormulaBannerRepository $bannerRepository
     */
    public function __construct(
        Context $context,
        CountryFormulaBannerRepository $bannerRepository
    ) {
        $this->bannerRepository = $bannerRepository;
        parent::__construct($context);
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                // Delete banner
                $this->bannerRepository->deleteById($id);
                
                // Display success message
                $this->messageManager->addSuccessMessage(__('The banner has been deleted.'));
                
                // Redirect to grid
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting the banner.'));
            }
            
            // Redirect to edit page if we have error
            return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
        }
        
        // Display error message if we don't have an id
        $this->messageManager->addErrorMessage(__('We can\'t find a banner to delete.'));
        
        // Redirect to grid
        return $resultRedirect->setPath('*/*/');
    }
}