<?php

require_once 'BaseTestCase.php';

class RevokeTest extends BaseTestCase
{
    public function testShouldRevokeMessageAsHam()
    {
        // the message cannot be older than 2 days
        $today   = date('j, d M Y');
        $message = str_replace('2 Jan 2010', $today, $this->ham);

        $this->assertTrue($this->sa->revoke($message));
    }

}