<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Test\Unit\Model\Gst;

use Formula\ZohoBooks\Model\Gst\StateCodeMapper;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class StateCodeMapperTest extends TestCase
{
    private StateCodeMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new StateCodeMapper();
    }

    /**
     * @dataProvider knownRegionCodeProvider
     */
    public function testResolveByRegionCode(string $regionCode, string $expectedGstCode): void
    {
        $this->assertSame($expectedGstCode, $this->mapper->resolve($regionCode, null));
    }

    public function knownRegionCodeProvider(): array
    {
        return [
            'Maharashtra' => ['MH', '27'],
            'Delhi' => ['DL', '07'],
            'Karnataka' => ['KA', '29'],
            'Haryana' => ['HR', '06'],
            'Tamil Nadu' => ['TN', '33'],
            'Uttar Pradesh' => ['UP', '09'],
            'Gujarat' => ['GJ', '24'],
            'Kerala' => ['KL', '32'],
            'West Bengal' => ['WB', '19'],
            'Telangana' => ['TG', '36'],
            'Andhra Pradesh' => ['AP', '37'],
            'Jammu and Kashmir single-digit-padded' => ['JK', '01'],
            'Ladakh' => ['LA', '38'],
        ];
    }

    /**
     * @dataProvider knownRegionNameProvider
     */
    public function testResolveByRegionName(string $regionName, string $expectedGstCode): void
    {
        $this->assertSame($expectedGstCode, $this->mapper->resolve(null, $regionName));
    }

    public function knownRegionNameProvider(): array
    {
        return [
            'Maharashtra' => ['Maharashtra', '27'],
            'Delhi' => ['Delhi', '07'],
            'Karnataka' => ['Karnataka', '29'],
        ];
    }

    public function testDadraAndNagarHaveliAndDamanAndDiuShareTheMergedUtCode(): void
    {
        $this->assertSame('26', $this->mapper->resolve('DN', null));
        $this->assertSame('26', $this->mapper->resolve('DD', null));
    }

    public function testRegionCodeIsCaseInsensitive(): void
    {
        $this->assertSame('27', $this->mapper->resolve('mh', null));
        $this->assertSame('27', $this->mapper->resolve('Mh', null));
    }

    public function testRegionNameIsCaseInsensitive(): void
    {
        $this->assertSame('27', $this->mapper->resolve(null, 'maharashtra'));
        $this->assertSame('27', $this->mapper->resolve(null, 'MAHARASHTRA'));
    }

    public function testRegionCodeIsTrimmedBeforeMatching(): void
    {
        $this->assertSame('27', $this->mapper->resolve(' MH ', null));
    }

    public function testRegionNameIsTrimmedBeforeMatching(): void
    {
        $this->assertSame('27', $this->mapper->resolve(null, ' Maharashtra '));
    }

    public function testRegionCodeTakesPrecedenceOverRegionNameWhenBothGiven(): void
    {
        // Code wins even if the name would resolve to a different state -
        // the code is the more reliable of the two identifiers.
        $this->assertSame('27', $this->mapper->resolve('MH', 'Delhi'));
    }

    public function testFallsBackToNameWhenCodeDoesNotMatch(): void
    {
        $this->assertSame('07', $this->mapper->resolve('', 'Delhi'));
    }

    public function testThrowsWhenRegionCodeIsUnknown(): void
    {
        $this->expectException(LocalizedException::class);

        $this->mapper->resolve('ZZ', null);
    }

    public function testThrowsWhenRegionNameIsUnknown(): void
    {
        $this->expectException(LocalizedException::class);

        $this->mapper->resolve(null, 'Narnia');
    }

    public function testThrowsWhenBothIdentifiersAreNull(): void
    {
        $this->expectException(LocalizedException::class);

        $this->mapper->resolve(null, null);
    }

    public function testThrowsWhenBothIdentifiersAreEmptyStrings(): void
    {
        $this->expectException(LocalizedException::class);

        $this->mapper->resolve('', '');
    }

    public function testNeverSilentlyDefaultsOnUnrecognizedInput(): void
    {
        // A malformed/unmapped region must never resolve to *some* code -
        // wrong state means wrong CGST/IGST split, which is a compliance bug.
        $this->expectException(LocalizedException::class);

        $this->mapper->resolve('Maharashtra', null); // full name passed as code param -> not a code match
    }
}
