<?php
namespace Formula\Wallet\Plugin;

use Formula\Wallet\Api\WalletBalanceServiceInterface;
use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Psr\Log\LoggerInterface;

class CustomerRegistrationPlugin
{
    private const SIGNUP_BONUS_AMOUNT = 100.00;

    /**
     * @var WalletBalanceServiceInterface
     */
    private $walletBalanceService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param WalletBalanceServiceInterface $walletBalanceService
     * @param LoggerInterface $logger
     */
    public function __construct(
        WalletBalanceServiceInterface $walletBalanceService,
        LoggerInterface $logger
    ) {
        $this->walletBalanceService = $walletBalanceService;
        $this->logger = $logger;
    }

    /**
     * Credit signup wallet bonus after account creation
     *
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $result
     * @return CustomerInterface
     */
    public function afterCreateAccount(
        AccountManagementInterface $subject,
        CustomerInterface $result
    ): CustomerInterface {
        $customerId = (int)$result->getId();

        try {
            $this->walletBalanceService->updateBalanceAtomic(
                $customerId,
                self::SIGNUP_BONUS_AMOUNT,
                'add',
                'Welcome bonus',
                WalletTransactionInterface::REFERENCE_TYPE_SIGNUP
            );

            $this->logger->info('Signup wallet bonus credited', [
                'customer_id' => $customerId,
                'amount' => self::SIGNUP_BONUS_AMOUNT
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to credit signup wallet bonus', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }
}
