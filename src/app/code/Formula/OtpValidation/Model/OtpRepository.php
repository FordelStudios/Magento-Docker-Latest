<?php
namespace Formula\OtpValidation\Model;

use Formula\OtpValidation\Api\Data\OtpInterface;
use Formula\OtpValidation\Api\OtpRepositoryInterface;
use Formula\OtpValidation\Model\ResourceModel\Otp as OtpResourceModel;
use Formula\OtpValidation\Model\ResourceModel\Otp\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class OtpRepository implements OtpRepositoryInterface
{
    protected $otpFactory;
    protected $otpResourceModel;
    protected $otpCollectionFactory;

    public function __construct(
        OtpFactory $otpFactory,
        OtpResourceModel $otpResourceModel,
        CollectionFactory $otpCollectionFactory
    ) {
        $this->otpFactory = $otpFactory;
        $this->otpResourceModel = $otpResourceModel;
        $this->otpCollectionFactory = $otpCollectionFactory;
    }

    public function save(OtpInterface $otp)
    {
        try {
            $this->otpResourceModel->save($otp);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the OTP: %1', $exception->getMessage()),
                $exception
            );
        }
        return $otp;
    }

    public function getById($otpId)
    {
        $otp = $this->otpFactory->create();
        $this->otpResourceModel->load($otp, $otpId);
        if (!$otp->getEntityId()) {
            throw new NoSuchEntityException(__('OTP with id "%1" does not exist.', $otpId));
        }
        return $otp;
    }

    public function delete(OtpInterface $otp)
    {
        try {
            $this->otpResourceModel->delete($otp);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the OTP: %1', $exception->getMessage())
            );
        }
        return true;
    }

    public function deleteById($otpId)
    {
        return $this->delete($this->getById($otpId));
    }

    public function getByCustomerIdAndPhone($customerId, $phoneNumber)
    {
        $collection = $this->otpCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId)
                   ->addFieldToFilter('phone_number', $phoneNumber)
                   ->setOrder('created_at', 'DESC');

        return $collection->getFirstItem();
    }
}