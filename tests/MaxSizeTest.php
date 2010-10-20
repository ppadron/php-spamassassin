<?php

require_once 'BaseTestCase.php';

class MaxSizeTest extends BaseTestCase
{
    public function testShouldThrowExceptionIfMessageExceedsMaxSize()
    {
        $this->expectedException = 'SpamAssassin_Client_Exception';

        $params = $this->params;

        // setting max size as 10 bytes less than message size
        $params['maxSize'] = strlen($this->gtube) - 10;
        
        $sa = new SpamAssassin_Client($params);

        $result = $sa->process($this->gtube);
    }

    public function testShouldProcessIfMessageSmallerThanMaxSize()
    {
        $result = $this->sa->process($this->gtube);
        $this->assertTrue($result->isSpam);
    }
}