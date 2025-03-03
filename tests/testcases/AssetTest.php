<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AssetTest extends TestCase
{
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