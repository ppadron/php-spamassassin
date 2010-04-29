<?php

require_once 'BaseTestCase.php';

class LearnDisabledTest extends BaseTestCase
{
    public function setUp()
    {
        if (!isset($GLOBALS['PHPUNIT_SA_LEARN_ENABLED'])
            || $GLOBALS['PHPUNIT_SA_LEARN_ENABLED'] == 1) {

            $this->markTestSkipped(
                'This test only runs when learning is disabled in SpamAssassin'
            );
        }

        $params["protocolVersion"] = $GLOBALS["PHPUNIT_SA_PROTOCOL_VERSION"];

        $this->sa = new SpamAssassin_Client(
            $GLOBALS['PHPUNIT_SA_HOST'],
            (int) $GLOBALS['PHPUNIT_SA_PORT'],
            $GLOBALS['PHPUNIT_SA_USER']
        );

    }

    public function testShouldThrowExceptionIfLearningIsDisabled()
    {
        $this->expectedException = 'SpamAssassin_Exception';
        $this->sa->learn($this->gtube, SpamAssassin_Client::LEARN_SPAM);
    }

    public function testShouldThrowExceptionWhenForgettingIfLearningIsDisabled()
    {
        $this->expectedException = 'SpamAssassin_Exception';
        $this->sa->learn($message, SpamAssassin_Client::LEARN_FORGET);
    }

}
