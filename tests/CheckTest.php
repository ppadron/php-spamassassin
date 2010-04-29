<?php

require_once 'BaseTestCase.php';

class CheckTest extends BaseTestCase
{
    public function testShouldBeAbleToCheckSpamMessage()
    {
        $message = $this->getMessage('Spam_testCheckSpamMessage.txt');
        $return  = $this->sa->check($message);

        $this->assertTrue($return->isSpam);
        $this->assertEquals(5.0,    $return->thresold);
        $this->assertEquals(1000.0, $return->score);
    }

    public function testShouldBeAbleToCheckHamMessage()
    {
        $message = $this->getMessage('Ham_testCheckHamMessage.txt');
        $return  = $this->sa->check($message);

        $this->assertFalse($return->isSpam);
    }

}
