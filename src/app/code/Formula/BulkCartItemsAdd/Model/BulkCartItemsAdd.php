<?php
declare(strict_types=1);

namespace Formula\BulkCartItemsAdd\Model;

use Formula\BulkCartItemsAdd\Api\BulkCartItemsAddInterface;
use Formula\BulkCartItemsAdd\Api\Data\BulkAddResponseInterface;
use Formula\BulkCartItemsAdd\Api\Data\BulkAddResponseInterfaceFactory;
use Formula\BulkCartItemsAdd\Api\Data\FailedItemInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Psr\Log\LoggerInterface;

class BulkCartItemsAdd implements BulkCartItemsAddInterface
{
    private FailedItemInterfaceFactory $failedItemFactory;
    private BulkAddResponseInterfaceFactory $responseFactory;
    private CartRepositoryInterface $cartRepository;
    private CartItemRepositoryInterface $cartItemRepository;
    private CartItemInterfaceFactory $cartItemFactory;
    private ProductRepositoryInterface $productRepository;
    private Request $request;
    private LoggerInterface $logger;

    public function __construct(
        FailedItemInterfaceFactory $failedItemFactory,
        BulkAddResponseInterfaceFactory $responseFactory,
        CartRepositoryInterface $cartRepository,
        CartItemRepositoryInterface $cartItemRepository,
        CartItemInterfaceFactory $cartItemFactory,
        ProductRepositoryInterface $productRepository,
        Request $request,
        LoggerInterface $logger
    ) {
        $this->failedItemFactory = $failedItemFactory;
        $this->responseFactory = $responseFactory;
        $this->cartRepository = $cartRepository;
        $this->cartItemRepository = $cartItemRepository;
        $this->cartItemFactory = $cartItemFactory;
        $this->productRepository = $productRepository;
        $this->request = $request;
        $this->logger = $logger;
    }

    public function addCustomerCartItems(): BulkAddResponseInterface
    {
        $startTime = microtime(true);
        $customerId = $this->getCustomerIdFromToken();
        $requestData = $this->getRequestData();
        $items = $this->parseRequestData($requestData);

        try {
            $cart = $this->cartRepository->getActiveForCustomer($customerId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('No active cart found for customer'));
        }

        $successCount = 0;
        $failedItems = [];
        $totalRequested = count($items);

        foreach ($items as $item) {
            $sku = isset($item['sku']) ? (string)$item['sku'] : '';
            $qty = isset($item['qty']) ? (float)$item['qty'] : 0.0;

            if ($sku === '' || $qty <= 0) {
                $failedItem = $this->failedItemFactory->create();
                $failedItem->setSku($sku)->setError('Invalid sku or qty');
                $failedItems[] = $failedItem;
                continue;
            }

            try {
                $product = $this->productRepository->get($sku);
                $cartItem = $this->cartItemFactory->create();
                $cartItem->setQuoteId((int)$cart->getId());
                $cartItem->setSku($product->getSku());
                $cartItem->setQty($qty);

                $this->cartItemRepository->save($cartItem);
                $successCount++;
            } catch (NoSuchEntityException $e) {
                $failedItem = $this->failedItemFactory->create();
                $failedItem->setSku($sku)->setError('Product not found');
                $failedItems[] = $failedItem;
            } catch (\Exception $e) {
                $failedItem = $this->failedItemFactory->create();
                $failedItem->setSku($sku)->setError($e->getMessage());
                $failedItems[] = $failedItem;
                $this->logger->warning('Failed to add item: ' . $sku . ' - ' . $e->getMessage());
            }
        }

        $response = $this->responseFactory->create();
        return $response
            ->setSuccess($successCount > 0)
            ->setMessage($successCount === $totalRequested ? 'All requested items added successfully' : 'Partial add completed')
            ->setTotalRequested($totalRequested)
            ->setSuccessfullyAdded($successCount)
            ->setFailed(count($failedItems))
            ->setFailedItems($failedItems)
            ->setExecutionTimeMs(round((microtime(true) - $startTime) * 1000, 2));
    }

    private function getCustomerIdFromToken(): int
    {
        $customerId = 0;

        try {
            $context = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Authorization\Model\UserContextInterface::class);

            if ($context->getUserType() === \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER) {
                $customerId = (int)$context->getUserId();
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to identify customer from token'));
        }

        if ($customerId <= 0) {
            throw new LocalizedException(__('Customer not authenticated'));
        }

        return $customerId;
    }

    private function getRequestData(): array
    {
        try {
            $bodyParams = $this->request->getBodyParams();

            if (empty($bodyParams)) {
                $rawBody = $this->request->getContent();
                if ($rawBody) {
                    $bodyParams = json_decode($rawBody, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new LocalizedException(__('Invalid JSON in request body'));
                    }
                }
            }

            return is_array($bodyParams) ? $bodyParams : [];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to parse request data: %1', $e->getMessage()));
        }
    }

    private function parseRequestData(array $data): array
    {
        if (isset($data['cartItems']) && is_array($data['cartItems'])) {
            return array_values($data['cartItems']);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            return array_values($data['items']);
        }

        throw new LocalizedException(__('Field "cartItems" must be provided as an array of objects with "sku" and "qty"'));
    }
}
