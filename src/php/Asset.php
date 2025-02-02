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
    private const DEFAULT_DECIMALS=2;
    private const UNIT_ALIAS=['£'=>'GBP','€'=>'EUR','AU$'=>'AUD','$'=>'USD','US$'=>'USD'];
    private const DECIMALS=['XPF'=>0,'XAF'=>0,'VUV'=>0,'UGX'=>0,'TND'=>3,'RWF'=>0,'OMR'=>3,'PYG'=>0,'LYD'=>3,'KRW'=>0,'KWD'=>3,'KMF'=>0,'JPY'=>0,'JOD'=>3,'IQD'=>3,'IDR'=>0,'GNF'=>0,'DJF'=>0,'CVE'=>0,'BHD'=>3];
    private $currencies=[];

    private $asset=array();
    
    function __construct(float $value=0,string $unit=self::DEFAULT_UNIT,\DateTime $dateTime=NULL)
    {
        $now=new \DateTime('now');
        $unit=$this->normalizeUnit($unit);
        $this->asset=['value'=>$value,'unit'=>$unit,'dateTime'=>$dateTime??$now];
        // add currencies
        $rates=new Rates();
        $this->currencies=$rates->getCurrencies();
        $this->asset['Currency']=$this->currencies[$unit]??'';
    }

    final public function guessAssetFromString(string $string,string $unit=self::DEFAULT_UNIT,\DateTime $dateTime=NULL)
    {
        $now=new \DateTime('now');
        $unit=$this->normalizeUnit($unit);
        $newAsset=['unit'=>'','string'=>$string,'dateTime'=>$dateTime??$now];
        // detect currency
        $currencyStr=preg_replace('/[0-9,.+-]+/','',$string);
        if (!empty($currencyStr)){
            foreach($this->currencies as $code=>$name){
                if (stripos($currencyStr,$code)===FALSE){continue;}
                $newAsset['unit']=$code;
                $string=str_replace($code,'',$string);
                break;
            }
            if (empty($newAsset['unit'])){
                foreach(self::UNIT_ALIAS as $needle=>$code){
                    if (stripos($currencyStr,$needle)===FALSE){continue;}
                    $newAsset['unit']=$code;
                    $string=str_replace($needle,'',$string);
                    break;
                }        
            }
        }
        $newAsset['unit']=(empty($newAsset['unit']))?$unit:$newAsset['unit'];
        // recover value
        $numberStr=preg_replace('/[^0-9,.+-eE]+/','',$string);
        $chrArr=count_chars($numberStr,1);
        if (($chrArr[44]??0)>1){$numberStr=str_replace(',','',$numberStr);}
        if (($chrArr[46]??0)>1){$numberStr=str_replace('.','',$numberStr);}
        $commaPos=strrpos($numberStr,',');
        $dotPos=strrpos($numberStr,'.');
        // 10,000 -> 10000 If the value has an ambiguous structure, English format is assumed 
        if ($commaPos!==FALSE && $dotPos===FALSE){
            $commaChunks=explode(',',$numberStr);
            if (strlen($commaChunks[0])<3 && strlen($commaChunks[1])===3){
                $numberStr=str_replace(',','',$numberStr);
                $commaPos=FALSE;
            }
        }
        if ($commaPos!==FALSE && $dotPos!==FALSE){
            if ($commaPos>$dotPos){
                // 1.000,00 -> 1000.00
                $numberStr=str_replace('.','',$numberStr);
                $numberStr=str_replace(',','.',$numberStr);
            } else {
                // 1,000.00 -> 1000.00
                $numberStr=str_replace(',','',$numberStr);
            }
        } else if ($commaPos!==FALSE){
            // 1,000 -> 1.000
            $numberStr=str_replace(',','.',$numberStr);
        }
        $newAsset['value']=floatval($numberStr);
        $newAsset['Currency']=$this->currencies[$newAsset['unit']]??'';
        $this->asset=$newAsset;
    }

    private function normalizeUnit(string $unit):string{
        $unit=strtoupper($unit);
        return self::UNIT_ALIAS[$unit]??$unit;
    }

    public function __toString()
    {
        $decimals=(isset(self::DECIMALS[$this->asset['unit']]))?self::DECIMALS[$this->asset['unit']]:self::DEFAULT_DECIMALS;
        return round($this->asset['value'],$decimals).' '.$this->asset['unit'].' ('.$this->asset['dateTime']->format('c').')';
    }

    final public function setValue(float $value)
    {
        $this->asset['value']=$value;
    }

    final public function setDateTime(\DateTime $dateTime)
    {
        $this->asset['dateTime']=$dateTime;
    }

    final public function setUnit(string $unit)
    {
        $unit=$this->normalizeUnit($unit);
        if ($unit!==$this->asset['unit']){
            $errors=$warnings=[];
            $rates=new Rates();
            $targetRate=$rates->getRate($this->asset['dateTime'],$unit);
            $sourceRate=$rates->getRate($this->asset['dateTime'],$this->asset['unit']);
            $this->asset['value']=$this->asset['value']*$targetRate['value']/$sourceRate['value'];
            $this->asset['unit']=$unit;
            $this->asset['Currency']=$this->currencies[$unit]??'';
            $this->asset['string']='';
            if (isset($sourceRate['Error'])){$errors[]=$sourceRate['Error'];}
            if (isset($targetRate['Error'])){$errors[]=$targetRate['Error'];}
            if (isset($sourceRate['Warning'])){$warnings[]=$sourceRate['Warning'];}
            if (isset($targetRate['Warning'])){$warnings[]=$targetRate['Warning'];}
            if (!empty($warnings)){$this->asset['Warning']=implode('|',$warnings);}
            if (!empty($errors)){$this->asset['Error']=implode('|',$errors);}
        }
    }

    public function getArray():array
    {
        $decimals=(isset(self::DECIMALS[$this->asset['unit']]))?self::DECIMALS[$this->asset['unit']]:self::DEFAULT_DECIMALS;
        $assetArr=['ISO 8601'=>$this->asset['dateTime']->format('c'),'RFC 2822'=>$this->asset['dateTime']->format('r'),'Timestamp'=>$this->asset['dateTime']->getTimestamp(),'Currency'=>$this->asset['unit'],'Currency (long)'=>$this->asset['Currency'],'Amount'=>round($this->asset['value'],$decimals)];
        $assetArr['Amount de']=number_format($this->asset['value'],$decimals,',','');
        $assetArr['Amount (US)']=$assetArr['Currency'].' '.number_format($this->asset['value'],$decimals);
        $assetArr['Amount (DE)']=number_format($this->asset['value'],$decimals,',','').' '.$assetArr['Currency'];
        $assetArr['Amount (DE full)']=number_format($this->asset['value'],$decimals,',','.').' '.$assetArr['Currency'];
        $assetArr['Amount (FR)']=number_format($this->asset['value'],$decimals,'.',' ').' '.$assetArr['Currency'];
        if (!empty($this->asset['string'])){$assetArr['String']=$this->asset['string'];}
        if (!empty($this->asset['Error'])){$assetArr['Error']=$this->asset['Error'];}
        if (!empty($this->asset['Warning'])){$assetArr['Warning']=$this->asset['Warning'];}
        return $assetArr;
    }

    final public function getValue():float
    {
        return $this->asset['value'];
    }

    final public function getUnit():string
    {
        return $this->asset['unit'];
    }

    final public function getDateTime():\DateTime
    {
        return $this->asset['dateTime'];
    }

    final public function getWarnings():string|bool
    {
        return $this->asset['Warning']??FALSE;
    }

    final public function getErrors():string|bool
    {
        return $this->asset['Warning']??FALSE;
    }

    final public function addIntrestYearly(float $yearlyRatePercent=4,int $years=1):array
    {
        $steps=[0=>['dateTime'=>$this->asset['dateTime']->format('c'),'value'=>$this->asset['value'],'interest'=>0]];
        for($year=1;$year<=$years;$year++){
            $this->asset['dateTime']->add(new \DateInterval('P1Y'));
            $interest=$this->asset['value']*$yearlyRatePercent/100;
            $this->asset['value']=$this->asset['value']+$interest;
            $steps[$year]=['dateTime'=>$this->asset['dateTime']->format('c'),'value'=>$this->asset['value'],'interest'=>$interest];
        }
        return $steps;
    }

    final public function addIntrestMonthly(float $monthlyRatePercent=4,int $months=1):array
    {
        $decimals=(isset(self::DECIMALS[$this->asset['unit']]))?self::DECIMALS[$this->asset['unit']]:self::DEFAULT_DECIMALS;
        $steps=[0=>['dateTime'=>$this->asset['dateTime']->format('c'),'value'=>$this->asset['value'],'interest'=>0]];
        for($month=1;$month<=$months;$month++){
            $this->asset['dateTime']->add(new \DateInterval('P1M'));
            $interest=round($this->asset['value'],$decimals)*$monthlyRatePercent/100;
            $this->asset['value']=$this->asset['value']+$interest;
            $steps[$month]=['dateTime'=>$this->asset['dateTime']->format('c'),'value'=>$this->asset['value'],'interest'=>$interest];
        }
        return $steps;
    }
    
    final public function addAsset(float $value=0,string $unit=self::DEFAULT_UNIT,\DateTime $dateTime=NULL)
    {
        $now=new \DateTime('now');
        $this->setDateTime($dateTime??$now);
        $orgUnit=$this->getUnit();
        $this->setUnit($unit);
        $this->asset['value']+=$value;
        $this->setUnit($orgUnit);
    }
}
?>