<?php

use Spamassassin\Client;

class HeadersTest extends BaseTestCase
{
    public function setUp()
    {
        /* @see phpunit.xml */
        if (!empty($GLOBALS["PHPUNIT_SA_SOCKET"])) {
            $this->params = array(
                "socketPath" => $GLOBALS["PHPUNIT_SA_SOCKET"],
                "user"       => $GLOBALS["PHPUNIT_SA_USER"],
            );
        } else {
            $this->params = array(
                "hostname" => $GLOBALS["PHPUNIT_SA_HOST"],
                "port"     => (int) $GLOBALS["PHPUNIT_SA_PORT"],
                "user"     => $GLOBALS["PHPUNIT_SA_USER"]
            );
        }

        $this->gtube = $this->getMessage('Spam_GTUBE.txt');
        $this->ham   = $this->getMessage('Ham_testCheckHamMessage.txt');
    }


    public function testShouldReturnProcessedSpamMessageHeaders()
    {
        $this->params["protocolVersion"] = 1.5;
        $sa = new Client($this->params);

        $message = $this->getMessage('Spam_GTUBE.txt');
        $headers = $sa->headers($message);

        $this->assertContains("X-Spam-Status: Yes",  $headers);
    }

    public function testShouldReturnProcessedHamMessageHeaders()
    {
        $this->params["protocolVersion"] = 1.5;
        $sa = new Client($this->params);

        $message = $this->getMessage('HeadersTest_Ham.txt');
        $headers = $sa->headers($message);

        $this->assertContains("X-Spam-Status: No", $headers);
    }

    public function testShouldAlsoWorkWithProtocol13()
    {
        $this->params["protocolVersion"] = 1.3;
        $sa = new Client($this->params);
        $message = $this->getMessage('HeadersTest_Ham.txt');
        $headers = $sa->headers($message);

        $this->assertContains("X-Spam-Status: No", $headers);
    }

    public function testShouldAlsoWorkWithProtocol12()
    {
        $this->params["protocolVersion"] = 1.2;
        $sa = new Client($this->params);
        $message = $this->getMessage('HeadersTest_Ham.txt');
        $headers = $sa->headers($message);

        $this->assertContains("X-Spam-Status: No", $headers);
    }
}
