<?php
class SpamReportTest extends BaseTestCase
{
    public function testShouldReturnReportIfMessageIsSpam()
    {
        $report = $this->sa->getSpamReport($this->gtube);

        $this->assertContains("Content preview:",  $report->message);
        $this->assertContains("1000 GTUBE",        $report->message);
        $this->assertTrue($report->isSpam);
    }

    public function testShouldReturnNullIfMessageIsHam()
    {
        $message = $this->getMessage('Ham_testReportWithHamMessage.txt');
        $report = $this->sa->getSpamReport($message);
        $this->assertFalse($report->isSpam);
    }

}
