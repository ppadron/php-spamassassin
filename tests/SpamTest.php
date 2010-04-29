<?php

require_once 'BaseTestCase.php';

class SpamTest extends BaseTestCase
{
    public function testCheckSpamMessage()
    {
        $message = $this->getMessage('Spam_testCheckSpamMessage.txt');
        $return  = $this->sa->check($message);

        $this->assertTrue($return->isSpam);
        $this->assertEquals(5.0,    $return->thresold);
        $this->assertEquals(1000.0, $return->score);
    }

    public function testCheckHamMessage()
    {
        $message = $this->getMessage('Ham_testCheckHamMessage.txt');
        $return  = $this->sa->check($message);

        $this->assertFalse($return->isSpam);
        $this->assertTrue($return->score < $return->thresold);
    }

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

    public function testProcess()
    {
        $result = $this->sa->process($this->gtube);

        $this->assertEquals(true,   $result->isSpam);
        $this->assertEquals(1000.0, $result->score);

        $this->assertContains(
            "Content-Description: original message before SpamAssassin",
            $result->output
        );
    }

    public function testSymbols()
    {
        $result = $this->sa->symbols($this->gtube);
        $this->assertEquals(true, in_array('GTUBE', $result));
    }

}
