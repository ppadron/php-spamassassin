<?php

require_once 'BaseTestCase.php';

class SpamTest extends BaseTestCase
{
    public function testShouldReturnProcessedSpamMessageHeaders()
    {
        $message = $this->getMessage('Spam_GTUBE.txt');
        $headers = $this->sa->headers($message);

        $this->assertContains("X-Spam-Flag: YES", $headers);
        $this->assertContains("X-Spam-Status: Yes, score=1000.0",  $headers);
    }

    public function testReportMethodShouldReturnReportObject()
    {
        $report = $this->sa->getSpamReport($this->gtube);

        $this->assertContains("This is the GTUBE", $report);
        $this->assertContains("Content preview:",  $report);
        $this->assertContains("1000 GTUBE",        $report);
    }

    public function testReportWithHamMessage()
    {
        $message = $this->getMessage('Ham_testReportWithHamMessage.txt');
        $report = $this->sa->getSpamReport($message);
        $this->assertEquals(null, $report);
    }

}
