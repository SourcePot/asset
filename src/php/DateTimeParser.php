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
    private const SYSTEM_TIMEZONE='UTC';

    private const DATE_FORMAT_IF_IN_DOUBT_UK=TRUE;

    private const TIME_FILTER=[
        'HH:MM:SS'=>'/([0-2][0-9]):([0-5][0-9]):([0-5][0-9])/',
        'HH.MM pm'=>'/([0-1]{0,1}[0-9])[:.]([0-5][0-9]){0,1}([ap][.]{0,1}[m])/',
        'HH:MM Uhr'=>'/([0-2]{0,1}[0-9])[:.]([0-5][0-9])[uhr]{0,3}/',
        'HHhMM'=>'/([0-1]{0,1}[0-9])[h]([0-5][0-9])/',
        '12noon'=>'/(12)noon/',
        '12midnight'=>'/(12)midnight/',
    ];

    private const DATE_FILTER=[
        'DD Month YYYY'=>'/([0-3]{0,1}[0-9])[.\s]{0,2}([a-zäüö]{3,15})\s{0,1}([0-9]{2,4})/',
        'Month DD.,YYYY'=>'/([a-zäüö]{3,15})\s{0,1}([0-3]{0,1}[0-9])[., ]{1,3}([0-9]{2,4})/',
        'MM-DD-YYYY'=>'/([0-1][0-9])[-\s]{1,2}([0-3][0-9])[-\s]{1,2}([0-9]{4})/',
        'YYYY-MM-DD'=>'/([0-9]{4})[-\s]{1,2}([0-9]{2})[-\s]{1,2}([0-9]{2})/',
        'DD/MM/YYYY'=>'/([0-3]{0,1}[0-9])\/([0-3]{0,1}[0-9])\/([0-9]{2,4})/',
        'DD.MM.YYYY'=>'/([0-3]{0,1}[0-9])[.\s]{1,2}([0-3]{0,1}[0-9])[.\s]{1,2}([1-2][0-9][0-9][0-9])/',
        'DD.MM.YY'=>'/([0-3]{0,1}[0-9])[.\s]{1,2}([0-3]{0,1}[0-9])[.\s]{1,2}([0-9][0-9])/',
        'YYYYMMDD'=>'/([12][0-9]{3})([01][0-9])([0-3][0-9])/',
        'YYYY年MM月DD'=>'/([0-9]{4})[年 ]{1,3}([01]{0,1}[0-9])[月 ]{1,3}([0-3]{0,1}[0-9])[日号 ]{1,2}/',
    ];

    private const MONTHS_NEEDLES=[
        'january'=>'january','januar'=>'january','enero'=>'january','janvier'=>'january','jan.'=>'january','jan'=>'january',
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
    private const MONTHS_DICT_FR=['01'=>'janvier','02'=>'février','03'=>'mars','04'=>'avril','05'=>'mai','06'=>'juin','07'=>'juillet','08'=>'août','09'=>'septembre','10'=>'octobre','11'=>'novembre','12'=>'décembre'];
    
    private $initDateTime=['date'=>'0000-01-01','time'=>'00:00:00'];

    private const YEAR_2000_THRESHOLD=50;

    private $dateTime=NULL;
    private $orgString='';
    private $orgTimezone='';

    private $isValid=FALSE;

    function __construct()
    {
        $this->dateTime=new \DateTime('now');
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
        return $this->isValid;
    }

    final public function getDateTime():\DateTime
    {
        return $this->dateTime;
    }

    final public function getArray():array
    {
        $this->setTimezone(self::SYSTEM_TIMEZONE);
        $dateTimeArr=[];
        $dateTimeArr['System short']=$this->dateTime->format('Y-m-d');
        $dateTimeArr['System']=$this->dateTime->format('Y-m-d H:i:s');
        $dateTimeArr['YYYYMMDD']=$this->dateTime->format('Ymd');
        $dateTimeArr['Timezone']=$this->dateTime->getTimezone()->getName();
        $dateTimeArr['Timestamp']=$this->dateTime->getTimestamp();
        $dateTimeArr['US']=$this->dateTime->format('m-d-Y');
        $dateTimeArr['UK']=$this->dateTime->format('d/m/Y');
        $dateTimeArr['DE']=$this->dateTime->format('d.m.Y');
        $dateTimeArr['CN']=$this->dateTime->format('Y年m月d日');
        $dateTimeArr['RFC2822']=$this->dateTime->format(\DateTimeInterface::RFC2822);
        $dateTimeArr['day']=$this->dateTime->format('d');
        $dateTimeArr['month']=$this->dateTime->format('m');
        $dateTimeArr['year']=$this->dateTime->format('Y');
        $dateTimeArr['US long']=$this->dateTime->format('F').' '.$this->dateTime->format('j').', '.$this->dateTime->format('Y');
        $dateTimeArr['UK long']=$this->dateTime->format('j').' '.$this->dateTime->format('F').' '.$this->dateTime->format('Y');
        $dateTimeArr['DE long']=$this->dateTime->format('j').'. '.self::MONTHS_DICT_DE[$this->dateTime->format('m')].' '.$this->dateTime->format('Y');
        $dateTimeArr['FR long']='le '.$this->dateTime->format('j').' '.self::MONTHS_DICT_FR[$this->dateTime->format('m')].' '.$this->dateTime->format('Y');
        $dateTimeArr['ES long']=$this->dateTime->format('j').' de '.self::MONTHS_DICT_ES[$this->dateTime->format('m')].' de '.$this->dateTime->format('Y');
        $dateTimeArr['orgString']=$this->orgString;
        $dateTimeArr['orgTimezone']=$this->orgTimezone;
        $dateTimeArr['isValid']=$this->isValid;
        return $dateTimeArr;
    }

    /**
     * Setter methods
     */

    final public function set(string|int|\DateTime $dateTime)
    {
        $this->orgTimezone='';
        if (is_object($dateTime)){
            $this->orgString=get_class($dateTime);
            $this->dateTime=$dateTime;
        } else if (is_integer($dateTime)){
            $this->orgString=strval($dateTime);
            $this->setFromTimestamp($dateTime);
        } else {
            $this->orgString=strval($dateTime);
            $this->setFromString($dateTime);
        }
    }
    
    final public function setInitDateTime(string $dateTime)
    {
        preg_match('/([0-9]{4}-[0-1][0-9]-[0-3][0-9]) ([0-2][0-9]:[0-5][0-9]:[0-5][0-9])/',$dateTime,$match);
        if (isset($match[0])){
            $this->initDateTime=['date'=>$match[1],'time'=>$match[2]];
        } else {
            throw new \Exception('E104: Invalid initDateTime "'.$dateTime.'". Required format is "Y-m-d H:i:s"');
        }
    }

    final public function setFromTimestamp(string|int $timestamp)
    {
        $this->orgString=strval($timestamp);
        $this->isValid=!empty($timestamp);
        if (empty($timestamp)){
            $dateTimetStr=implode(' ',$this->initDateTime);
            $this->dateTime=new \DateTime($dateTimetStr);
        } else {
            $timestamp=intval($timestamp);
            $this->dateTime=new \DateTime('@'.$timestamp);
        }
    }

    final public function setFromExcelTimestamp(string|int $excelTimestamp)
    {
        $this->orgString=strval($excelTimestamp);
        $this->isValid=!empty($excelTimestamp);
        if (empty($excelTimestamp)){
            $dateTimetStr=implode(' ',$this->initDateTime);
            $this->dateTime=new \DateTime($dateTimetStr);
        } else {
            $timestamp=intval(86400*(floatval($excelTimestamp)-25569));
            $this->dateTime=new \DateTime('@'.$timestamp);
        }
    }

    final public function setFromString(string $string,$timezone=NULL):bool
    {
        $this->orgString=strval($string);
        $dateTimeArr=['string'=>strtolower($string),'isValid'=>FALSE];
        // parse timezone
        $dateTimeArr=$this->addTimezone($dateTimeArr);
        $dateTimeArr['timezone']=$timezone??new \DateTimeZone($dateTimeArr['timezone']);
        // parse offset
        $dateTimeArr=$this->addOffset($dateTimeArr);
        // parse date
        $dateTimeArr=$this->addDate($dateTimeArr);
        // parse time
        $dateTimeArr=$this->addTime($dateTimeArr);
        // compile all
        $dateTimeStr=trim($dateTimeArr['date'].' '.$dateTimeArr['time'].' '.$dateTimeArr['offset']);
        $this->dateTime=NULL;
        try {
            $this->dateTime=new \DateTime($dateTimeStr,$dateTimeArr['timezone']);
            $this->orgTimezone=$dateTimeArr['timezone']->getName();
        } catch (\Exception $e){
            try {
                $this->dateTime=new \DateTime($string,$dateTimeArr['timezone']);
                $this->orgTimezone=$dateTimeArr['timezone']->getName();
            } catch (\Exception $e){
                $dateTimeArr['isValid']=FALSE;
                $initDateTime=implode(' ',$this->initDateTime);
                $this->dateTime=new \DateTime($initDateTime);
                $this->orgTimezone=$this->dateTime->getTimezone()->getName();
            }    
        }
        $this->isValid=$dateTimeArr['isValid'];
        return $this->isValid();
    }

    final public function setTimezone(string|\DateTimeZone $timezone)
    {
        if (!is_object($timezone)){
            $timezone=new \DateTimeZone($timezone);
        }
        $this->dateTime->setTimezone($timezone);
    }

    /**
     *  Timezone string methods 
     */
    private function addTimezone(array $dateTimeArr):array
    {
        $dateTimeArr['timezone']=self::DEFAULT_TIMEZONE;
        foreach(\DateTimeZone::listIdentifiers() as $fullName){
            $nameComps=explode('/',$fullName);
            $name=array_pop($nameComps);
            if (stripos($dateTimeArr['string'],$fullName)!==FALSE){
                $dateTimeArr['timezone']=$fullName;
                $dateTimeArr['string']=str_replace($fullName,'',$dateTimeArr['string']);
                return $dateTimeArr;
            } else if (stripos($dateTimeArr['string'],$name)!==FALSE){
                $dateTimeArr['timezone']=$name;
                $dateTimeArr['string']=str_replace($name,'',$dateTimeArr['string']);
                return $dateTimeArr;
            }
        }
        return $dateTimeArr;
    }

    private function addOffset(array $dateTimeArr):array
    {
        preg_match('/[+\-]{1}[0-9]{2}:{0,1}[0-9]{2}/',$dateTimeArr['string'],$match);
        if (empty($match[0])){
            $dateTimeArr['offset']='';
            return $dateTimeArr;
        } else {
            $dateTimeArr['offset']=$match[0];
            $dateTimeArr['string']=str_replace($match[0],'',$dateTimeArr['string']);
            return $dateTimeArr;
        }
    }

    /**
     *  Time string methods - detection of different time formats 
     */

    private function addTime(array $dateTimeArr):array
    {
        $initComps=explode(':',$this->initDateTime['time']);
        $comps=['hour'=>$initComps[0],'min'=>$initComps[1],'sec'=>$initComps[2],'type'=>''];
        // filter raw string
        foreach(self::TIME_FILTER as $type=>$filter){
            preg_match($filter,$dateTimeArr['string'],$match);
            if (empty($match[0])){
                continue;
            }
            $comps=match($type){
                'HH.MM pm'=>$this->normalizeUKtime($match),
                'HH:MM Uhr'=>['hour'=>intval($match[1]),'min'=>intval($match[2]),'sec'=>0,'type'=>$type],
                'HHhMM'=>['hour'=>intval($match[1]),'min'=>intval($match[2]),'sec'=>0,'type'=>$type],
                'HH:MM:SS'=>['hour'=>intval($match[1]),'min'=>intval($match[2]),'sec'=>intval($match[3]),'type'=>$type],
                '12noon'=>['hour'=>12,'min'=>0,'sec'=>0,'type'=>$type],
                '12midnight'=>['hour'=>0,'min'=>0,'sec'=>0,'type'=>$type],
            };
            $dateTimeArr['string']=str_replace($match[0],'',$dateTimeArr['string']);
            break;
        }
        $dateTimeArr['time']=$this->timeComps2time($comps);
        return $dateTimeArr;
    }

    private function normalizeUKtime(array $match):array
    {
        $timeComps=['hour'=>intval($match[1]),'min'=>intval($match[2]),'sec'=>0,'type'=>'HH.MM pm','function'=>__FUNCTION__];
        $amPm=preg_replace('/[^apm]/','',$match[3]);
        if ($amPm==='am'){
            $timeComps['hour']=($timeComps['hour']===12)?0:$timeComps['hour'];
        } else {
            $timeComps['hour']=($timeComps['hour']===12)?12:($timeComps['hour']+12);
        }
        return $timeComps;
    }
    
    private function timeComps2time(array $timeComps):string
    {
        $timeComps['sec']=str_pad(strval($timeComps['sec']),2,'0',STR_PAD_LEFT);
        $timeComps['min']=str_pad(strval($timeComps['min']),2,'0',STR_PAD_LEFT);
        $timeComps['hour']=str_pad(strval($timeComps['hour']),2,'0',STR_PAD_LEFT);
        return $timeComps['hour'].':'.$timeComps['min'].':'.$timeComps['sec'];
    }

    /**
     *  Date string methods - detection of different date formats, verification of ranges and formating
     */

    function addDate(array $dateTimeArr):array
    {
        $dateArr=['day'=>FALSE,'month'=>FALSE,'year'=>FALSE];
        foreach(self::DATE_FILTER as $format=>$filter){
            preg_match($filter,$dateTimeArr['string'],$match);
            if (empty($match[0])){
                continue;
            }
            $dateTimeArr['string']=str_replace($match[0],'',$dateTimeArr['string']);
            if ($format==='YYYYMMDD'){
                $dateArr=['day'=>intval($match[3]),'month'=>intval($match[2]),'year'=>intval($match[1])];
            } else if ($format==='DD.MM.YYYY' || $format==='DD.MM.YY'){
                $dateArr=['day'=>intval($match[1]),'month'=>intval($match[2]),'year'=>intval($match[3])];
            } else if ($format==='YYYY-MM-DD'){
                $dateArr=['day'=>intval($match[3]),'month'=>intval($match[2]),'year'=>intval($match[1])];
            } else if ($format==='MM-DD-YYYY'){
                $dateArr=['day'=>intval($match[2]),'month'=>intval($match[1]),'year'=>intval($match[3])];
            } else if ($format==='YYYY年MM月DD'){
                $dateArr=['day'=>intval($match[3]),'month'=>intval($match[2]),'year'=>intval($match[1])];
            } else if ($format==='DD/MM/YYYY'){
                $A=intval($match[1]);
                $B=intval($match[2]);
                $C=intval($match[3]);
                if ($A>12){
                    $dateArr=['day'=>$A,'month'=>$B,'year'=>$C];
                } else if ($B>12){
                    $dateArr=['day'=>$B,'month'=>$A,'year'=>$C];
                } else {
                    $dateArr=(self::DATE_FORMAT_IF_IN_DOUBT_UK)?['day'=>$A,'month'=>$B,'year'=>$C]:['day'=>$B,'month'=>$A,'year'=>$C];
                }
            } else if ($format==='DD Month YYYY'){
                $month=$this->monthName2number($match[2]);
                $dateArr=['day'=>intval($match[1]),'month'=>intval($month),'year'=>intval($match[3])];
            } else if ($format==='Month DD.,YYYY'){
                $month=$this->monthName2number($match[1]);
                $dateArr=['day'=>intval($match[2]),'month'=>intval($month),'year'=>intval($match[3])];
            }
            if (!empty($dateArr['day']) && !empty($dateArr['month']) && !empty($dateArr['year'])){
                $dateTimeArr['isValid']=TRUE;
                break;
            }
        }
        $dateTimeArr['date']=$this->dateComps2date($dateArr);
        return $dateTimeArr;
    }

    private function monthName2number(string $month):string
    {
        foreach(self::MONTHS_NEEDLES as $needle=>$monthName){
            if (strpos($month,$needle)!==FALSE){
                return self::MONTH2NUMERIC[$monthName];
            }
        }
        return '';
    }

    private function dateComps2date(array $dateComps):string
    {
        if ($dateComps['year']===FALSE || $dateComps['month']===FALSE || $dateComps['day']===FALSE){
            return $this->initDateTime['date'];
        }
        if ($dateComps['year']<self::YEAR_2000_THRESHOLD){
            $dateComps['year']='20'.str_pad(strval($dateComps['year']),2,'0',STR_PAD_LEFT);
        } else if ($dateComps['year']<100){
            $dateComps['year']='19'.str_pad(strval($dateComps['year']),2,'0',STR_PAD_LEFT);
        } else {
            $dateComps['year']=str_pad(strval($dateComps['year']),4,'0',STR_PAD_LEFT);
        }
        $dateComps['month']=str_pad(strval($dateComps['month']),2,'0',STR_PAD_LEFT);
        $dateComps['day']=str_pad(strval($dateComps['day']),2,'0',STR_PAD_LEFT);
        return $dateComps['year'].'-'.$dateComps['month'].'-'.$dateComps['day'];
    }
}
?>