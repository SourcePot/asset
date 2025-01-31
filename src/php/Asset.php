<?php
/*
* @package asset
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2024 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit MIT
*/

declare(strict_types=1);

namespace SourcePot\Asset;

require_once('Rates.php');

final class Asset{

    private const DEFAULT_UNIT='EUR';
    private const DEFAULT_TIMEZONE='Europe/Berlin';
    private const UNIT_ALIAS=['£'=>'GBP','€'=>'EUR','AU$'=>'AUD','$'=>'USD','US$'=>'USD'];
    
    private $asset=array();
    
    function __construct(float $value=0,string $unit=self::DEFAULT_UNIT,\DateTime $dateTime=NULL)
    {
        $now=new \DateTime('now');
        $unit=$this->normalizeUnit($unit);
        $this->asset=['value'=>$value,'unit'=>$unit,'dateTime'=>$dateTime??$now];
    }

    private function normalizeUnit(string $unit):string{
        return self::UNIT_ALIAS[$unit]??$unit;
    }

    public function __toString()
    {
        return $this->asset['value'].' '.$this->asset['unit'].' ('.$this->asset['dateTime']->format('c').')';
    }

    public function getArray():array
    {
        $assetArr=['Currency'=>$this->asset['unit'],'Amount'=>$this->asset['value'],'ISO 8601'=>$this->asset['dateTime']->format('c'),'RFC 2822'=>$this->asset['dateTime']->format('r'),'Timestamp'=>$this->asset['dateTime']->getTimestamp()];
        $assetArr['Amount de']=str_replace('.',',',strval($assetArr['Amount']));
        $assetArr['Amount (US)']=$assetArr['Currency'].' '.number_format($assetArr['Amount'],2);
        $assetArr['Amount (DE)']=number_format($assetArr['Amount'],2,',','').' '.$assetArr['Currency'];
        $assetArr['Amount (DE full)']=number_format($assetArr['Amount'],2,',','.').' '.$assetArr['Currency'];
        $assetArr['Amount (FR)']=number_format($assetArr['Amount'],2,'.',' ').' '.$assetArr['Currency'];
        $assetArr['String']=$this->asset['string']??'';
        return $assetArr;
    }
    

}
?>