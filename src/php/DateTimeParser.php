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
        'DD Month YYYY'=>'/[0-3]{0,1}[0-9][.]{0,1}[a-zäüö]{3,15}[0-9]{2,4}/',
        'Month MM.,YYYY'=>'/[a-zäüö]{3,15}[0-3]{0,1}[0-9][.,]{1,2}[0-9]{2,4}/',
        'MM-DD-YYYY'=>'/([0-1][0-9])-([0-3][0-9])-([0-9]{4})/',
        'YYYY-MM-DD'=>'/([0-9]{4})-([0-9]{2})-([0-9]{2})/',
        'DD/MM/YYYY'=>'/([0-3]{0,1}[0-9])\/([0-3]{0,1}[0-9])\/([0-9]{2,4})/',
        'DD.MM.YYYY'=>'/([0-3]{0,1}[0-9])[.]([0-3]{0,1}[0-9])[.]([1-2][0-9][0-9][0-9])/',
        'DD.MM.YY'=>'/([0-3]{0,1}[0-9])[.]([0-3]{0,1}[0-9])[.]([0-9][0-9])/',
        'YYYYMMDD'=>'/([12][0-9]{3})([01][0-9])([0-3][0-9])/',
        'YYYY年MM月DD'=>'/([12][0-9]{3})[年 ]{1,3}([01]{0,1}[0-9])[月 ]{1,3}([0-3]{0,1}[0-9])[日号 ]{1,2}/',
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
    
    private $initDateTime=['date'=>'0000-01-01','time'=>'12:00:00'];

    private const YEAR_2000_THRESHOLD=50;

    private $dateTime=NULL;

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
        $dateTimeArr['isValid']=$this->isValid;
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
    
    final public function setInitDateTime(string $dateTime)
    {
        preg_match('/([0-9]{4}-[0-1][0-9]-[0-3][0-9]) ([0-2][0-9]:[0-5][0-9]:[0-5][0-9])/',$dateTime,$match);
        if (isset($match[0])){
            $this->initDateTime=['date'=>$match[1],'time'=>$match[2]];
        } else {
            throw new \Exception('E104: Invalid initDateTime "'.$dateTime.'". Required format is "Y-m-d H:i:s"');
        }
    }

    final public function setFromTimestamp($timestamp)
    {
        $this->isValid=!empty($timestamp);
        if (empty($timestamp)){
            $dateTimetStr=implode(' ',$this->initDateTime);
            $this->dateTime=new \DateTime($dateTimetStr);
        } else {
            $timestamp=intval($timestamp);
            $this->dateTime=new \DateTime('@'.$timestamp);
        }
    }

    final public function setFromExcelTimestamp($excelTimestamp)
    {
        $this->isValid=!empty($timestamp);
        if (empty($excelTimestamp)){
            $dateTimetStr=implode(' ',$this->initDateTime);
            $this->dateTime=new \DateTime($dateTimetStr);
        } else {
            $timestamp=intval(86400*(floatval($excelTimestamp)-25569));
            $this->dateTime=new \DateTime('@'.$timestamp);
        }
    }

    final public function setFromString(string $string,$timeZone=NULL):bool
    {
        try {
            $dateTimeFromString=new \DateTime($string,$timeZone);
        } catch (\Exception $e){
            $dateTimeFromString=NULL;
        }
        // parse timezone
        $dateTimeArr['timezone']=$this->string2timezoneString($string);
        $timeZone=$timeZone??new \DateTimeZone($dateTimeArr['timezone']);
        // parse offset
        $dateTimeArr['offset']=$this->string2offset($string);
        // parse date
        $comps=$this->dateString2comps($comps['string']??$string);
        $dateTimeArr['date']=$this->dateComps2date($comps);
        // parse time
        $comps=$this->timeString2comps($comps['string']??$string);
        $dateTimeArr['time']=$this->timeComps2time($comps);
        // compile all
        $dateTimeStr=trim($dateTimeArr['date'].' '.$dateTimeArr['time'].' '.$dateTimeArr['offset']);
        try {
            $dateTimeFromParsedString=new \DateTime($dateTimeStr,$timeZone);
        } catch (\Exception $e){
            $dateTimeFromParsedString=NULL;
        }
        $dateTime=(strlen($string)>13)?($dateTimeFromString??$dateTimeFromParsedString):($dateTimeFromParsedString??$dateTimeFromString);
        if (empty($dateTime)){
            $initDateTime=implode(' ',$this->initDateTime);
            $this->dateTime=new \DateTime($initDateTime);
        } else {
            $this->dateTime=$dateTime;
        }
        $this->isValid=!empty($dateTime);
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
     *  Timezone string methods 
     */
    private function string2timezoneString(string $string):string
    {
        $string=strtolower($string);
        foreach(\DateTimeZone::listIdentifiers() as $fullName){
            $nameComps=explode('/',strtolower($fullName));
            $name=array_pop($nameComps);
            if (strpos($string,$fullName)!==FALSE){
                $timeZone=$fullName;
                break;
            }
            if (strpos($string,$name)!==FALSE){
                $timeZone=$fullName;
            }
        }
        return $timeZone=$timeZone??self::DEFAULT_TIMEZONE;
    }

    private function string2offset(string $string):string
    {
        preg_match('/[+\-]{1}[0-9]{2}:{0,1}[0-9]{2}/',$string,$match);
        return $match[0]??'';
    }

    /**
     *  Time string methods - detection of different time formats 
     */

    private function timeString2comps(string $timeString):array
    {
        $initComps=explode(':',$this->initDateTime['time']);
        $comps=['hour'=>$initComps[0],'min'=>$initComps[1],'sec'=>$initComps[2],'type'=>'','string'=>$timeString,'exception'=>''];
        // filter raw string
        $timeString=strtolower($timeString);
        $timeString=preg_replace('/\s/','',$timeString);
        foreach(self::TIME_FILTER as $type=>$filter){
            preg_match($filter,$timeString,$match);
            if (empty($match[0])){continue;}
            $comps=match($type){
                'HH.MM pm'=>$this->normalizeUKtime($match),
                'HH:MM Uhr'=>['hour'=>intval($match[1]),'min'=>intval($match[2]),'sec'=>0,'type'=>$type],
                'HHhMM'=>['hour'=>intval($match[1]),'min'=>intval($match[2]),'sec'=>0,'type'=>$type],
                'HH:MM:SS'=>['hour'=>intval($match[1]),'min'=>intval($match[2]),'sec'=>intval($match[3]),'type'=>$type],
                '12noon'=>['hour'=>12,'min'=>0,'sec'=>0,'type'=>$type],
                '12midnight'=>['hour'=>0,'min'=>0,'sec'=>0,'type'=>$type],
            };
            $comps['string']=str_replace($match[0],'',$timeString);
            $comps['exception']=($comps['hour']>24)?', Parsed hours out of range':'';
            $comps['exception']=($comps['min']>69)?', Parsed minutes out of range':'';
            $comps['exception']=($comps['sec']>69)?', Parsed seconds out of range':'';
            $comps['exception']=trim($comps['exception'],' ,');
            break;
        }
        return $comps;
    }

    private function normalizeUKtime($match):array
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
    
    private function timeComps2time($timeComps):string
    {
        $timeComps['sec']=str_pad(strval($timeComps['sec']),2,'0',STR_PAD_LEFT);
        $timeComps['min']=str_pad(strval($timeComps['min']),2,'0',STR_PAD_LEFT);
        $timeComps['hour']=str_pad(strval($timeComps['hour']),2,'0',STR_PAD_LEFT);
        return $timeComps['hour'].':'.$timeComps['min'].':'.$timeComps['sec'];
    }

    /**
     *  Date string methods - detection of different date formats, verification of ranges and formating
     */

    function dateString2comps(string $dateString):array
    {
        $dateComps=['day'=>FALSE,'month'=>FALSE,'year'=>FALSE,];
        // filter raw string
        $dateString=strtolower($dateString);
        $dateString=preg_replace('/\s/u','',$dateString);
        foreach(self::DATE_FILTER as $format=>$filter){
            preg_match($filter,$dateString,$match);
            if (empty($match[0])){continue;}
            $string=str_replace($match[0],'',$dateString);
            if ($format==='YYYYMMDD'){
                return ['day'=>intval($match[3]),'month'=>intval($match[2]),'year'=>intval($match[1]),'string'=>$string];
            } else if ($format==='DD.MM.YYYY' || $format==='DD.MM.YY'){
                return ['day'=>intval($match[1]),'month'=>intval($match[2]),'year'=>intval($match[3]),'string'=>$string];
            } else if ($format==='YYYY-MM-DD'){
                return ['day'=>intval($match[3]),'month'=>intval($match[2]),'year'=>intval($match[1]),'string'=>$string];
            } else if ($format==='MM-DD-YYYY'){
                return ['day'=>intval($match[2]),'month'=>intval($match[1]),'year'=>intval($match[3]),'string'=>$string];
            } else if ($format==='YYYY年MM月DD'){
                return ['day'=>intval($match[3]),'month'=>intval($match[2]),'year'=>intval($match[1]),'string'=>$string];
            } else if ($format==='DD/MM/YYYY'){
                $A=intval($match[1]);
                $B=intval($match[2]);
                $C=intval($match[3]);
                if ($A>12){
                    return ['day'=>$A,'month'=>$B,'year'=>$C,];
                } else if ($B>12){
                    return ['day'=>$B,'month'=>$A,'year'=>$C,];
                } else {
                    return (self::DATE_FORMAT_IF_IN_DOUBT_UK)?['day'=>$A,'month'=>$B,'year'=>$C,'string'=>$string]:['day'=>$B,'month'=>$A,'year'=>$C,'string'=>$string];
                }
            }
        }
        // if name of month is provided
        foreach(self::MONTHS_NEEDLES as $needle=>$month){
            if (strpos($dateString,$needle)===FALSE){continue;}
            $dateComps['month']=self::MONTH2NUMERIC[$month];
            $dateString=str_replace($needle,'|',$dateString);
            $comps=preg_split('/[^0-9]+/',trim($dateString,'|'),-1,PREG_SPLIT_NO_EMPTY);
            $dateComps['day']=array_shift($comps);
            $dateComps['day']=intval($dateComps['day']);
            $dateComps['year']=array_shift($comps);
            $dateComps['year']=substr($dateComps['year']??'',0,4);
            $dateComps['year']=intval($dateComps['year']);
            return $dateComps;
            break;
        }
        return $dateComps;
    }

    private function dateComps2date(array $dateComps):string
    {
        if (empty($dateComps['year']) || empty($dateComps['month']) || empty($dateComps['day'])){
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