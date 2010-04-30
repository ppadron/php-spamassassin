<?php

require_once 'BaseTestCase.php';

class ReportTest extends BaseTestCase
{
    public function testShouldReportMessageAsSpam()
    {
        // the message cannot be older than 2 days
        $today   = date('j, d M Y');
        $message = str_replace('Thu, 29 Apr 2010', $today, $this->gtube);

        $this->assertTrue($this->sa->report($message));
    }

}
