<?php
/*
* @package asset
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2024 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit MIT
*/

declare(strict_types=1);

namespace SourcePot\Asset;

final class DateTimeParser{

    private const DEFAULT_TIMEZONE='Europe/Berlin';
    private const MONTHS_NEEDLES=['january'=>'january','januar'=>'january','enero'=>'january','janvier'=>'january','jan.'=>'january','jan'=>'january',
                                  'february'=>'february','februar'=>'february','febrero'=>'february','février'=>'february','feb.'=>'february','feb'=>'february',
                                  'march'=>'march','märz'=>'march','marzo'=>'march','mars'=>'march','mar.'=>'march','mar'=>'march',
                                  'april'=>'april','abril'=>'april','avril'=>'april','apr.'=>'april','apr'=>'april',
                                  'may'=>'may','mai'=>'may','mayo'=>'may','mai'=>'may',
                                  'june'=>'june','juni'=>'june','junio'=>'june','juin'=>'june','jun.'=>'june','jun'=>'june',
                                  'july'=>'july','juli'=>'july','julio'=>'july','juillet'=>'july','jul.'=>'july','jul'=>'july',
                                  'august'=>'august','agosto'=>'august','aout'=>'august','aug.'=>'august','aug'=>'august',
                                  'september'=>'september','september'=>'september','septiembre'=>'september','septembre'=>'september','sep.'=>'september','sep'=>'september',
                                  'october'=>'october','oktober'=>'october','octubre'=>'october','octobre'=>'october','oct.'=>'october','oct'=>'october',
                                  'november'=>'november','noviembre'=>'november','novembre'=>'november','nov.'=>'november','nov'=>'november',
                                  'december'=>'december','dezember'=>'december','diciembre'=>'december','décembre'=>'december','dec.'=>'december','dec'=>'december','dic.'=>'december','dic'=>'december',
                                ];
    
    private const MONTH2NUMERIC=['january'=>'01','february'=>'02','march'=>'03','april'=>'04','may'=>'05','june'=>'06','july'=>'07','august'=>'08','september'=>'09','october'=>'10','november'=>'11','december'=>'12'];
    
    private const MONTHS_DICT_DE=['01'=>'Januar','02'=>'Februar','03'=>'März','04'=>'April','05'=>'Mai','06'=>'Juni','07'=>'Juli','08'=>'August','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Dezember'];
    private const MONTHS_DICT_ES=['01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril','05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto','09'=>'septiembr','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'];
    private const MONTHS_DICT_FR=['01'=>'janvier','02'=>'février','03'=>'mars','04'=>'avril','05'=>'mai','06'=>'juin','07'=>'juillet','08'=>'aout','09'=>'septembre','10'=>'octobre','11'=>'novembre','12'=>'décembre'];
    private const YEAR_2000_THRESHOLD=50;

    private $dateTime=NULL;

    function __construct(\DateTime $dateTime=NULL)
    {
        $now=new \DateTime('now');
        $this->dateTime=$dateTime??$now;
    }

    /**
     * Getter methods
     */

    final public function get():\DateTime
    {
        return $this->dateTime;
    }

    final public function __toString():string
    {
        return $this->dateTime->format('c');
    }

    final public function isValid():bool
    {
        return !($this->dateTime->format('d')==='01' && $this->dateTime->format('m')==='01' && $this->dateTime->format('Y')==='0000');
    }

    final public function getDateTime():\DateTime
    {
        return $this->dateTime;
    }

    final public function getArray():array
    {
        $dateTimeArr=[];
        $dateTimeArr['System short']=$this->dateTime->format('Y-m-d');
        $dateTimeArr['System']=$this->dateTime->format('Y-m-d H:i:s');
        $dateTimeArr['YYYYMMDD']=$this->dateTime->format('Ymd');
        $dateTimeArr['Timezone']=$this->dateTime->getTimezone()->getName();
        $dateTimeArr['Timestamp']=$this->dateTime->getTimestamp();
        $dateTimeArr['US']=$this->dateTime->format('m-d-Y');
        $dateTimeArr['UK']=$this->dateTime->format('d/m/Y');
        $dateTimeArr['DE']=$this->dateTime->format('d.m.Y');
        $dateTimeArr['RFC2822']=$this->dateTime->format(\DateTimeInterface::RFC2822);
        $dateTimeArr['day']=$this->dateTime->format('d');
        $dateTimeArr['month']=$this->dateTime->format('m');
        $dateTimeArr['year']=$this->dateTime->format('Y');
        $dateTimeArr['US long']=$this->dateTime->format('F').' '.$this->dateTime->format('j').', '.$this->dateTime->format('Y');
        $dateTimeArr['UK long']=$this->dateTime->format('j').' '.$this->dateTime->format('F').' '.$this->dateTime->format('Y');
        $dateTimeArr['DE long']=$this->dateTime->format('j').'. '.self::MONTHS_DICT_DE[$this->dateTime->format('m')].' '.$this->dateTime->format('Y');
        $dateTimeArr['isValid']=$this->isValid();
        return $dateTimeArr;
    }

