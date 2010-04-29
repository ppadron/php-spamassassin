<?php

require_once 'BaseTestCase.php';

class HeadersTest extends BaseTestCase
{
    public function testShouldReturnProcessedSpamMessageHeaders()
    {
        $message = $this->getMessage('Spam_GTUBE.txt');
        $headers = $this->sa->headers($message);

        $this->assertContains("X-Spam-Status: Yes",  $headers);
    }

    public function testShouldReturnProcessedHamMessageHeaders()
    {
        $message = $this->getMessage('HeadersTest_Ham.txt');
        $headers = $this->sa->headers($message);

        $this->assertContains("X-Spam-Status: No", $headers);
    }

}
