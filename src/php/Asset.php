<?php
/*
* @package asset
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2024 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit MIT
*/

declare(strict_types=1);

namespace SourcePot\Asset;

use \Money\Money;
use \Money\Currency;
use \Money\Converter;
use \Money\Currencies\ISOCurrencies;
use \Money\Formatter\DecimalMoneyFormatter;
use \Money\Parser\DecimalMoneyParser;
use \Money\Exchange\FixedExchange;

require_once('../../vendor/autoload.php');

final class Asset{

    private const DEFAULT_UNIT='EUR';
    private const DEFAULT_DECIMALS=2;
    private const UNIT_ALIAS=['£'=>'GBP','€'=>'EUR','AU$'=>'AUD','$'=>'USD','US$'=>'USD'];
    private const DECIMALS=['XPF'=>0,'XAF'=>0,'VUV'=>0,'UGX'=>0,'TND'=>3,'RWF'=>0,'OMR'=>3,'PYG'=>0,'LYD'=>3,'KRW'=>0,'KWD'=>3,'KMF'=>0,'JPY'=>0,'JOD'=>3,'IQD'=>3,'IDR'=>0,'GNF'=>0,'DJF'=>0,'CVE'=>0,'BHD'=>3];
    private const BCMATH_SCALE=6;
    
    private $currencies=[];

    private $asset=array();
    
    function __construct(float $value=0,string $unit=self::DEFAULT_UNIT,\DateTime $dateTime=NULL)
    {
        $this->set($value,$unit,$dateTime);
        // add currencies
        $rates=new Rates();
        $this->currencies=$rates->getCurrencies();
        $this->asset['Currency']=$this->currencies[$unit]??'';
    }

    public function __toString()
    {
        $currencies = new ISOCurrencies();
        $moneyFormatter = new DecimalMoneyFormatter($currencies);
        $value=$moneyFormatter->format($this->asset['money']); // outputs 1.00
        return $value.' '.$this->asset['unit'].' ('.$this->asset['dateTime']->format('c').')';
    }

    /**
     * Setter methods
     */

    public function set(float|string $value=0,string $unit=self::DEFAULT_UNIT,\DateTime $dateTime=NULL)
    {
        $now=new \DateTime('now');
        $unit=$this->normalizeUnit($unit);
        $this->asset=['value'=>floatval($value),'unit'=>$unit,'Currency'=>$this->currencies[$unit]??self::DEFAULT_UNIT,'dateTime'=>$dateTime??$now];
        // create Money PHP object
        $currencies = new ISOCurrencies();
        $moneyParser = new DecimalMoneyParser($currencies);
        $this->asset['money'] = $moneyParser->parse(strval($value),new \Money\Currency($unit));
    }

    public function setFromString(string $string,string|NULL $unit=NULL,\DateTime $dateTime=NULL)
    {
        $asset=$this->guessAssetFromString($string,$unit,$dateTime);
        $this->set($asset['value'],$asset['unit'],$asset['dateTime']);
    }

    /**
     * Getter methods
     */