    /**
     * Setter methods
     */

    final public function set($dateTime)
    {
        if (is_object($dateTime)){
            $this->dateTime=$dateTime;
        } else if (is_integer($dateTime)){
            $this->setFromTimestamp($dateTime);
        } else {
            $this->setFromString($dateTime);
        }
    }
    
    final public function setFromTimestamp($timestamp)
    {
        $timestamp=intval($timestamp);
        $this->dateTime=new \DateTime('@'.$timestamp);
    }

    final public function setFromExcelTimestamp($excelTimestamp)
    {
        $unixTimestamp=intval(86400*(floatval($excelTimestamp)-25569));
        $this->dateTime=new \DateTime('@'.$unixTimestamp);
    }

    final public function setFromString(string $string,\DateTimeZone $timeZone=new \DateTimeZone(self::DEFAULT_TIMEZONE)):bool
    {
        // is numeric
        if (is_numeric($string)){
            $dateTimeArr=$this->numeric2dateTimeArr($string);
            $string=$dateTimeArr['date'].' '.$dateTimeArr['time'];
            $this->dateTime=new \DateTime($string,$timeZone);
        } else {
            // detect valid dateTime string
            try {
                $this->dateTime=new \DateTime($string,$timeZone);
            } catch (\Exception $e){
                $dateTimeArr=$this->str2dateTimeArr($string);
                $string=$dateTimeArr['date'].' '.$dateTimeArr['time'];
                if (empty($dateTimeArr['timezone'])){
                    $this->dateTime=new \DateTime($string,$timeZone);
                } else {
                    $parsedTimeeZone=new \DateTimeZone($dateTimeArr['timezone']);
                    $this->dateTime=new \DateTime($string,$parsedTimeeZone);
                    $this->dateTime->setTimezone($timeZone);
                }
            }
        }
        return $this->isValid();
    }

    final public function setTimezone(string|\DateTimeZone $timeZone)
    {
        if (!is_object($timeZone)){
            $timeZone=new \DateTimeZone($timeZone);
        }
        $this->dateTime->setTimezone($timeZone);
    }

    /**
     * Date-time parser methods
     */

    private function numeric2dateTimeArr(string $string):array
    {
        $dateTimeArr=['date'=>'0000-01-01','time'=>'12:00:00'];
        // YYYMMDD
        preg_match('/([12][0-9]{3})([01][0-9])([0-3][0-9])/',$string,$match);
        if (isset($match[0])){
            $date=$this->createDateStr($match[3],$match[2],$match[1]);
            if ($date){
                $dateTimeArr['date']=$date;
            }
        }
        return $dateTimeArr;
    }

    private function str2dateTimeArr(string $string):array
    {
        $dateTimeArr=['date'=>'0000-01-01','time'=>'12:00:00'];
        // parse timezone
        foreach(\DateTimeZone::listIdentifiers() as $timezoneStr){
            if (strpos($string,$timezoneStr)===FALSE){continue;}
            if (strlen($timezoneStr)<4){continue;}  
            $dateTimeArr['timezone']=$timezoneStr;
            break;
        }
        // parse time
        $string=$this->normalizeSystemTimeString($string);
        $string=$this->normalizeUKtimeString($string);
        preg_match('/\{([0-9]{2}:[0-9]{2}:[0-9]{2})\}/',$string,$match);
        if (isset($match[1])){
            // valid time detected
            $string=str_replace($match[0],'',$string);
            $dateTimeArr['time']=$match[1];
        }
        // parse date
        $string=$this->normalizeDEdateString($string);
        $string=$this->normalizeUSdateString($string);
        $string=$this->normalizeUKdateString($string);
        $string=$this->normalizeSystemdateString($string);
        $string=$this->normalizeTextDateString($string);
        preg_match('/\{([0-9]{4}-[0-9]{2}-[0-9]{2})\}/',$string,$match);
        if (isset($match[1])){
            // valid date detected
            $string=str_replace($match[0],'',$string);
            $dateTimeArr['date']=$match[1];
        }
        return $dateTimeArr;
    }
    
