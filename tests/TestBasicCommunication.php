<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'SpamAssassin/Client.php';

class BasicCommunication extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sa = new SpamAssassin_Client("localhost", "783");
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

    public function testProcessSpamMessage()
    {
        $message = file_get_contents(dirname(__FILE__) . "/files/spam.txt");
        $return = $this->sa->process($message);

        $this->assertEquals(true,   is_array($return));
        $this->assertEquals(true,   $return['is_spam']);
        $this->assertEquals(5.0,    $return['thresold']);
        $this->assertEquals(1000.0, $return['score']);
    }

    public function testProcessHamMessage()
    {
        $message = file_get_contents(dirname(__FILE__) . "/files/ham.txt");
        $return = $this->sa->process($message);

        $this->assertEquals(true,  is_array($return));
        $this->assertEquals(false, $return['is_spam']);
        $this->assertEquals(5.0,   $return['thresold']);
        $this->assertEquals(0,     $return['score']);
    }
}
