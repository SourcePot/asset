<?php
/*
* @package asset
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2024 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit MIT
*/

declare(strict_types=1);

namespace SourcePot\Asset;

use DateTimeZone;

final class Rates{

    private const ECB_RATES_URL='https://data-api.ecb.europa.eu/service/data/EXR/D..EUR.SP00.A?format=csvdata&startPeriod={START}&endPeriod={END}';
    private const ECB_TIMEZONE='CET';
    private $currencies=['EUR'=>'Euro'];

    function __construct()
    {
        $this->getRates(new \DateTime('2020-01-15'));
    }

    final public function getCurrencies():array{
        return $this->currencies;
    }

    final public function getRates(\DateTime $dateTime):array
    {
        $rates=['DateTime'=>$dateTime,'EUR'=>1];
        $dateTime->setTimezone(new DateTimeZone(self::ECB_TIMEZONE));
        $dateTimeEarliestStr='1999-01-04 15:00:00';
        $dateTimeCmpStr=$dateTime->format('Y-m-d').' 15:00:00';
        $dateTimeNow=new \DateTime('now',new DateTimeZone(self::ECB_TIMEZONE));
        $dateTimeNowCmpStr=$dateTimeNow->format('Y-m-d H:i:s');
        if ($dateTimeCmpStr>$dateTimeNowCmpStr){
            throw new \Exception('E001: Requested rate is not yet available. Rates will be available past '.$dateTimeCmpStr.' CET.');
        } else if ($dateTime->format('Y-m-d H:i:s')<$dateTimeEarliestStr){
            throw new \Exception('E002: Requested rate is pre-EUR and not available. Rates available from '.$dateTimeEarliestStr.' CET.');
        }
        $ratesFileName='../data/'.$dateTime->format('Y-m-d').'_rates.csv';
        if (!is_file($ratesFileName)){
            $url=self::ECB_RATES_URL;
            $url=str_replace('{START}',$dateTime->format('Y-m-d'),$url);
            $url=str_replace('{END}',$dateTime->format('Y-m-d'),$url);
            $csv=@file_get_contents($url);
            if ($csv===FALSE){
                throw new \Exception('E005: Failed to receive data from "'.$url.'"');
            } else if (empty($csv)){
                throw new \Exception('E003: No rates available for this date.');
            } else {
                file_put_contents($ratesFileName,$csv);
            }
        }
        $ratesRaw=$this->csvFile2arr($ratesFileName);
        foreach($ratesRaw as $rate){
            $this->currencies[$rate['UNIT']]=str_replace('Euro/','',$rate['TITLE']);
            if (strlen($rate['OBS_VALUE'])===0){
                throw new \Exception('E006: Invalid rate.');
            } else {
                $rates[$rate['UNIT']]=floatval($rate['OBS_VALUE']);
            }
        }
        return $rates;
    }

    private function csvFile2arr(string $fileName):array
    {
        $resultArr=[];
        $columns=[];
        $file=fopen($fileName,"r");
        while (($data=fgetcsv($file))!==FALSE)
        {
            if (empty($columns)){
                foreach($data as $index=>$value){$columns[$index]=$value;}
                continue;
            }
            $resultArr[]=array_combine($columns,$data);
        }
        fclose($file);
        return $resultArr;
    }

    final public function getRate(\DateTime $dateTime, string $unit='USD',$recursionDepth=0,$requestedDateTime=''):array
    {
        $unit=strtoupper($unit);
        $rate=['dateTime'=>$dateTime,'unit'=>$unit];
        if ($unit==='EUR'){
            $rate['value']=1;
            return $rate;
        }
        try {
            $error='';
            $rates=$this->getRates($dateTime);
        } catch (\Exception $e) {
            $error=$e->getMessage();
        }
        if (empty($error) && isset($rates[$unit])){
        $rate['unit']=$unit;
            $rate['value']=$rates[$unit];
        } else if (empty($error) && !isset($rates[$unit])){
            throw new \Exception('E010: Unit "'.$unit.'"not available within rates.');
        } else if ($recursionDepth>5){
            throw new \Exception('E011: Recursivion depth "'.$recursionDepth.'" above threshold.');
        } else if (strpos($error,'E001: ')===0){
            $requestedDateTime=$dateTime->format('Y-m-d');
            $dateTime=new \DateTime('yesterday');
            $rate=$this->getRate($dateTime,$unit);
            $rate['Warning']='W001: Futre "'.$unit.'" rate for "'.$requestedDateTime.'" missing, latest rate dated "'.$dateTime->format('Y-m-d').'" used.';
        } else if (strpos($error,'E002: ')===0){
            $requestedDateTime=$dateTime->format('Y-m-d');
            $dateTime=new \DateTime('1999-01-05 15:00:00');
            $rate=$this->getRate($dateTime,$unit);
            $rate['Warning']='W002: Historic "'.$unit.'" rate for "'.$requestedDateTime.'" missing, earliest rate dated "'.$dateTime->format('Y-m-d').'" used.';
        } else if (strpos($error,'E003: ')===0 || strpos($error,'E006: ')===0){
            $requestedDateTime=$dateTime->format('Y-m-d');
            $dateTime->sub(new \DateInterval('P1D'));
            $rate=$this->getRate($dateTime,$unit,$recursionDepth+1,$requestedDateTime);
            if (empty($rate['Error'])){
                $rate['Warning']='W001: "'.$unit.'" rate for "'.$requestedDateTime.'" missing, rate dated "'.$dateTime->format('Y-m-d').'" used.';
            }
        } else {
            $rate['value']=NULL;
            $rate['Error']=$error;
        }
        return $rate;
    }

}
?>