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
use \Money\Currencies\AggregateCurrencies;
use \Money\Currencies\ISOCurrencies;
use \Money\Currencies\BitcoinCurrencies;
use \Money\Currencies\CryptoCurrencies;
use \Money\Formatter\DecimalMoneyFormatter;
use \Money\Parser\DecimalMoneyParser;
use \Money\Exchange\FixedExchange;

final class Asset{

    private const DEFAULT_UNIT='EUR';
    private const CURRENCY_UNIT_ALIAS=[
        '£'=>'GBP',
        '€'=>'EUR',
        'US$'=>'USD',
        'AU$'=>'AUD',
        '$'=>'USD',
        '¥'=>'JPY',
        '₹'=>'INR',
        '؋'=>'AFN',
        '₿'=>'BTC',
        '₡'=>'CRC',
        '₢'=>'BRL',
        '₣'=>'CHF',
        '₥'=>'FRF',
        '₦'=>'NGN',
        '₧'=>'ESP',
        '₨'=>'PKR',
        '₩'=>'KRW',
        '₪'=>'ILS',
        '₫'=>'VND',
        '₭'=>'LAK',
        '₮'=>'MNT',
        '₯'=>'GRD',
        '₰'=>'DEM',
        '₱'=>'PHP',
        '₲'=>'PYG',
        '₳'=>'ARA',
        '₴'=>'UAH',
        '₵'=>'GHS',
        '₸'=>'KZT',
        '₺'=>'TRY',
        '₼'=>'AZN',
        '₽'=>'RUB',
        '₾'=>'GEL',
        'SEK'=>'SEK',
        'kr'=>'SEK',
    ];
    private const BCMATH_SCALE=6;
    private const LIMITS=[
        'max'=>36000000000000,
        'min'=>0.0001,
    ];
    public const NUMBER_REGEX='/([+\-]{0,1})(([., ]{0,1}[0-9]+)+)(([eE+\-]{0,2}[0-9.,]+){0,1})/';
    
    private $currencies;

    private $asset=[
        'string'=>'',
        'value'=>0
    ];
    
    function __construct(float $value=0,string $unit=self::DEFAULT_UNIT,\DateTime|NULL $dateTime=NULL)
    {
        // add currencies
        $this->currencies = new AggregateCurrencies([
            new BitcoinCurrencies(),
            new CryptoCurrencies(), 
            new ISOCurrencies(),
        ]);
        // initial asset
        $this->set($value,$unit,$dateTime);
    }

    public function __toString()
    {
        $moneyFormatter = new DecimalMoneyFormatter($this->currencies);
        $value=$moneyFormatter->format($this->asset['money']); // outputs 1.00
        return $value.' '.$this->asset['unit'].' ('.$this->asset['dateTime']->format('c').')';
    }

    /**
     * Setter methods
     */

    public function set(float|string $value=0,string $unit=self::DEFAULT_UNIT,\DateTime|NULL $dateTime=NULL)
    {
        $this->asset=['value'=>floatval($value),'dateTime'=>$dateTime??new \DateTime('now')];
        $this->asset['string']=$value.' '.$unit.' '.$this->asset['dateTime']->format('c');
        $this->asset=$this->addUnitFromString($this->asset,$unit);
        // create Money PHP object
        $moneyParser = new DecimalMoneyParser($this->currencies);
        if (abs($this->asset['value'])>self::LIMITS['max']){
            $this->asset['value']=($this->asset['value']>0)?self::LIMITS['max']:-self::LIMITS['max'];
            $this->asset['Warning']='E100: Value is limited to '.$this->asset['value'];
        } else if ($this->asset['value']!==0.0 && abs($this->asset['value'])<self::LIMITS['min']){
            $this->asset['value']=($this->asset['value']>0)?self::LIMITS['min']:-self::LIMITS['min'];
            $this->asset['Warning']='E100: Value is limited to '.$this->asset['value'];
        }
        $this->asset['money']=$moneyParser->parse(strval($this->asset['value']),new \Money\Currency($this->asset['unit']));
    }

    public function setFromString(string $string,string|NULL $unit=NULL,\DateTime|NULL $dateTime=NULL)
    {
        $asset=$this->guessAssetFromString($string,$unit,$dateTime);
        $this->set($asset['value'],$asset['unit'],$asset['dateTime']);
        if (isset($asset['Error'])){
            $this->asset['Error']=$asset['Error'];
        }
    }

    /**
     * Getter methods
     */

