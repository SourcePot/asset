<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AssetTest extends TestCase
{
    public function testSetPreservesZeroAndNormalizesCurrencySymbols(): void
    {
        $asset=new \SourcePot\Asset\Asset(0,'EUR');
        $this->assertSame(0.0,$asset->get('value'));
        $this->assertArrayNotHasKey('Warning',$asset->getArray());

        $asset=new \SourcePot\Asset\Asset(12,'US$');
        $this->assertSame('USD',$asset->get('unit'));
        $this->assertSame(12.0,$asset->get('value'));
    }

    public function testSetFromStringRejectsMissingNumber(): void
    {
        $asset=new \SourcePot\Asset\Asset();
        $asset->setFromString('hello');
        $this->assertFalse($asset->get('isValid'));
        $this->assertSame('E103: No numeric value found',$asset->get('Error'));
    }

    public function testInterestUsesRequestedInterval(): void
    {
        $asset=new \SourcePot\Asset\Asset(100,'EUR',new \DateTime('2026-11-21'));
        $steps=$asset->addIntrest('P1M',2,10);
        $this->assertSame('2027-01-21',$asset->get('dateTime')->format('Y-m-d'));
        $this->assertSame(121.0,$steps[2]['value']);
    }

    public function testMortgageRejectsBelowInterestPayment(): void
    {
        $result=(new \SourcePot\Asset\Asset())->fixedRateMortgage(1000,12,10,2);
        $this->assertSame('E102: Repayment below interest',$result['Error']);
    }

    public function testMortgagePaysOffWithFinalPayment(): void
    {
        $result=(new \SourcePot\Asset\Asset())->fixedRateMortgage(1000,12,100,2);
        $this->assertArrayNotHasKey('Error',$result);
        $this->assertEqualsWithDelta(12.68,(float)$result['Effective yearly interest'],0.001);
        $this->assertEqualsWithDelta(0.92,(float)$result['Years'],0.001);
    }

    public function testDateParser(): void
    {
        $testArr=['3456 EUR'=>['value'=>3456.0,'unit'=>'EUR'],
                '4,567.89 USD'=>['value'=>4567.89,'unit'=>'USD'],
                '5 678,90 US$'=>['value'=>5678.90,'unit'=>'USD'],
                '12,34 €'=>['value'=>12.34,'unit'=>'EUR'],
                '12.34 $'=>['value'=>12.34,'unit'=>'USD'],
                '12,345 $'=>['value'=>12345.0,'unit'=>'USD'],
                '1234e-3'=>['value'=>1.234,'unit'=>'EUR'],
                '.4e+3'=>['value'=>400.0,'unit'=>'EUR'],
                '.987e3'=>['value'=>987.0,'unit'=>'EUR'],
                ];

        $assetObj=new \SourcePot\Asset\Asset();
        
        foreach($testArr as $testcase=>$shouldBeArr){
            $asset=$assetObj->guessAssetFromString($testcase);
            $this->assertSame($shouldBeArr['value'],$asset['value']);
            $this->assertSame($shouldBeArr['unit'],$asset['unit']);
        }
    }

}