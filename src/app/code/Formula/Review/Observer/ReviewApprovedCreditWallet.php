<?php
declare(strict_types=1);

namespace Formula\Review\Observer;

use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Formula\Wallet\Api\WalletBalanceServiceInterface;
use Formula\Wallet\Api\WalletTransactionRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Review\Model\Review;
use Psr\Log\LoggerInterface;

/**
 * Credit ₹50 to the customer's Formula wallet when a review is approved.
 *
 * Dedup guard: checks formula_wallet_transaction for an existing row with
 * reference_type='review' + reference_id=<review_id> before crediting.
 * Wallet failure is logged but never rethrows — review approval must not be blocked.
 */
class ReviewApprovedCreditWallet implements ObserverInterface
{
    private const CREDIT_AMOUNT = 50.00;

    /**
     * @var WalletBalanceServiceInterface
     */
    private $walletBalanceService;

    /**
     * @var WalletTransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param WalletBalanceServiceInterface $walletBalanceService
     * @param WalletTransactionRepositoryInterface $transactionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        WalletBalanceServiceInterface $walletBalanceService,
        WalletTransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->walletBalanceService = $walletBalanceService;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Review $review */
        $review = $observer->getEvent()->getObject();

        if (!$review) {
            return;
        }

        // Only act on transition TO approved — not on re-saves of already-approved reviews.
        if ((int)$review->getStatusId() !== Review::STATUS_APPROVED) {
            return;
        }
        if ((int)$review->getOrigData('status_id') === Review::STATUS_APPROVED) {
            return;
        }

        // Guest reviews have no customer to credit.
        $customerId = (int)$review->getCustomerId();
        if (!$customerId) {
            return;
        }

        $reviewId = (int)$review->getId();

        try {
            // Dedup: bail if we already credited this review.
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(WalletTransactionInterface::REFERENCE_TYPE, WalletTransactionInterface::REFERENCE_TYPE_REVIEW)
                ->addFilter(WalletTransactionInterface::REFERENCE_ID, $reviewId)
                ->create();

            $existingTransactions = $this->transactionRepository->getList($searchCriteria);

            if ($existingTransactions->getTotalCount() > 0) {
                $this->logger->info(sprintf(
                    'ReviewApprovedCreditWallet: skipping duplicate credit for review #%d (customer #%d)',
                    $reviewId,
                    $customerId
                ));
                return;
            }

            $this->walletBalanceService->updateBalanceAtomic(
                $customerId,
                self::CREDIT_AMOUNT,
                'add',
                sprintf('Review reward for product #%d', (int)$review->getEntityPkValue()),
                WalletTransactionInterface::REFERENCE_TYPE_REVIEW,
                $reviewId
            );

            $this->logger->info(sprintf(
                'ReviewApprovedCreditWallet: credited ₹%.2f to customer #%d for review #%d',
                self::CREDIT_AMOUNT,
                $customerId,
                $reviewId
            ));
        } catch (\Exception $e) {
            // Wallet failure must never block review approval.
            $this->logger->error(sprintf(
                'ReviewApprovedCreditWallet: failed to credit wallet for review #%d (customer #%d): %s',
                $reviewId,
                $customerId,
                $e->getMessage()
            ));
        }
    }
}
