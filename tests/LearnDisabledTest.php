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

        /* @see phpunit.xml */
        if (!empty($GLOBALS["PHPUNIT_SA_SOCKET"])) {
            $params = array(
                "socketPath" => $GLOBALS["PHPUNIT_SA_SOCKET"],
                "user"       => $GLOBALS["PHPUNIT_SA_USER"],
            );
        } else {
            $params = array(
                "hostname" => $GLOBALS["PHPUNIT_SA_HOST"],
                "port"     => (int) $GLOBALS["PHPUNIT_SA_PORT"],
                "user"     => $GLOBALS["PHPUNIT_SA_USER"]
            );
        }

        $params["protocolVersion"] = $GLOBALS["PHPUNIT_SA_PROTOCOL_VERSION"];

        $this->sa = new SpamAssassin_Client($params);
    }

    public function testShouldThrowExceptionIfLearningIsDisabled()
    {
        $message = $this->getMessage('Spam_GTUBE.txt');
        $this->expectedException = 'SpamAssassin_Client_Exception';
        $this->sa->learn($message, SpamAssassin_Client::LEARN_SPAM);
    }

    public function testShouldThrowExceptionWhenForgettingIfLearningIsDisabled()
    {
        $message = $this->getMessage('Spam_GTUBE.txt');
        $this->expectedException = 'SpamAssassin_Client_Exception';
        $this->sa->learn($message, SpamAssassin_Client::LEARN_FORGET);
    }

}
