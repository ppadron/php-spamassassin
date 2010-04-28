<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'SpamAssassin/Client.php';

class BasicCommunication extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        /* @see phpunit.xml */
        $this->sa = new SpamAssassin_Client(
            $GLOBALS["PHPUNIT_SA_HOST"],
            (int) $GLOBALS["PHPUNIT_SA_PORT"],
            $GLOBALS["PHPUNIT_SA_USER"]
        );

        $this->gtube = $this->_getMessage('Spam_GTUBE.txt');
    }

    private function _getMessage($filename)
    {
        return file_get_contents(dirname(__FILE__) . '/files/' . $filename);
    }

    public function testPing()
    {
        $this->assertEquals(true, $this->sa->ping());
    }

    public function testCheckSpamMessage()
    {
        $message = $this->_getMessage('Spam_testCheckSpamMessage.txt');
        $return  = $this->sa->check($message);

        $this->assertTrue($return->isSpam);
        $this->assertEquals(5.0,    $return->thresold);
        $this->assertEquals(1000.0, $return->score);
    }

    public function testCheckHamMessage()
    {
        $message = $this->_getMessage('Ham_testCheckHamMessage.txt');
        $return  = $this->sa->check($message);

        $this->assertFalse($return->isSpam);
        $this->assertTrue($return->score < $return->thresold);
    }

    public function testShouldReturnProcessedSpamMessageHeaders()
    {
        $message = $this->_getMessage('Spam_GTUBE.txt');
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
        $message = $this->_getMessage('Ham_testReportWithHamMessage.txt');
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

    public function testLearnMessageAsSpam()
    {
        $this->assertTrue($this->sa->learn($this->gtube, SpamAssassin_Client::LEARN_SPAM));
        $this->assertTrue($this->sa->learn($this->gtube, SpamAssassin_Client::LEARN_FORGET));
    }

    public function testLearnMessageAsHam()
    {

        $message = $this->_getMessage('Ham_testLearnMessageAsHam.txt');

        $this->assertTrue($this->sa->learn($message, SpamAssassin_Client::LEARN_HAM));
        $this->assertTrue($this->sa->learn($message, SpamAssassin_Client::LEARN_FORGET));
    }

}
