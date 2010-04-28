<?php

require_once 'BaseTestCase.php';

class TestLearn extends BaseTestCase
{
    public function testShouldLearnAndMessageAsSpam()
    {
        $this->assertTrue($this->sa->learn($this->gtube, SpamAssassin_Client::LEARN_SPAM));
        $this->assertTrue($this->sa->learn($this->gtube, SpamAssassin_Client::LEARN_FORGET));
    }

    public function testShouldLearnMessageAsHam()
    {
        $message = $this->getMessage('Ham_testLearnMessageAsHam.txt');

        $this->assertTrue($this->sa->learn($message, SpamAssassin_Client::LEARN_HAM));
        $this->assertTrue($this->sa->learn($message, SpamAssassin_Client::LEARN_FORGET));
    }

    public function testShouldNotLearnIfMessageIsAlreadyKnown()
    {
        $message = $this->getMessage('Ham_testLearnMessageAsHam.txt');

        /* should learn in the first call */
        $this->assertTrue($this->sa->learn($message, SpamAssassin_Client::LEARN_HAM));

        /* should fail in the second call because message is already known */
        $this->assertFalse($this->sa->learn($message, SpamAssassin_Client::LEARN_HAM));

        /* cleanup (forget message) */
        $this->assertTrue($this->sa->learn($message, SpamAssassin_Client::LEARN_FORGET));
    }

}
