<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Helper;

use Formula\Shadowfax\Helper\Data as HelperData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManager;

    /**
     * @var HelperData
     */
    private $helper;

    /**
     * @var MockObject|Context
     */
    private $context;

    /**
     * @var MockObject|ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var MockObject|EncryptorInterface
     */
    private $encryptor;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfig);

        $this->encryptor = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManagerHelper($this);
        $this->helper = $this->objectManager->getObject(
            HelperData::class,
            [
                'context' => $this->context,
                'encryptor' => $this->encryptor,
            ]
        );
    }

    public function testIsEnabledReturnsTrueWhenConfigFlagIsOn(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('shadowfax/general/enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('1');

        $this->assertTrue($this->helper->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenConfigFlagIsOff(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('shadowfax/general/enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('0');

        $this->assertFalse($this->helper->isEnabled());
    }

    public function testGetApiTokenReturnsDecryptedValue(): void
    {
        $encrypted = '0:2:someEncryptedBlobFromMagento==';
        $plaintext = 'sk_live_shadowfax_api_token';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('shadowfax/general/api_token', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($encrypted);

        $this->encryptor->expects($this->once())
            ->method('decrypt')
            ->with($encrypted)
            ->willReturn($plaintext);

        $this->assertSame($plaintext, $this->helper->getApiToken());
    }

    public function testGetApiTokenReturnsNullWhenNotConfigured(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('shadowfax/general/api_token', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(null);

        $this->encryptor->expects($this->never())->method('decrypt');

        $this->assertNull($this->helper->getApiToken());
    }

    public function testGetWebhookSecretReturnsDecryptedValue(): void
    {
        $encrypted = '0:2:anotherEncryptedBlob==';
        $plaintext = 'whsec_shadowfax_webhook_secret';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('shadowfax/general/webhook_secret', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($encrypted);

        $this->encryptor->expects($this->once())
            ->method('decrypt')
            ->with($encrypted)
            ->willReturn($plaintext);

        $this->assertSame($plaintext, $this->helper->getWebhookSecret());
    }
}
