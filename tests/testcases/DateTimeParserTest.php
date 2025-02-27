<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DateTimeParserTest extends TestCase
{
    public function testInvalidDate(): void
    {
        
        $dateTimeParserObj=new \SourcePot\Asset\DateTimeParser();
        $testString='abcdef ghUZGJI457 56sfd fdgsfdg4e465r';
        $isValid=$dateTimeParserObj->setFromString($testString);
        $this->assertSame(FALSE, $isValid);
    }

    public function testDateParser(): void
    {
        $testArr=['31 Mai 2013'=>'2013-05-31',
                  'Mai 2, 2003'=>'2003-05-02',
                  'July 15., 1932'=>'1932-07-15',
                  '31 Mai 2013'=>'2013-05-31',
                  '23/2/1997'=>'1997-02-23',
                  '2/23/2007'=>'2007-02-23',
                  '2-23-2007'=>'2007-02-23',
                  '2-3-2007'=>'2007-02-03',
                  '12.2.2015'=>'2015-02-12',
                  '13.2.15'=>'2015-02-13',
                  '14.2.95'=>'1995-02-14',
                  '20011105'=>'2001-11-05',
                ];

        $dateTimeParserObj=new \SourcePot\Asset\DateTimeParser();
        
        foreach($testArr as $testcase=>$shouldBe){
            $dateTimeParserObj->setFromString(strval($testcase));
            $resultArr=$dateTimeParserObj->getArray();
            $this->assertSame($shouldBe, $resultArr['System short']);
        }
    }

    public function testDateTimeParser(): void
    {
        $testArr=['on 12.2.2015 at 2.15am will be the meeting'=>'2015-02-12 02:15:00',
                  '13.2.15 2.15pm'=>'2015-02-13 14:15:00',
                  '14.2.95 13:21:55'=>'1995-02-14 13:21:55',
                  '14/2/95 13:21:55'=>'1995-02-14 13:21:55',
                  'test 02-14-95 1:21pm'=>'1995-02-14 13:21:00',
                  '30 avril 2023 13:21:55'=>'2023-04-30 13:21:55',
                  'February 14., 1995 13:21:55'=>'1995-02-14 13:21:55',
                 ];

        $dateTimeParserObj=new \SourcePot\Asset\DateTimeParser();
        
        foreach($testArr as $testcase=>$shouldBe){
            $dateTimeParserObj->setFromString($testcase);
            $isValid=$dateTimeParserObj->isValid();
            $this->assertSame(TRUE, $isValid);
            $resultArr=$dateTimeParserObj->getArray();
            $this->assertSame($shouldBe, $resultArr['System']);
        }
    }

    public function testDateTimeParserTimezone(): void
    {
        $teststring='13.2.2000 4.15pm';
        $testArr=['Asia/Tokyo'=>'2000-02-14 00:15:00',
                  'Europe/Riga'=>'2000-02-13 17:15:00',
                  'Europe/Berlin'=>'2000-02-13 16:15:00',
                  'Europe/London'=>'2000-02-13 15:15:00'
                ];

        $dateTimeParserObj=new \SourcePot\Asset\DateTimeParser();
        $dateTimeParserObj->setFromString($teststring);

        foreach($testArr as $timeZone=>$shouldBe){
            $dateTimeParserObj->setTimezone($timeZone);
            $resultArr=$dateTimeParserObj->getArray();
            $this->assertSame($shouldBe, $resultArr['System']);
        }
    }

    public function testDateTimeParserIsValid(): void
    {
        $teststring='jdfgsd54 2342jh234 dgdsgd dgs';
        
        $dateTimeParserObj=new \SourcePot\Asset\DateTimeParser();
        $dateTimeParserObj->setFromString($teststring);
        $this->assertSame(FALSE, $dateTimeParserObj->isValid());
    }

}