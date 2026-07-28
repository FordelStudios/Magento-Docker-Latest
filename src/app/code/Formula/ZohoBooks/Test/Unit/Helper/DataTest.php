<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Test\Unit\Helper;

use Formula\ZohoBooks\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfig;

    /** @var EncryptorInterface|MockObject */
    private $encryptor;

    private Data $helper;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->encryptor = $this->createMock(EncryptorInterface::class);

        /** @var Context|MockObject $context */
        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($this->scopeConfig);

        $this->helper = new Data($context, $this->encryptor);
    }

    public function testGetClientSecretReturnsDecryptedValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('zohobooks/general/client_secret', 'store', null)
            ->willReturn('encrypted-secret');
        $this->encryptor->method('decrypt')->with('encrypted-secret')->willReturn('plain-secret');

        $this->assertSame('plain-secret', $this->helper->getClientSecret());
    }

    public function testGetClientSecretReturnsNullWhenNotConfigured(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('zohobooks/general/client_secret', 'store', null)
            ->willReturn(null);

        $this->assertNull($this->helper->getClientSecret());
    }

    public function testGetRefreshTokenReturnsDecryptedValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('zohobooks/general/refresh_token', 'store', null)
            ->willReturn('encrypted-token');
        $this->encryptor->method('decrypt')->with('encrypted-token')->willReturn('plain-token');

        $this->assertSame('plain-token', $this->helper->getRefreshToken());
    }

    public function testGetDefaultGstRateReturnsFloat(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('zohobooks/general/default_gst_rate', 'store', null)
            ->willReturn('18');

        $this->assertSame(18.0, $this->helper->getDefaultGstRate());
        $this->assertIsFloat($this->helper->getDefaultGstRate());
    }

    public function testGetDefaultGstRateFallsBackTo18WhenUnset(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('zohobooks/general/default_gst_rate', 'store', null)
            ->willReturn(null);

        $this->assertSame(18.0, $this->helper->getDefaultGstRate());
    }

    public function testIsEnabledCastsToBool(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('zohobooks/general/enabled', 'store', null)
            ->willReturn('1');

        $this->assertTrue($this->helper->isEnabled());
    }

    public function testIsDebugModeCastsToBool(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('zohobooks/general/debug_mode', 'store', null)
            ->willReturn('0');

        $this->assertFalse($this->helper->isDebugMode());
    }
}