    final public function get(string $key='')
    {
        $this->asset['isValid']=empty($this->asset['Error']);
        return (isset($this->asset[$key]))?$this->asset[$key]:$this->asset;
    }
     
    public function getArray():array
    {
        $assetArr=$this->asset;
        $assetArr=$this->addUnitFromString($assetArr);
        $decimals=$this->currencies->subunitFor(new Currency($assetArr['unit']));
        $assetArr['string']=$this->asset['value'].' '.$this->asset['unit'].' on '.$this->asset['dateTime']->format('c');
        $assetArr['ISO 8601']=$this->asset['dateTime']->format('c');
        $assetArr['RFC 2822']=$this->asset['dateTime']->format('r');
        $assetArr['Timestamp']=$this->asset['dateTime']->getTimestamp();
        $assetArr['Amount']=round($this->asset['value'],$decimals);
        $assetArr['Amount de']=number_format($this->asset['value'],$decimals,',','');
        $assetArr['Amount (US)']=$assetArr['unit'].' '.number_format($this->asset['value'],$decimals);
        $assetArr['Amount (DE)']=number_format($this->asset['value'],$decimals,',','').' '.$assetArr['unit'];
        $assetArr['Amount (DE full)']=number_format($this->asset['value'],$decimals,',','.').' '.$assetArr['unit'];
        $assetArr['Amount (FR)']=number_format($this->asset['value'],$decimals,'.',' ').' '.$assetArr['unit'];
        if (!empty($this->asset['string'])){$assetArr['String']=$this->asset['string'];}
        if (!empty($this->asset['Error'])){$assetArr['Error']=$this->asset['Error'];}
        if (!empty($this->asset['Warning'])){$assetArr['Warning']=$this->asset['Warning'];}
        $assetArr['isValid']=empty($assetArr['Error']);
        return $assetArr;
    }

    /**
     * Feature methods
     */

    private function normalizeUnit(string|null $unit):string{
        $unit=strtoupper($unit??'');
        return self::CURRENCY_UNIT_ALIAS[$unit]??$unit;
    }

    final public function guessAssetFromString(string $string,string|NULL $unit=NULL,\DateTime|NULL $dateTime=NULL):array
    {
        $asset=['string'=>$string,'value'=>0];
        $asset=$this->addDateTimeFromString($asset,$dateTime);
        $asset=$this->addUnitFromString($asset,$unit);
        $asset['string']=preg_replace('/[£$€¥]+/','',$asset['string']);
        $asset['string']=strtr($asset['string'],['–'=>'-','—'=>'-',]);
        preg_match(self::NUMBER_REGEX,$asset['string'],$match);
        if (isset($match[0])){
            $numberStr=str_replace(' ','',$match[2]);
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
            $asset['value string']=$match[1].$numberStr.($match[5]??'');
            $asset['value']=floatval($asset['value string']);
        } else {
            $asset['Error']='E103: No numeric value found';
        }
        return $asset;
    }

    private function addDateTimeFromString(array $asset,\DateTime|NULL $dateTime=NULL):array
    {
        $asset['dateTime']=$dateTime;
        if (!isset($asset['dateTime'])){
            $dateTimeParserObj=new DateTimeParser();
            $dateTimeParserObj->setFromString($asset['string']);
            if ($dateTimeParserObj->isValid()){
                $asset['dateTime']=$dateTimeParserObj->get();
            } else {
                $asset['dateTime']=new \DateTime('now');
            }
        }
        return $asset;
    }

    private function addUnitFromString(array $asset,string|null $unit=''):array
    {
        $unit=strtoupper($unit?:($asset['unit']??''));
        $normalizedUnit=$this->normalizeUnit($unit);
        $string=strtoupper($asset['string']??'');
        $asset['Currency']='';
        foreach(self::CURRENCY_UNIT_ALIAS as $needle=>$code){
            if (strpos($string,strtoupper($needle))!==FALSE){
                $asset['unit']=$asset['Currency']=$code;
                break;
            }
        }
        if (empty($asset['Currency'])){
            foreach($this->currencies as $currency){
                $currencyCode=$currency->getCode();
                if (strlen($currencyCode)>=3 && strpos($string,$currencyCode)!==FALSE){
                    $asset['unit']=$asset['Currency']=$currencyCode;
                    break;
                }
            }
        }
        if (empty($asset['Currency'])){
            foreach($this->currencies as $currency){
                if ($normalizedUnit===($currencyCode=$currency->getCode())){
                    $asset['unit']=$asset['Currency']=$currencyCode;
                    break;
                }
            }
        }
        if (empty($asset['Currency']) && isset(self::CURRENCY_UNIT_ALIAS[$unit])){
            $asset['unit']=$asset['Currency']=self::CURRENCY_UNIT_ALIAS[$unit];
        }
        $asset['unit']=$asset['unit']??self::DEFAULT_UNIT;
        return $asset;
    }

