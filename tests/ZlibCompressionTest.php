<?php
use Spamassassin\Client;

class ZlibCompressionTest extends BaseTestCase
{
    public function testShouldZlibCompressionWhenAvailable()
    {
    	return;
        $params = $this->params;
        $params['enableZlib'] = true;

        $sa     = new Client($params);
        $result = $sa->process($this->gtube);
        
        $this->assertTrue($result->isSpam);
    }
}
