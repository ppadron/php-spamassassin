<?php

require_once 'BaseTestCase.php';

class ProcessTest extends BaseTestCase
{
    public function testShouldReturnProcessedMessage()
    {
        $result = $this->sa->process($this->gtube);

        $this->assertEquals(true,   $result->isSpam);
        $this->assertEquals(1000.0, $result->score);

        $this->assertContains(
            "Content-Description: original message before SpamAssassin",
            $result->output
        );
    }
}
