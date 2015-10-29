<?php
class ReportTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        if (!isset($GLOBALS['PHPUNIT_SA_LEARN_ENABLED'])
            || $GLOBALS['PHPUNIT_SA_LEARN_ENABLED'] == 0) {

            $this->markTestSkipped(
                'To test the learning behavior, enable the TELL ' .
                'command in SpamAssassin and update phpunit.xml'
            );
        }
    }

    public function testShouldReportMessageAsSpam()
    {
        // the message cannot be older than 2 days
        $today   = date('j, d M Y');
        $message = str_replace('Thu, 29 Apr 2010', $today, $this->gtube);

        $this->assertTrue($this->sa->report($message));
    }

}
