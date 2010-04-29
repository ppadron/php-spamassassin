<?php

require_once 'BaseTestCase.php';

class SymbolsTest extends BaseTestCase
{
    public function testShouldReturnRuleNamesForSpamMessage()
    {
        $result = $this->sa->symbols($this->gtube);
        $this->assertEquals(true, in_array('GTUBE', $result));
    }

}
