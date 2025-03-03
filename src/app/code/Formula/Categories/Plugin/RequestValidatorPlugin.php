<?php
namespace Formula\Categories\Plugin;

use Magento\Webapi\Controller\Rest\RequestValidator;
use Magento\Framework\Webapi\Rest\Request;

class RequestValidatorPlugin
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Skip authentication validation for GET requests
     *
     * @param RequestValidator $subject
     * @param \Closure $proceed
     * @return void
     */
    public function aroundValidate(
        RequestValidator $subject,
        \Closure $proceed
    ) {
        // Skip authentication for all GET requests
        if ($this->request->getHttpMethod() === Request::HTTP_METHOD_GET) {
            return;
        }
        
        // For all other methods (POST, PUT, DELETE), proceed with normal validation
        return $proceed();
    }
}