    private function valueFromMoney(Money $money):float
    {
        $moneyFormatter = new DecimalMoneyFormatter($this->currencies);
        return floatval($moneyFormatter->format($money));
    }

    private function getExchangeRateArr(string $fromUnit,string $toUnit,\DateTime|NULL $conversionDateTime=NULL):array
    {
        $rates=new Rates();
        $sourceRate=$rates->getRate($conversionDateTime,$fromUnit);
        $targetRate=$rates->getRate($conversionDateTime,$toUnit);
        $exchageRate=[];
        $exchageRate['EUR'][$fromUnit]=strval($sourceRate['value']);
        $exchageRate['EUR'][$toUnit]=strval($targetRate['value']);
        $exchageRate[$fromUnit][$toUnit]=bcdiv($exchageRate['EUR'][$toUnit],$exchageRate['EUR'][$fromUnit],self::BCMATH_SCALE);
        return ['rates'=>$exchageRate,'source'=>$sourceRate,'target'=>$targetRate];
    }

    final public function convert2unit(string $unit,\DateTime|NULL $conversionDateTime=NULL)
    {
        $unit=$this->normalizeUnit($unit);
        if ($unit!==$this->asset['unit']){
            $conversionDateTime=$conversionDateTime??$this->asset['dateTime'];
            $errors=$warnings=[];
            // calculate money conversion
            $exchangeRateResult=$this->getExchangeRateArr($this->asset['unit'],$unit,$conversionDateTime);
            $exchageRateArr=$exchangeRateResult['rates'];
            $sourceRate=$exchangeRateResult['source'];
            $targetRate=$exchangeRateResult['target'];
            $exchange = new FixedExchange($exchageRateArr);
            $converter = new Converter($this->currencies, $exchange);
            $this->asset['money'] = $converter->convert($this->asset['money'], new Currency($unit));
            // update asset value, unit and dateTime
            $this->asset['value']=$this->valueFromMoney($this->asset['money']);
            $this->asset['unit']=$unit;
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
        $multiplier=(strval($multiplier));
        $this->asset['money']= $this->asset['money']->multiply($multiplier);
        $this->asset['value']=$this->valueFromMoney($this->asset['money']);
        return $this->__toString();
    }
    
    final public function divide($divider=1):string
    {
        $divider=(strval($divider));
        if ($divider==='0' || bccomp($divider,'0',12)==0){
            throw new \InvalidArgumentException('E105: Divider must not be zero');
        }
        $this->asset['money']= $this->asset['money']->divide($divider);
        $this->asset['value']=$this->valueFromMoney($this->asset['money']);
        return $this->__toString();
    }
    
    final public function addAssetString(string $string,string $unit=self::DEFAULT_UNIT,\DateTime|NULL $dateTime=NULL):string
    {
        $this->assetStringOperation('add',$string,$unit,$dateTime);
        return $this->__toString();
    }
    
    final public function subAssetString(string $string,string $unit=self::DEFAULT_UNIT,\DateTime|NULL $dateTime=NULL):string
    {
        $this->assetStringOperation('sub',$string,$unit,$dateTime);
        return $this->__toString();
    }
    
    final public function getRatioOfAssetString(string $string,string $unit=self::DEFAULT_UNIT,\DateTime|NULL $dateTime=NULL):string
    {
        return $this->assetStringOperation('ratioOf',$string,$unit,$dateTime);
    }
    
    private function assetStringOperation(string $operation,string $string,string|NULL $unit=NULL,\DateTime|NULL $dateTime=NULL):string
    {
        // get to add asset from string
        $toAddAsset=$this->guessAssetFromString($string,$unit,$dateTime);
        if (isset($toAddAsset['Error'])){
            throw new \Exception($toAddAsset['Error']);
        }
        $unit=(empty($toAddAsset['unit']))?$unit:$toAddAsset['unit'];
        $moneyParser = new DecimalMoneyParser($this->currencies);
        $toAddAsset['money'] = $moneyParser->parse($toAddAsset['value string'],new \Money\Currency($unit));
        // convert toAddAsset to target unit
        $exchangeRateResult=$this->getExchangeRateArr($unit,$this->asset['unit'],$dateTime);
        $exchageRateArr=$exchangeRateResult['rates'];
        $exchange = new FixedExchange($exchageRateArr);
        $converter = new Converter($this->currencies, $exchange);
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
        return $this->__toString();
    }

    final public function addIntrest(string $intervalDuration='P1Y',int $intervalCount=1, $ratePercent=3):array
    {
        if ($intervalCount<0){
            throw new \InvalidArgumentException('E106: Interval count must not be negative');
        }
        $multiplier=bcadd('1',bcdiv(strval($ratePercent),'100',self::BCMATH_SCALE),self::BCMATH_SCALE);
        $dateInterval=new \DateInterval($intervalDuration);
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
        $loan=strval($loan);
        $monthlyPayment=strval($monthlyPayment);
        $yearlyInterestPercent=strval($yearlyInterestPercent);
        $scale=max(2,min($scale,12));
        $mathScale=12;
        if (bccomp($loan,'0',$scale)<0){
            return ['Loan'=>$loan,'Monthly payment'=>$monthlyPayment,'Years'=>'NaN','Accumulated payment'=>'0','Accumulated interest'=>'0','Error'=>'E102: Loan must not be negative'];
        }
        if (bccomp($loan,'0',$scale)==0){
            return ['Loan'=>$loan,'Monthly payment'=>$monthlyPayment,'Years'=>0,'Accumulated payment'=>'0','Accumulated interest'=>'0','Effective yearly interest'=>$yearlyInterestPercent];
        }
        if (bccomp($monthlyPayment,'0',$scale)<=0){
            return ['Loan'=>$loan,'Monthly payment'=>$monthlyPayment,'Years'=>'NaN','Accumulated payment'=>'0','Accumulated interest'=>'0','Error'=>'E102: Monthly payment must be positive'];
        }
        if (bccomp($yearlyInterestPercent,'0',$scale)<0){
            return ['Loan'=>$loan,'Monthly payment'=>$monthlyPayment,'Years'=>'NaN','Accumulated payment'=>'0','Accumulated interest'=>'0','Error'=>'E102: Interest rate must not be negative'];
        }

        $month=0;
        $loanLeft=strval($loan);
        $accumulatedInterest='0';
        $accumulatedPayment='0';
        $yearlyInterestRate=bcdiv(strval($yearlyInterestPercent),'100',$mathScale);
        $monthlyIntrestRate=bcdiv($yearlyInterestRate,'12',$mathScale);
        while(bccomp($loanLeft,'0',$mathScale)>0 && $month<1200){
            $month++;
            $interest=bcmul($loanLeft,$monthlyIntrestRate,$mathScale);
            if (bccomp($monthlyPayment,$interest,$mathScale)<1){
                return ['Loan'=>$loan,'Monthly payment'=>$monthlyPayment,'Years'=>'NaN','Accumulated payment'=>$accumulatedPayment,'Accumulated interest'=>$accumulatedInterest,'Error'=>'E102: Repayment below interest'];
            }
            $payment=$monthlyPayment;
            $payoff=bcadd($loanLeft,$interest,$mathScale);
            if (bccomp($payment,$payoff,$mathScale)>=0){
                $payment=$payoff;
            }
            $principal=bcsub($payment,$interest,$mathScale);
            $loanLeft=bcsub($loanLeft,$principal,$mathScale);
            $accumulatedInterest=bcadd($accumulatedInterest,$interest,$mathScale);
            $accumulatedPayment=bcadd($accumulatedPayment,$payment,$mathScale);
        }
        if (bccomp($loanLeft,'0',$scale)>0){
            return ['Loan'=>$loan,'Monthly payment'=>$monthlyPayment,'Years'=>'NaN','Accumulated payment'=>$accumulatedPayment,'Accumulated interest'=>$accumulatedInterest,'Error'=>'E102: Mortgage exceeds 100-year limit'];
        }
        $monthlyRate=floatval($yearlyInterestRate)/12;
        $effectiveYearlyPercent=(pow(1+$monthlyRate,12)-1)*100;
        return ['Loan'=>$loan,'Monthly payment'=>$monthlyPayment,'Years'=>round($month/12,2),'Accumulated payment'=>$accumulatedPayment,'Accumulated interest'=>$accumulatedInterest,'Effective yearly interest'=>round($effectiveYearlyPercent,$scale)];
    }

}
?>