<?php
namespace Formula\ResendConfirmation\Api;

interface ResendConfirmationInterface
{
    /**
     * Resend the email-confirmation link for a customer who hasn't activated yet.
     *
     * Returns true when Magento accepted the resend request. To avoid leaking
     * which addresses have accounts, "no such email" / "already confirmed" are
     * also reported as success — the storefront never reveals whether an
     * address is registered.
     *
     * @param string $email
     * @param int|null $websiteId Optional; defaults to the request's website.
     * @return bool
     */
    public function execute($email, $websiteId = null);
}
