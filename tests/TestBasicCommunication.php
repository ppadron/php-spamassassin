<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'SpamAssassin/Client.php';

class BasicCommunication extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sa = new SpamAssassin_Client("localhost", "783");
        $this->spam = file_get_contents(dirname(__FILE__) . "/files/spam.txt");
        $this->ham  = file_get_contents(dirname(__FILE__) . "/files/ham.txt");
    }

    public function testPing()
    {
        $this->assertEquals(true, $this->sa->ping());
    }

    public function testMultipleCallsOnSameObject()
    {
        $this->assertEquals(true, $this->sa->ping());
        $this->assertEquals(true, $this->sa->ping());
        $this->assertEquals(true, $this->sa->ping());
    }

    public function testCheckSpamMessage()
    {
        $message = file_get_contents(dirname(__FILE__) . "/files/spam.txt");
        $return = $this->sa->check($message);

        $this->assertEquals(true,   is_array($return));
        $this->assertEquals(true,   $return['is_spam']);
        $this->assertEquals(5.0,    $return['thresold']);
        $this->assertEquals(1000.0, $return['score']);
    }

    public function testCheckHamMessage()
    {
        $message = file_get_contents(dirname(__FILE__) . "/files/ham.txt");
        $return = $this->sa->check($message);

        $this->assertEquals(true,  is_array($return));
        $this->assertEquals(false, $return['is_spam']);
        $this->assertEquals(5.0,   $return['thresold']);
        $this->assertEquals(0,     $return['score']);
    }

    public function testShouldReturnProcessedSpamMessageHeaders()
    {
        $headers = $this->sa->headers($this->spam);

        $this->assertContains("X-Spam-Flag: YES", $headers);
        $this->assertContains("X-Spam-Status: Yes, score=1000.0",  $headers);
    }

    public function testReportMethodShouldReturnReportObject()
    {
        $report = $this->sa->report($this->spam);

        $this->assertContains("This is the GTUBE", $report);
        $this->assertContains("Content preview:",  $report);
        $this->assertContains("1000 GTUBE",        $report);

    }

    public function testProcess()
    {
        $result = $this->sa->process($this->spam);

        $this->assertEquals(true,   $result["is_spam"]);
        $this->assertEquals(1000.0, $result["score"]);
        $this->assertEquals(2980,   $result["content_lenght"]);

        $this->assertContains(
            "Content-Description: original message before SpamAssassin",
            $result["message"]
        );
    }

}
