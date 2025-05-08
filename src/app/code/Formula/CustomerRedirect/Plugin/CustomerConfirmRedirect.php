<?php
namespace Formula\CustomerRedirect\Plugin;

use Magento\Customer\Controller\Account\Confirm;
use Magento\Framework\Controller\Result\Redirect;

class CustomerConfirmRedirect
{
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $redirectFactory;

    /**
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     */
    public function __construct(
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
    ) {
        $this->redirectFactory = $redirectFactory;
    }

    /**
     * After plugin for the execute method of the Customer Account Confirm controller
     *
     * @param Confirm $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(Confirm $subject, $result)
    {
        // Only intercept successful confirmation redirects
        if ($result instanceof Redirect) {
            // Create our own redirect
            $redirect = $this->redirectFactory->create();
            $redirect->setUrl('https://formula.yellowchalk.dev?verified=true');
            return $redirect;
        }

        return $result;
    }
}
