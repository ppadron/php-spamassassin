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
/*
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

    public function testProcess()
    {
        echo $this->sa->process($this->spam);
    }

    public function testShouldReturnProcessedSpamMessageHeaders()
    {
        $headers = $this->sa->headers($this->spam);

        $this->assertContains("X-Spam-Flag: YES", $headers);
        $this->assertContains("Subject: [SPAM]",  $headers);
    }

    public function testReportMethodShouldReturnReportObject()
    {
        $report = $this->sa->report($this->spam);

        $this->assertContains("This is the GTUBE", $report);
        $this->assertContains("Content preview:",  $report);
        $this->assertContains("1000 GTUBE",        $report);

    }
*/
}
