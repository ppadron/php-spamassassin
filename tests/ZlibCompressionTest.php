<?php

require_once 'BaseTestCase.php';

class ZlibCompressionTest extends BaseTestCase
{
    public function testShouldZlibCompressionWhenAvailable()
    {
        $params = $this->params;
        $params['enableZlib'] = true;

        $sa     = new SpamAssassin_Client($params);
        $result = $sa->process($this->gtube);
        
        $this->assertTrue($result->isSpam);
    }
}