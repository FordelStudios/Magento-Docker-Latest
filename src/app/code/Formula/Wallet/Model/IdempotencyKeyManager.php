<?php
/**
 * Idempotency Key Manager
 *
 * Prevents duplicate wallet operations by tracking idempotency keys in cache.
 * When a client sends the same idempotency key within the TTL window,
 * the duplicate request is rejected.
 *
 * Usage:
 * - Client sends X-Idempotency-Key header with unique request ID
 * - Server checks if key was already processed
 * - If processed, returns cached response
 * - If new, processes request and caches the key
 */
namespace Formula\Wallet\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class IdempotencyKeyManager
{
    private const CACHE_TAG = 'wallet_idempotency';
    private const CACHE_KEY_PREFIX = 'wallet_idempotency_';
    private const CONFIG_PATH_ENABLED = 'formula_wallet/security/enable_idempotency';
    private const CONFIG_PATH_TTL = 'formula_wallet/security/idempotency_ttl';
    private const DEFAULT_TTL = 3600; // 1 hour

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CacheInterface $cache
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        CacheInterface $cache,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * Check if idempotency checking is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::CONFIG_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the TTL for idempotency keys
     *
     * @return int
     */
    public function getTtl(): int
    {
        $ttl = $this->scopeConfig->getValue(
            self::CONFIG_PATH_TTL,
            ScopeInterface::SCOPE_STORE
        );

        return $ttl !== null ? (int)$ttl : self::DEFAULT_TTL;
    }

    /**
     * Check if a key has already been processed
     *
     * @param string $key The idempotency key
     * @param int $customerId The customer ID
     * @param string $operation The operation type (e.g., 'wallet_apply', 'wallet_debit')
     * @return array|null Returns cached result if key exists, null otherwise
     */
    public function checkKey(string $key, int $customerId, string $operation): ?array
    {
        if (!$this->isEnabled() || empty($key)) {
            return null;
        }

        $cacheKey = $this->buildCacheKey($key, $customerId, $operation);
        $cachedData = $this->cache->load($cacheKey);

        if ($cachedData) {
            try {
                $result = $this->serializer->unserialize($cachedData);
                $this->logger->info('Idempotency key found, returning cached result', [
                    'key' => $key,
                    'customer_id' => $customerId,
                    'operation' => $operation
                ]);
                return $result;
            } catch (\Exception $e) {
                $this->logger->error('Failed to deserialize cached idempotency result', [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return null;
    }

    /**
     * Mark a key as processed and store the result
     *
     * @param string $key The idempotency key
     * @param int $customerId The customer ID
     * @param string $operation The operation type
     * @param array $result The operation result to cache
     * @return bool
     */
    public function markKeyProcessed(string $key, int $customerId, string $operation, array $result): bool
    {
        if (!$this->isEnabled() || empty($key)) {
            return false;
        }

        try {
            $cacheKey = $this->buildCacheKey($key, $customerId, $operation);
            $serializedResult = $this->serializer->serialize($result);
            $ttl = $this->getTtl();

            $this->cache->save(
                $serializedResult,
                $cacheKey,
                [self::CACHE_TAG],
                $ttl
            );

            $this->logger->info('Idempotency key marked as processed', [
                'key' => $key,
                'customer_id' => $customerId,
                'operation' => $operation,
                'ttl' => $ttl
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache idempotency key', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check key and throw exception if duplicate
     *
     * Convenience method that checks the key and throws if it's a duplicate.
     * Returns null if the key is new (operation should proceed).
     *
     * @param string $key The idempotency key
     * @param int $customerId The customer ID
     * @param string $operation The operation type
     * @return array|null Returns cached result if duplicate, null if new
     * @throws LocalizedException If duplicate and configured to throw
     */
    public function checkOrThrow(string $key, int $customerId, string $operation): ?array
    {
        $cachedResult = $this->checkKey($key, $customerId, $operation);

        if ($cachedResult !== null) {
            $this->logger->warning('Duplicate wallet operation detected', [
                'key' => $key,
                'customer_id' => $customerId,
                'operation' => $operation
            ]);
            return $cachedResult;
        }

        return null;
    }

    /**
     * Build the cache key
     *
     * @param string $key The idempotency key
     * @param int $customerId The customer ID
     * @param string $operation The operation type
     * @return string
     */
    private function buildCacheKey(string $key, int $customerId, string $operation): string
    {
        // Create a unique cache key based on the idempotency key, customer, and operation
        $combined = sprintf('%s_%d_%s', $key, $customerId, $operation);
        return self::CACHE_KEY_PREFIX . hash('sha256', $combined);
    }

    /**
     * Clear all idempotency keys (for testing/admin purposes)
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        try {
            $this->cache->clean([self::CACHE_TAG]);
            $this->logger->info('All idempotency keys cleared');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear idempotency keys', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
