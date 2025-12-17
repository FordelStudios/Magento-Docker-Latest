<?php
/**
 * Security Plugin: Prevent customers from manipulating wallet_amount_used via Cart API
 *
 * This plugin intercepts CartRepository::save() to ensure that customers cannot
 * directly set wallet_amount_used through PUT /V1/carts/mine or GraphQL mutations.
 * The legitimate wallet application must go through WalletManagement::applyWalletToCart()
 */
namespace Formula\Wallet\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\App\State;
use Magento\Framework\Authorization;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class CartRepositorySavePlugin
{
    /**
     * Registry key used to bypass this plugin during legitimate wallet operations
     */
    public const REGISTRY_KEY_WALLET_OPERATION = 'wallet_apply_in_progress';

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param State $appState
     * @param Authorization $authorization
     * @param ResourceConnection $resourceConnection
     * @param Registry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        State $appState,
        Authorization $authorization,
        ResourceConnection $resourceConnection,
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->appState = $appState;
        $this->authorization = $authorization;
        $this->resourceConnection = $resourceConnection;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * Intercept cart save to prevent wallet_amount_used manipulation
     *
     * @param CartRepositoryInterface $subject
     * @param CartInterface $cart
     * @return array
     */
    public function beforeSave(
        CartRepositoryInterface $subject,
        CartInterface $cart
    ) {
        try {
            // IMPORTANT: Skip this plugin if a legitimate wallet operation is in progress
            // This allows WalletManagement::applyWalletToCart() to set wallet amounts
            if ($this->registry->registry(self::REGISTRY_KEY_WALLET_OPERATION)) {
                return [$cart];
            }

            $areaCode = $this->appState->getAreaCode();

            // Only intercept customer API calls (REST and GraphQL)
            // Allow admin operations, frontend (checkout), cron, etc.
            $protectedAreas = ['webapi_rest', 'graphql'];

            if (!in_array($areaCode, $protectedAreas)) {
                return [$cart];
            }

            // Allow admin users to modify wallet amounts
            if ($this->authorization->isAllowed('Magento_Customer::manage')) {
                return [$cart];
            }

            // For customer API calls, preserve the original wallet_amount_used from database
            // This prevents customers from directly setting wallet amounts via cart update API
            $cartId = $cart->getId();

            if ($cartId) {
                $originalData = $this->getOriginalWalletAmounts((int)$cartId);

                if ($originalData !== null) {
                    // Restore original values - customer cannot override these via API
                    $cart->setWalletAmountUsed($originalData['wallet_amount_used']);
                    $cart->setBaseWalletAmountUsed($originalData['base_wallet_amount_used']);

                    // Also update extension attributes if they exist
                    $extensionAttributes = $cart->getExtensionAttributes();
                    if ($extensionAttributes) {
                        $extensionAttributes->setWalletAmountUsed($originalData['wallet_amount_used']);
                        $extensionAttributes->setBaseWalletAmountUsed($originalData['base_wallet_amount_used']);
                    }

                    $this->logger->debug('CartRepositorySavePlugin: Preserved wallet amounts', [
                        'cart_id' => $cartId,
                        'wallet_amount_used' => $originalData['wallet_amount_used'],
                        'base_wallet_amount_used' => $originalData['base_wallet_amount_used']
                    ]);
                }
            } else {
                // New cart - ensure wallet amounts start at 0
                $cart->setWalletAmountUsed(0);
                $cart->setBaseWalletAmountUsed(0);
            }

        } catch (\Exception $e) {
            // Log error but don't block cart save - just ensure wallet is zeroed for safety
            $this->logger->error('CartRepositorySavePlugin error: ' . $e->getMessage(), [
                'cart_id' => $cart->getId() ?? 'new'
            ]);

            // On error, zero out wallet amounts for safety
            $cart->setWalletAmountUsed(0);
            $cart->setBaseWalletAmountUsed(0);
        }

        return [$cart];
    }

    /**
     * Get original wallet amounts directly from database
     *
     * @param int $cartId
     * @return array|null
     */
    private function getOriginalWalletAmounts(int $cartId): ?array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('quote');

            $select = $connection->select()
                ->from($tableName, ['wallet_amount_used', 'base_wallet_amount_used'])
                ->where('entity_id = ?', $cartId);

            $result = $connection->fetchRow($select);

            if ($result) {
                return [
                    'wallet_amount_used' => (float)($result['wallet_amount_used'] ?? 0),
                    'base_wallet_amount_used' => (float)($result['base_wallet_amount_used'] ?? 0)
                ];
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Error fetching original wallet amounts: ' . $e->getMessage());
            return null;
        }
    }
}
