<?php
namespace Formula\Wallet\Model;

use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Formula\Wallet\Api\Data\WalletTransactionInterfaceFactory;
use Formula\Wallet\Api\WalletTransactionRepositoryInterface;
use Formula\Wallet\Model\ResourceModel\WalletTransaction as TransactionResource;
use Formula\Wallet\Model\ResourceModel\WalletTransaction\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class WalletTransactionRepository implements WalletTransactionRepositoryInterface
{
    /**
     * @var TransactionResource
     */
    protected $resource;

    /**
     * @var WalletTransactionInterfaceFactory
     */
    protected $transactionFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @param TransactionResource $resource
     * @param WalletTransactionInterfaceFactory $transactionFactory
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        TransactionResource $resource,
        WalletTransactionInterfaceFactory $transactionFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->transactionFactory = $transactionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(WalletTransactionInterface $transaction)
    {
        try {
            $this->resource->save($transaction);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($transactionId)
    {
        $transaction = $this->transactionFactory->create();
        $this->resource->load($transaction, $transactionId);
        if (!$transaction->getTransactionId()) {
            throw new NoSuchEntityException(__('Transaction with id "%1" does not exist.', $transactionId));
        }
        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        // Convert items to array format for API serialization
        $transactionItems = [];
        foreach ($collection->getItems() as $item) {
            $transactionItems[] = [
                'transaction_id' => $item->getTransactionId(),
                'customer_id' => $item->getCustomerId(),
                'amount' => $item->getAmount(),
                'type' => $item->getType(),
                'balance_before' => $item->getBalanceBefore(),
                'balance_after' => $item->getBalanceAfter(),
                'description' => $item->getDescription(),
                'reference_type' => $item->getReferenceType(),
                'reference_id' => $item->getReferenceId(),
                'created_at' => $item->getCreatedAt(),
                'admin_user_id' => $item->getAdminUserId(),
                'admin_username' => $item->getAdminUsername()
            ];
        }

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($transactionItems);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(WalletTransactionInterface $transaction)
    {
        try {
            $this->resource->delete($transaction);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($transactionId)
    {
        return $this->delete($this->getById($transactionId));
    }

    /**
     * {@inheritdoc}
     */
    public function createTransaction(
        $customerId,
        $amount,
        $type,
        $balanceBefore,
        $balanceAfter,
        $description = null,
        $referenceType = null,
        $referenceId = null,
        $adminUserId = null,
        $adminUsername = null
    ) {
        $transaction = $this->transactionFactory->create();
        $transaction->setCustomerId($customerId);
        $transaction->setAmount(abs($amount));
        $transaction->setType($type);
        $transaction->setBalanceBefore($balanceBefore);
        $transaction->setBalanceAfter($balanceAfter);
        $transaction->setDescription($description);
        $transaction->setReferenceType($referenceType);
        $transaction->setReferenceId($referenceId);
        $transaction->setAdminUserId($adminUserId);
        $transaction->setAdminUsername($adminUsername);

        return $this->save($transaction);
    }
}