    /*  Time string methods - detection of different time formats, verification of ranges and formating
    *
    */
    
    private function normalizeSystemTimeString(string $string):string
    {
        $tmpString=' '.$string.' ';
        // detect 12:34:56
        preg_match('/[^0-9]([0-2][0-9][:][0-5][0-9][:][0-5][0-9])[^0-9]/',$tmpString,$match);
        if (isset($match[0])){
            return str_replace($match[0],'{'.$match[1].'}',$tmpString);
        }
        return $string;
    }

    private function normalizeUKtimeString(string $string):string
    {
        $tmpString=' '.strtolower($string).' ';
        // detect 12:34pm
        preg_match('/[^0-9]([0-1]{0,1}[0-9])[:.]([0-5]{0,1}[0-9])([apm]{2})[^apm]/',$tmpString,$match);
        if (isset($match[0])){
            if ($match[3]=='am'){
                $hour=intval($match[1]);
                $hour=($hour===12)?0:$hour;
            } else {
                $hour=intval($match[1]);
                $hour=($hour===12)?12:(12+$hour);
            }
            $time=$this->createTimeStr('00',$match[2],strval($hour));
            if ($time){
                return str_replace($match[0],'{'.$time.'}',$tmpString);
            }
        }
        // detect 22.34hrs
        preg_match('/[^0-9]([0-2]{0,1}[0-9])[:.]([0-5]{0,1}[0-9])([hrs]{0,3})[^0-9hrs:]/',$tmpString,$match);
        if (isset($match[0])){
            $time=$this->createTimeStr('00',$match[2],$match[1]);
            if ($time){
                return str_replace($match[0],'{'.$time.'}',$tmpString);
            }
        }
        return $string;
    }

    private function createTimeStr(string $second, string $minute, string $hour):string|bool
    {
        try {
            $timeStr='';
            $timeStr.=$this->getHourStr($hour);
            $timeStr.=':'.$this->getMinuteStr($minute);
            $timeStr.=':'.$this->getSecondStr($second);
            return $timeStr;
        } catch (\Exception $e){
            return FALSE;
        }
    }

    private function getHourStr(string $hour):string
    {
        $hour=intval($hour);
        if ($hour>23 || $hour<0){
            throw new \Exception('E101: Parsed hour out of range.');
        }
        if ($hour<10){
            return '0'.strval($hour);
        } else {
            return strval($hour);
        }
    }

    private function getMinuteStr(string $minute):string
    {
        $minute=intval($minute);
        if ($minute>59 || $minute<0){
            throw new \Exception('E101: Parsed minute out of range.');
        }
        if ($minute<10){
            return '0'.strval($minute);
        } else {
            return strval($minute);
        }
    }

    private function getSecondStr(string $second):string
    {
        $second=intval($second);
        if ($second>59 || $second<0){
            throw new \Exception('E101: Parsed second out of range.');
        }
        if ($second<10){
            return '0'.strval($second);
        } else {
            return strval($second);
        }
    }

    /*  Date string methods - detection of different date formats, verification of ranges and formating
    *
    */
    private function normalizeTextDateString(string $string):string
    {
        $tmpString=' '.(strtolower($string)).' ';
        // normalize month
        foreach(self::MONTHS_NEEDLES as $needle=>$month){
            if (strpos($tmpString,$needle)===FALSE){continue;}
            $tmpString=str_replace($needle,$month,$tmpString);
            break;
        }
        // detect format August 13., 2021
        preg_match('/[^a-z]([a-z]{3,20})([^,]{2,5})[,]\s{0,2}([0-9]{2,4})[^0-9]/',$tmpString,$match);
        if (isset($match[0])){
            $date=$this->createDateStr($match[2],$match[1],$match[3]);
            if ($date){
                return str_replace($match[0],'{'.$date.'}',$tmpString);
            }
        }
        // detect format 13. August 2021
        preg_match('/[^0-9]([0-3]{0,1}[0-9])[. ]{1,3}([a-z]{3,20})[. ]{1,3}([0-9]{2,4})[^0-9]/',$tmpString,$match);
        if (isset($match[0])){
            $date=$this->createDateStr($match[1],$match[2],$match[3]);
            if ($date){
                return str_replace($match[0],'{'.$date.'}',$tmpString);
            }
        }
        return $string;
    }

