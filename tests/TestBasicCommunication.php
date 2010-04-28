<?php

require_once 'BaseTestCase.php';

class TestBasicCommunication extends BaseTestCase
{
    public function testPing()
    {
        $this->assertEquals(true, $this->sa->ping());
    }

}
