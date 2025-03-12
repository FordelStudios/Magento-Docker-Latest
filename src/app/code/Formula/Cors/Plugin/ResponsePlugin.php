<?php
namespace Formula\Cors\Plugin;

class ResponsePlugin
{
    public function beforeSendResponse(\Magento\Framework\App\Response\Http $subject)
    {
        $subject->setHeader("Access-Control-Allow-Origin", "*");
        $subject->setHeader("Access-Control-Allow-Methods", "GET, PUT, POST, DELETE, OPTIONS");
        $subject->setHeader("Access-Control-Allow-Headers", "Authorization, Content-Type, X-Requested-With, X-CSRF-Token");
        $subject->setHeader("Access-Control-Allow-Credentials", "true");
        
        return null;
    }
}