    private function normalizeDEdateString(string $string):string
    {
        // detect DE format 31.08.2011
        $tmpString=' '.$string.' ';
        preg_match('/[^0-9]([0-3]{0,1}[0-9])[.]([0-1]{0,1}[0-9])[.]([0-9]{2,4})[^0-9]/',$tmpString,$match);
        if (isset($match[0])){
            $date=$this->createDateStr($match[1],$match[2],$match[3]);
            if ($date){
                return str_replace($match[0],'{'.$date.'}',$tmpString);
            }
        }
        return $string;
    }

    private function normalizeUSdateString(string $string):string
    {
        // detect US format 08-31-11
        $tmpString=' '.$string.' ';
        preg_match('/[^0-9]([0-1]{0,1}[0-9])[\-]([0-3]{0,1}[0-9])[\-]([0-9]{2,4})[^0-9]/',$tmpString,$match);
        if (isset($match[0])){
            $date=$this->createDateStr($match[2],$match[1],$match[3]);
            if ($date){
                return str_replace($match[0],'{'.$date.'}',$tmpString);
            }
        }
        return $string;
    }

    private function normalizeSystemdateString(string $string):string
    {
        // detect US format 2011-08-31
        $tmpString=' '.$string.' ';
        preg_match('/[^0-9]([0-9]{4}[\-][0-1]{0,1}[0-9][\-][0-3]{1}[0-9]{1})[^0-9]/',$tmpString,$match);
        if (isset($match[1])){
            return str_replace($match[0],'{'.$match[1].'}',$tmpString);
        }
        return $string;
    }

    private function normalizeUKdateString(string $string):string
    {
        $tmpString=' '.$string.' ';
        preg_match('/[^0-9]([0-3]{0,1}[0-9])[\/]([0-3]{0,1}[0-9])[\/]([0-9]{2,4})[^0-9]/',$tmpString,$match);
        if (isset($match[0])){
            // try UK format 31/08/2011
            $date=$this->createDateStr($match[1],$match[2],$match[3]);
            if ($date){
                return str_replace($match[0],'{'.$date.'}',$tmpString);
            } else {
                // try US format 08/31/2011
                $date=$this->createDateStr($match[2],$match[1],$match[3]);
                if ($date){
                    return str_replace($match[0],'{'.$date.'}',$tmpString);
                }
            }
        }
        return $string;
    }
    
    private function createDateStr(string $day, string $month, string $year):string|bool
    {
        try {
            $dateStr='';
            $dateStr.=$this->getYearStr($year);
            $dateStr.='-'.$this->getMonthStr($month);
            $dateStr.='-'.$this->getDayStr($day);
            return $dateStr;
        } catch (\Exception $e){
            return FALSE;
        }
    }

    private function getDayStr(string $day):string
    {
        $day=intval($day);
        if ($day>31 || $day<1){
            throw new \Exception('E101: Parsed day out of range.');
        }
        if ($day<10){
            return '0'.strval($day);
        } else {
            return strval($day);
        }
    }

    private function getMonthStr(string $month):string
    {
        if (!is_numeric($month)){
            if (isset(self::MONTH2NUMERIC[$month])){
                return self::MONTH2NUMERIC[$month];
            } else {
                throw new \Exception('E102: Parsed month out of range.');
            }
        }
        $month=intval($month);
        if ($month>12 || $month<1){
            throw new \Exception('E103: Parsed month out of range.');
        }
        if ($month<10){
            return '0'.strval($month);
        } else {
            return strval($month);
        }
    }

    private function getYearStr(string $year):string
    {
        if (strlen($year)===4){return $year;}
        $year=intval($year);
        if ($year>999){
            return strval($year);
        } else if ($year>99){
            return '2'.strval($year);
        } else if ($year<self::YEAR_2000_THRESHOLD){
            $year+=2000;
        } else {
            $year+=1900;
        }
        return strval($year);
    }

}
?>