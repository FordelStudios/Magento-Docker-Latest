<?php declare(strict_types=1);


// File: Model/BulkCartDelete.php

namespace Formula\BulkCartDelete\Model;

use Formula\BulkCartDelete\Api\BulkCartDeleteInterface;
use Formula\BulkCartDelete\Api\Data\BulkDeleteResponseInterface;
use Formula\BulkCartDelete\Api\Data\BulkDeleteResponseInterfaceFactory;
use Formula\BulkCartDelete\Api\Data\FailedItemInterfaceFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;

class BulkCartDelete implements BulkCartDeleteInterface
{
    /**
     * @var FailedItemInterfaceFactory
     */
    private $failedItemFactory;


    /**
     * @var BulkDeleteResponseInterfaceFactory
     */
    private $responseFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CartItemRepositoryInterface
     */
    private $cartItemRepository;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param FailedItemInterfaceFactory $failedItemFactory
     * @param BulkDeleteResponseInterfaceFactory $responseFactory
     * @param CartRepositoryInterface $cartRepository
     * @param CartItemRepositoryInterface $cartItemRepository
     * @param Request $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        FailedItemInterfaceFactory $failedItemFactory,
        BulkDeleteResponseInterfaceFactory $responseFactory,
        CartRepositoryInterface $cartRepository,
        CartItemRepositoryInterface $cartItemRepository,
        Request $request,
        LoggerInterface $logger
    ) {
        $this->failedItemFactory = $failedItemFactory;
        $this->responseFactory = $responseFactory;
        $this->cartRepository = $cartRepository;
        $this->cartItemRepository = $cartItemRepository;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Delete multiple cart items for logged-in customer
     *
     * @return BulkDeleteResponseInterface
     * @throws LocalizedException
     */
    public function deleteCustomerCartItems(): BulkDeleteResponseInterface
    {
        $startTime = microtime(true);
        
        // Get customer ID from token context
        $customerId = $this->getCustomerIdFromToken();
        
        // Get request body data
        $requestData = $this->getRequestData();
        
        // Parse and validate request data
        $data = $this->parseRequestData($requestData);
        
        try {
            // Get customer's active cart
            $cart = $this->cartRepository->getActiveForCustomer($customerId);
            
            if ($data['delete_all']) {
                return $this->deleteAllItems($cart, $startTime);
            } else {
                return $this->deleteSpecificItems($cart, $data['item_ids'], $startTime);
            }
            
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('No active cart found for customer'));
        } catch (\Exception $e) {
            $this->logger->error('Bulk cart delete error: ' . $e->getMessage());
            throw new LocalizedException(__('An error occurred while deleting cart items: %1', $e->getMessage()));
        }
    }

    /**
     * Delete multiple cart items for guest cart
     *
     * @param string $cartId
     * @return BulkDeleteResponseInterface
     * @throws LocalizedException
     */
    public function deleteGuestCartItems(string $cartId): BulkDeleteResponseInterface
    {
        $startTime = microtime(true);
        
        // Get request body data
        $requestData = $this->getRequestData();
        
        // Parse and validate request data
        $data = $this->parseRequestData($requestData);
        
        try {
            // Get guest cart by ID
            $cart = $this->cartRepository->get($cartId);
            
            if ($data['delete_all']) {
                return $this->deleteAllItems($cart, $startTime);
            } else {
                return $this->deleteSpecificItems($cart, $data['item_ids'], $startTime);
            }
            
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('Cart not found with ID: %1', $cartId));
        } catch (\Exception $e) {
            $this->logger->error('Guest bulk cart delete error: ' . $e->getMessage());
            throw new LocalizedException(__('An error occurred while deleting cart items: %1', $e->getMessage()));
        }
    }

    /**
     * Get customer ID from token context
     *
     * @return int
     * @throws LocalizedException
     */
    private function getCustomerIdFromToken(): int
    {
        $customerId = 0;
            
        try {
            // This gets the customer ID from the API authentication token context
            $context = \Magento\Framework\App\ObjectManager::getInstance()
                 ->get(\Magento\Authorization\Model\UserContextInterface::class);
                
            if ($context->getUserType() == \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER) {
                $customerId = $context->getUserId();
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to identify customer from token'));
        }
            
        if (!$customerId) {
            throw new LocalizedException(__('Customer not authenticated'));
        }

        return (int)$customerId;
    }

    /**
     * Get request body data
     *
     * @return array
     * @throws LocalizedException
     */
    private function getRequestData(): array
    {
        try {
            $bodyParams = $this->request->getBodyParams();
            
            if (empty($bodyParams)) {
                // Try to get raw body content
                $rawBody = $this->request->getContent();
                if ($rawBody) {
                    $bodyParams = json_decode($rawBody, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new LocalizedException(__('Invalid JSON in request body'));
                    }
                }
            }
            
            return $bodyParams ?: [];
            
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to parse request data: %1', $e->getMessage()));
        }
    }

    /**
     * Parse and validate request data
     *
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    private function parseRequestData(array $data): array
    {
        // Check for itemIds format
        if (isset($data['itemIds']) && is_array($data['itemIds'])) {
            $itemIds = array_map('intval', $data['itemIds']);
            return [
                'delete_all' => false,
                'item_ids' => $itemIds
            ];
        }

        // Check for deleteAll format
        if (isset($data['deleteAll']) && $data['deleteAll'] === true) {
            return [
                'delete_all' => true,
                'item_ids' => []
            ];
        }

        // Check for alternative formats (backward compatibility)
        if (isset($data['item_ids']) && is_array($data['item_ids'])) {
            $itemIds = array_map('intval', $data['item_ids']);
            return [
                'delete_all' => false,
                'item_ids' => $itemIds
            ];
        }

        if (isset($data['delete_all']) && $data['delete_all'] === true) {
            return [
                'delete_all' => true,
                'item_ids' => []
            ];
        }

        throw new LocalizedException(__('Either "deleteAll" must be true or "itemIds" must be provided'));
    }

    /**
     * Delete all items from cart
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param float $startTime
     * @return BulkDeleteResponseInterface
     */
    private function deleteAllItems($cart, float $startTime): BulkDeleteResponseInterface
    {
        $allItems = $cart->getAllVisibleItems();
        $totalItems = count($allItems);
        
        $response = $this->responseFactory->create();
        
        if ($totalItems === 0) {
            return $response
                ->setSuccess(true)
                ->setMessage('Cart is already empty')
                ->setTotalRequested(0)
                ->setSuccessfullyDeleted(0)
                ->setFailed(0)
                ->setFailedItems([])
                ->setExecutionTimeMs(round((microtime(true) - $startTime) * 1000, 2));
        }

        $successCount = 0;
        $failedItems = [];

        foreach ($allItems as $item) {
            try {
                $this->cartItemRepository->deleteById($cart->getId(), $item->getItemId());
                $successCount++;
            } catch (\Exception $e) {
                $failedItem = $this->failedItemFactory->create();
                $failedItem->setItemId($item->getItemId())
                          ->setError($e->getMessage());
                $failedItems[] = $failedItem;
                $this->logger->warning('Failed to delete item: ' . $item->getItemId() . ' - ' . $e->getMessage());
            }
        }

        return $response
            ->setSuccess($successCount > 0)
            ->setMessage($successCount === $totalItems ? 'All items deleted successfully' : 'Partial deletion completed')
            ->setTotalRequested($totalItems)
            ->setSuccessfullyDeleted($successCount)
            ->setFailed(count($failedItems))
            ->setFailedItems($failedItems)
            ->setExecutionTimeMs(round((microtime(true) - $startTime) * 1000, 2));
    }

    /**
     * Delete specific items from cart
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param array $itemIds
     * @param float $startTime
     * @return BulkDeleteResponseInterface
     */
    private function deleteSpecificItems($cart, array $itemIds, float $startTime): BulkDeleteResponseInterface
    {
        $totalRequested = count($itemIds);
        $successCount = 0;
        $failedItems = [];

        $response = $this->responseFactory->create();

        // Get existing cart items for validation
        $existingItems = [];
        foreach ($cart->getAllVisibleItems() as $item) {
            $existingItems[$item->getItemId()] = $item;
        }

        foreach ($itemIds as $itemId) {
            try {
                // Check if item exists in cart
                if (!isset($existingItems[$itemId])) {
                    $failedItem = $this->failedItemFactory->create();
                    $failedItem->setItemId($itemId)
                              ->setError('Item not found in cart');
                    $failedItems[] = $failedItem;
                    continue;
                }

                $this->cartItemRepository->deleteById($cart->getId(), $itemId);
                $successCount++;
                
            } catch (\Exception $e) {
                $failedItem = $this->failedItemFactory->create();
                $failedItem->setItemId($itemId)
                          ->setError($e->getMessage());
                $failedItems[] = $failedItem;
                $this->logger->warning('Failed to delete item: ' . $itemId . ' - ' . $e->getMessage());
            }
        }

        return $response
            ->setSuccess($successCount > 0)
            ->setMessage($successCount === $totalRequested ? 'All requested items deleted successfully' : 'Partial deletion completed')
            ->setTotalRequested($totalRequested)
            ->setSuccessfullyDeleted($successCount)
            ->setFailed(count($failedItems))
            ->setFailedItems($failedItems)
            ->setExecutionTimeMs(round((microtime(true) - $startTime) * 1000, 2));
    }
}