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
    private const ECB_TIMEZONE='Europe/Berlin';
    private $currencies=[];

    function __construct()
    {
        $this->getRates(new \DateTime('2025-01-15'));
    }

    public function getCurrencies():array{
        return $this->currencies;
    }

    public function getRates(\DateTime $dateTime):array
    {
        $rates=['DateTime'=>$dateTime];
        $dateTime->setTimezone(new DateTimeZone(self::ECB_TIMEZONE));
        $dateTimeCmpStr=$dateTime->format('Y-m-d').' 15:00:00';
        $dateTimeNow=new \DateTime('now',new DateTimeZone(self::ECB_TIMEZONE));
        $dateTimeNowCmpStr=$dateTimeNow->format('Y-m-d H:i:s');
        if ($dateTimeCmpStr>$dateTimeNowCmpStr){
            $rates['Error']='Requested rate is not yet available. Try again past '.$dateTimeCmpStr.' CET.';
            return $rates;
        }
        $ratesFileName='../data/'.$dateTime->format('Y-m-d').'_rates.csv';
        if (!is_file($ratesFileName)){
            $url=self::ECB_RATES_URL;
            $url=str_replace('{START}',$dateTime->format('Y-m-d'),$url);
            $url=str_replace('{END}',$dateTime->format('Y-m-d'),$url);
            $csv=@file_get_contents($url);
            if ($csv===FALSE){
                $rates['Error']='Failed to receive data from "'.$url.'"';
                return $rates;
            } else if (empty($csv)){
                $rates['Error']='No rates available for this date.';
                return $rates;
            } else {
                file_put_contents($ratesFileName,$csv);
            }
        }
        $ratesRaw=$this->csvFile2arr($ratesFileName);
        foreach($ratesRaw as $rate){
            $this->currencies[$rate['UNIT']]=str_replace('Euro/','',$rate['TITLE']);
            $rates[$rate['UNIT']]=floatval($rate['OBS_VALUE']);
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
}
?>