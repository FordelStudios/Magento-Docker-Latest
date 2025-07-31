<?php
/**
 * User Quiz Repository
 */
declare(strict_types=1);

namespace Formula\UserQuiz\Model;

use Formula\UserQuiz\Api\Data\UserQuizInterface;
use Formula\UserQuiz\Api\UserQuizRepositoryInterface;
use Formula\UserQuiz\Model\ResourceModel\UserQuiz as ResourceUserQuiz;
use Formula\UserQuiz\Model\ResourceModel\UserQuiz\CollectionFactory as UserQuizCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaBuilder;

class UserQuizRepository implements UserQuizRepositoryInterface
{
    /**
     * @var ResourceUserQuiz
     */
    protected $resource;

    /**
     * @var UserQuizFactory
     */
    protected $userQuizFactory;

    /**
     * @var UserQuizCollectionFactory
     */
    protected $userQuizCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param ResourceUserQuiz $resource
     * @param UserQuizFactory $userQuizFactory
     * @param UserQuizCollectionFactory $userQuizCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        ResourceUserQuiz $resource,
        UserQuizFactory $userQuizFactory,
        UserQuizCollectionFactory $userQuizCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->resource = $resource;
        $this->userQuizFactory = $userQuizFactory;
        $this->userQuizCollectionFactory = $userQuizCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritdoc
     */
    public function save(UserQuizInterface $userQuiz)
    {
        try {
            // For REST API with token, the customerId comes from the token context
            $customerId = 0;
            
            if ($this->customerSession->isLoggedIn()) {
                // Customer is logged in through session
                $customerId = $this->customerSession->getCustomerId();
            } else {
                // Check if we have a valid token-based customer context
                $currentCustomerId = 0;
                
                try {
                    // This gets the customer ID from the API authentication token context
                    $context = \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Magento\Authorization\Model\UserContextInterface::class);
                    
                    if ($context->getUserType() == \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER) {
                        $currentCustomerId = $context->getUserId();
                    }
                } catch (\Exception $e) {
                    // Context not available or not a customer context
                }
                
                if ($currentCustomerId) {
                    $customerId = $currentCustomerId;
                }
            }
            
            // Set the customer ID if it's available
            if ($customerId) {
                $userQuiz->setCustomerId($customerId);
            }
            
            // Make sure customer ID is set
            if (!$userQuiz->getCustomerId()) {
                throw new LocalizedException(
                    __('Customer must be logged in to save quiz answers')
                );
            }
            
            // Make sure question_id is an integer and is set
            if ($userQuiz->getQuestionId() === null || $userQuiz->getQuestionId() === '') {
                throw new LocalizedException(
                    __('Question ID is required')
                );
            }
            
            $userQuiz->setQuestionId((int)$userQuiz->getQuestionId());
            
            // Make sure chosen_option_ids is set
            if ($userQuiz->getChosenOptionIds() === null || $userQuiz->getChosenOptionIds() === '') {
                throw new LocalizedException(
                    __('Chosen option IDs are required')
                );
            }

            // Check if this question has already been answered by this customer
            if (!$userQuiz->getEntityId()) {
                $collection = $this->userQuizCollectionFactory->create();
                $collection->addFieldToFilter('customer_id', $userQuiz->getCustomerId());
                
                if ($userQuiz->getQuestionId()) {
                    $collection->addFieldToFilter('question_id', $userQuiz->getQuestionId());
                    
                    if ($collection->getSize() > 0) {
                        // If found, update the existing entry instead of creating a new one
                        $existingItem = $collection->getFirstItem();
                        $userQuiz->setEntityId($existingItem->getEntityId());
                    }
                }
            }
            
            $this->resource->save($userQuiz);
            
            // Reload to ensure we have all data
            $savedUserQuiz = $this->getById($userQuiz->getEntityId());
            
            return $savedUserQuiz;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the user quiz: %1',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function getById($entityId)
    {
        $userQuiz = $this->userQuizFactory->create();
        $this->resource->load($userQuiz, $entityId);
        if (!$userQuiz->getId()) {
            throw new NoSuchEntityException(__('User Quiz with id "%1" does not exist.', $entityId));
        }
        
        // Security check - only allow access to the customer's own data
        $customerId = $this->getCurrentCustomerId();
        if ($customerId && $userQuiz->getCustomerId() != $customerId) {
            throw new LocalizedException(__('You do not have permission to access this quiz'));
        }
        
        // Ensure data is properly set
        $userQuiz->setQuestionId((int)$userQuiz->getQuestionId());
        
        return $userQuiz;
    }

    /**
     * @inheritdoc
     */
    public function getByCustomerId($customerId)
    {
        // Security check - if customerId is provided, make sure it matches the current customer
        $currentCustomerId = $this->getCurrentCustomerId();
        if ($currentCustomerId && $customerId != $currentCustomerId) {
            $customerId = $currentCustomerId; // Override with current customer ID for security
        }
        
        $collection = $this->userQuizCollectionFactory->create();
        $collection->addCustomerFilter($customerId);
        
        $items = [];
        foreach ($collection->getItems() as $item) {
            // Convert model to proper array to ensure all data is included
            $itemData = [
                'entity_id' => $item->getEntityId(),
                'customer_id' => $item->getCustomerId(),
                'question_id' => $item->getQuestionId(),
                'chosen_option_ids' => $item->getChosenOptionIds(),
                'created_at' => $item->getCreatedAt(),
                'updated_at' => $item->getUpdatedAt()
            ];
            $items[] = $itemData;
        }
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->userQuizCollectionFactory->create();
        
        // Security filter - only allow access to the customer's own data
        $customerId = $this->getCurrentCustomerId();
        if ($customerId) {
            $collection->addFieldToFilter('customer_id', $customerId);
        }
        
        $this->collectionProcessor->process($searchCriteria, $collection);
        
        $items = [];
        foreach ($collection->getItems() as $item) {
            // Convert model to proper array to ensure all data is included
            $itemData = [
                'entity_id' => $item->getEntityId(),
                'customer_id' => $item->getCustomerId(),
                'question_id' => $item->getQuestionId(),
                'chosen_option_ids' => $item->getChosenOptionIds(),
                'created_at' => $item->getCreatedAt(),
                'updated_at' => $item->getUpdatedAt()
            ];
            $items[] = $itemData;
        }
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(UserQuizInterface $userQuiz)
    {
        try {
            // Security check - only allow deletion of the customer's own data
            $customerId = $this->getCurrentCustomerId();
            if ($customerId && $userQuiz->getCustomerId() != $customerId) {
                throw new LocalizedException(__('You do not have permission to delete this quiz'));
            }
            
            $this->resource->delete($userQuiz);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the User Quiz: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($entityId)
    {
        return $this->delete($this->getById($entityId));
    }
    
    /**
     * Get current customer ID from session or API context
     *
     * @return int|null
     */
    private function getCurrentCustomerId()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomerId();
        }
        
        try {
            $context = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Authorization\Model\UserContextInterface::class);
            
            if ($context->getUserType() == \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER) {
                return $context->getUserId();
            }
        } catch (\Exception $e) {
            // Context not available or not a customer context
        }
        
        return null;
    }
}