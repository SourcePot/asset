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
        $testArr=['31 Mai 2013'=>'2013-05-31','Mai 2, 2003'=>'2003-05-02','July 15., 1932'=>'1932-07-15','31 Mai 2013'=>'2013-05-31','23/2/1997'=>'1997-02-23','2/23/2007'=>'2007-02-23','2-23-2007'=>'2007-02-23','2-3-2007'=>'2007-02-03','12.2.2015'=>'2015-02-12','13.2.15'=>'2015-02-13','14.2.95'=>'1995-02-14',];

        $dateTimeParserObj=new \SourcePot\Asset\DateTimeParser();
        
        foreach($testArr as $testcase=>$shouldBe){
            $dateTimeParserObj->setFromString($testcase);
            $resultArr=$dateTimeParserObj->getArray();
            $this->assertSame($shouldBe, $resultArr['System short']);
        }
    }

    public function testDateTimeParser(): void
    {
        $testArr=['12.2.2015 2.15am'=>'2015-02-12 02:15:00','13.2.15 2.15pm'=>'2015-02-13 14:15:00','14.2.95 13:21:55'=>'1995-02-14 13:21:55',];

        $dateTimeParserObj=new \SourcePot\Asset\DateTimeParser();
        
        foreach($testArr as $testcase=>$shouldBe){
            $dateTimeParserObj->setFromString($testcase);
            $resultArr=$dateTimeParserObj->getArray();
            $this->assertSame($shouldBe, $resultArr['System']);
        }
    }


}