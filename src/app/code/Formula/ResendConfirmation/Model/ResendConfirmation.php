<?php
namespace Formula\ResendConfirmation\Model;

use Formula\ResendConfirmation\Api\ResendConfirmationInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Psr\Log\LoggerInterface;

class ResendConfirmation implements ResendConfirmationInterface
{
    /** @var AccountManagementInterface */
    private $accountManagement;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        AccountManagementInterface $accountManagement,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->accountManagement = $accountManagement;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($email, $websiteId = null)
    {
        $email = is_string($email) ? trim($email) : '';
        if ($email === '') {
            // Don't 400 — we want the storefront to never know which addresses
            // map to an account.
            return true;
        }

        if ($websiteId === null) {
            try {
                $websiteId = (int) $this->storeManager->getStore()->getWebsiteId();
            } catch (\Exception $e) {
                $websiteId = null;
            }
        }

        try {
            $this->accountManagement->resendConfirmation($email, (int) $websiteId);
        } catch (NoSuchEntityException $e) {
            // Silently treat "no account for that email" as success — same
            // user-enumeration defence as Magento's password-reset flow.
            $this->logger->info(
                sprintf('[ResendConfirmation] No account for %s — returning success.', $email)
            );
        } catch (InvalidTransitionException $e) {
            // Already-confirmed account; nothing to send. Still report true so
            // the UI doesn't leak the account state.
            $this->logger->info(
                sprintf('[ResendConfirmation] %s already confirmed.', $email)
            );
        } catch (\Exception $e) {
            $this->logger->error(
                '[ResendConfirmation] Unexpected error: ' . $e->getMessage(),
                ['email' => $email, 'exception' => $e]
            );
            // Re-throw so the storefront can surface a "try again" message —
            // only happens on genuine infrastructure issues.
            throw $e;
        }

        return true;
    }
}
