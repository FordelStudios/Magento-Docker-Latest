<?php
// Create this as test_mail.php in your Magento root
require_once 'app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$transportBuilder = $obj->get('Magento\Framework\Mail\Template\TransportBuilder');
$transport = $transportBuilder
    ->setTemplateIdentifier('email_reset')
    ->setTemplateOptions(['area' => 'frontend', 'store' => 1])
    ->setTemplateVars([])
    ->setFrom(['email' => 'souravdas.fordelstudios@gmail.com', 'name' => 'Test Sender'])
    ->addTo('abhishek@fordelstudios.com')
    ->getTransport();

try {
    $transport->sendMessage();
    echo "Message sent successfully\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}