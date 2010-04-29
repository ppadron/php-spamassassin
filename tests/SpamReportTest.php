<?php

require_once 'BaseTestCase.php';

class SpamReportTest extends BaseTestCase
{
    public function testShouldReturnReportIfMessageIsSpam()
    {
        $report = $this->sa->getSpamReport($this->gtube);

        $this->assertContains("This is the GTUBE", $report);
        $this->assertContains("Content preview:",  $report);
        $this->assertContains("1000 GTUBE",        $report);
    }

    public function testShouldReturnNullIfMessageIsHam()
    {
        $message = $this->getMessage('Ham_testReportWithHamMessage.txt');
        $report = $this->sa->getSpamReport($message);
        $this->assertEquals(null, $report);
    }

}
