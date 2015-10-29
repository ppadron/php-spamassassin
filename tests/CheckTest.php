<?php

class CheckTest extends BaseTestCase
{
    public function testShouldBeAbleToCheckSpamMessage()
    {
        $message = $this->getMessage('Spam_testCheckSpamMessage.txt');
        $return  = $this->sa->check($message);

        $this->assertTrue($return->isSpam);
        $this->assertEquals(5.0,    $return->thresold);
        $this->assertTrue($return->score >= 1000);
    }

    public function testShouldBeAbleToCheckHamMessage()
    {
        $message = $this->getMessage('Ham_testCheckHamMessage.txt');
        $return  = $this->sa->check($message);

        $this->assertFalse($return->isSpam);
    }

    public function testIsSpamShouldBeAnAliasToCheck()
    {
        $this->assertTrue($this->sa->isSpam($this->gtube));
        $this->assertFalse($this->sa->isSpam($this->ham));
    }

}