    final public function get(string $key='')
    {
        return (isset($this->asset[$key]))?$this->asset[$key]:$this->asset;
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

    /**
     * Feature methods
     */

    private function normalizeUnit(string $unit):string{
        $unit=strtoupper($unit);
        return self::UNIT_ALIAS[$unit]??$unit;
    }

    final public function guessAssetFromString(string $string,string|NULL $unit=NULL,\DateTime $dateTime=NULL):array
    {
        if (!isset($dateTime)){
            $dateTimeParserObj=new DateTimeParser();
            $dateTimeParserObj->setFromString($string);
            if ($dateTimeParserObj->isValid()){
                $dateTime=$dateTimeParserObj->get();
            }
        }
        // detect unit | currency
        if ($unit){
            $unit=$this->normalizeUnit($unit);
            $unit=$this->currencies[$unit]??NULL;
        }
        $string=strtoupper($string);
        if ($unit===NULL){
            foreach(self::UNIT_ALIAS as $needle=>$code){
                if (strpos($string,$needle)!==FALSE){
                    $unit=$code;
                    break;
                }
            }
        }
        if ($unit===NULL){
            foreach($this->currencies as $needle=>$name){
                if (strpos($string,$needle)!==FALSE){
                    $unit=$needle;
                    break;
                }
            }
        }
        // set template
        $asset=['unit'=>$unit??self::DEFAULT_UNIT,'string'=>$string,'dateTime'=>$dateTime??(new \DateTime('now'))];
        $asset['Currency']=$this->currencies[$asset['unit']];
        // recover value
        preg_match('/[+\-]{0,1}[0-9,.]+[eE+\-]{0,2}[0-9]+/',$string,$match);
        if (isset($match[0])){
            $numberStr=$match[0];
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
            $asset['value string']=$numberStr;
            $asset['value']=floatval($numberStr);
            $asset['Currency']=$this->currencies[$asset['unit']]??NULL;
        }
        return $asset;
    }

    private function valueFromMoney(Money $money):float
    {
        $currencies = new ISOCurrencies();
        $moneyFormatter = new DecimalMoneyFormatter($currencies);
        return floatval($moneyFormatter->format($money));
    }

    private function getExchangeRateArr(string $fromUnit,string $toUnit,\DateTime|NULL $conversionDateTime=NULL):array
    {
        $rates=new Rates();
        $exchageRate=[];
        $exchageRate['EUR'][$fromUnit]=strval(($rates->getRate($conversionDateTime,$fromUnit))['value']);
        $exchageRate['EUR'][$toUnit]=strval(($rates->getRate($conversionDateTime,$toUnit))['value']);
        $exchageRate[$fromUnit][$toUnit]=bcdiv($exchageRate['EUR'][$toUnit],$exchageRate['EUR'][$fromUnit],self::BCMATH_SCALE);
        return $exchageRate;
    }

    final public function convert2unit(string $unit,\DateTime|NULL $conversionDateTime=NULL)
    {
        $unit=$this->normalizeUnit($unit);
        if ($unit!==$this->asset['unit']){
            $conversionDateTime=$conversionDateTime??$this->asset['dateTime'];
            $errors=$warnings=[];
            // calculate money conversion
            $exchageRateArr=$this->getExchangeRateArr($this->asset['unit'],$unit,$conversionDateTime);
            $exchange = new FixedExchange($exchageRateArr);
            $converter = new Converter(new ISOCurrencies(), $exchange);
            $this->asset['money'] = $converter->convert($this->asset['money'], new Currency($unit));
            // update asset value, unit and dateTime
            $this->asset['value']=$this->valueFromMoney($this->asset['money']);
            $this->asset['unit']=$unit;
            $this->asset['Currency']=$this->currencies[$unit]??'';
            $this->asset['string']='';
            // errors and warnings
            if (isset($sourceRate['Error'])){$errors[]=$sourceRate['Error'];}
            if (isset($targetRate['Error'])){$errors[]=$targetRate['Error'];}
            if (isset($sourceRate['Warning'])){$warnings[]=$sourceRate['Warning'];}
            if (isset($targetRate['Warning'])){$warnings[]=$targetRate['Warning'];}
            if (!empty($warnings)){$this->asset['Warning']=implode('|',$warnings);}
            if (!empty($errors)){$this->asset['Error']=implode('|',$errors);}
        }
    }

    final public function multiply($multiplier=1):string
    {
        $multiplier=(is_string($multiplier));
        $this->asset['money']= $this->asset['money']->multiply($multiplier);
        $this->asset['value']=$this->valueFromMoney($this->asset['money']);
        return $this->__toString();
    }
    
    final public function divide($divider=1):string
    {
        $divider=(is_string($divider));
        $this->asset['money']= $this->asset['money']->divide($divider);
        $this->asset['value']=$this->valueFromMoney($this->asset['money']);
        return $this->__toString();
    }
    
    final public function addAssetString(string $string,string $unit=self::DEFAULT_UNIT,\DateTime $dateTime=NULL):string
    {
        $this->assetStringOperation('add',$string,$unit,$dateTime);
        return $this->__toString();
    }
    
    final public function subAssetString(string $string,string $unit=self::DEFAULT_UNIT,\DateTime $dateTime=NULL):string
    {
        $this->assetStringOperation('sub',$string,$unit,$dateTime);
        return $this->__toString();
    }
    
    final public function getRatioOfAssetString(string $string,string $unit=self::DEFAULT_UNIT,\DateTime $dateTime=NULL):string
    {
        return $this->assetStringOperation('ratioOf',$string,$unit,$dateTime);
    }
    
    private function assetStringOperation(string $operation,string $string,string|NULL $unit=NULL,\DateTime $dateTime=NULL)
    {
        // get to add asset from string
        $toAddAsset=$this->guessAssetFromString($string,$unit,$dateTime);
        $unit=(empty($toAddAsset['unit']))?$unit:$toAddAsset['unit'];
        $currencies = new ISOCurrencies();
        $moneyParser = new DecimalMoneyParser($currencies);
        $toAddAsset['money'] = $moneyParser->parse($toAddAsset['value string'],new \Money\Currency($unit));
        // convert toAddAsset to target unit
        $exchageRateArr=$this->getExchangeRateArr($unit,$this->asset['unit'],$dateTime??$this->asset['dateTime']);
        $exchange = new FixedExchange($exchageRateArr);
        $converter = new Converter(new ISOCurrencies(), $exchange);
        $money = $converter->convert($toAddAsset['money'], new Currency($this->asset['unit']));
        // add to asset
        if ($operation==='add'){
            $this->asset['money']=$this->asset['money']->add($money);
        } else if ($operation==='sub'){
            $this->asset['money']=$this->asset['money']->subtract($money);
        } else if ($operation==='ratioOf'){
            return $this->asset['money']->ratioOf($money);
        } else {
            throw new \Exception('E101: "'.$operation.'" is not a valid operation');
        }
        $this->asset['value']=$this->valueFromMoney($this->asset['money']);
    }

    final public function addIntrest(string $intervalDuration,int $intervalCount, $ratePercent=3):array
    {
        $multiplier=bcadd('1',bcdiv(strval($ratePercent),'100',self::BCMATH_SCALE),self::BCMATH_SCALE);
        $dateInterval=new \DateInterval('P1Y');
        $lastValue=$this->asset['value'];
        $steps=[0=>['dateTime'=>$this->asset['dateTime']->format('c'),'value'=>$this->asset['value'],'interest'=>0]];
        for($count=1;$count<=$intervalCount;$count++){
            $this->asset['dateTime']->add($dateInterval);
            $this->asset['money']=$this->asset['money']->multiply($multiplier);
            $value=$this->valueFromMoney($this->asset['money']);
            $steps[$count]=['dateTime'=>$this->asset['dateTime']->format('c'),'value'=>$value,'interest'=>$value-$lastValue];
            $lastValue=$value;
        }
        $this->asset['value']=$this->valueFromMoney($this->asset['money']);
        return $steps;
    }

    final public function fixedRateMortgage($loan=300000,$yearlyInterestPercent=3, $monthlyPayment=2800,int $scale=2):array
    {
        $month=0;
        $loanLeft=strval($loan);
        $monthlyPayment=strval($monthlyPayment);
        $accumulatedInterest='0';
        $accumulatedPayment='0';
        $yearlyInterestRate=bcdiv(strval($yearlyInterestPercent),'100',self::BCMATH_SCALE);
        $monthlyIntrestRate=bcdiv($yearlyInterestRate,'12',self::BCMATH_SCALE);
        while(bccomp($loanLeft,'0',$scale)>-1){
            $month++;
            $interest=bcmul($loanLeft,$monthlyIntrestRate,self::BCMATH_SCALE);
            $repayment=bcsub($monthlyPayment,$interest,$scale);
            if (bccomp($monthlyPayment,$interest,$scale)<1){
                return ['Loan'=>$loan,'Monthly payment'=>$monthlyPayment,'Years'=>'NaN','Accumulated payment'=>$monthlyPayment,'Accumulated interest'=>$interest,'Error'=>'E102: Repayment below interest'];
            }
            $loanLeft=bcsub($loanLeft,$repayment,$scale);
            $accumulatedInterest=bcadd($accumulatedInterest,$interest,$scale);
            $accumulatedPayment=bcadd($accumulatedPayment,$monthlyPayment,$scale);
            if ($month===12){
                $yearlyInterestPercent=bcdiv($accumulatedInterest,$loanLeft,self::BCMATH_SCALE);
                $yearlyInterestPercent=bcmul($yearlyInterestPercent,'100',$scale);
            }
        }
        $accumulatedPayment=bcadd($accumulatedPayment,$loanLeft,$scale);
        return ['Loan'=>$loan,'Monthly payment'=>$monthlyPayment,'Years'=>round($month/12,2),'Accumulated payment'=>$accumulatedPayment,'Accumulated interest'=>$accumulatedInterest,'Effective yearly interest'=>$yearlyInterestPercent];
    }

}
?>