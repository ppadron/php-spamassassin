<?php
class RevokeTest extends BaseTestCase
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

    public function testShouldRevokeMessageAsHam()
    {
        // the message cannot be older than 2 days
        $today   = date('j, d M Y');
        $message = str_replace('2 Jan 2010', $today, $this->ham);

        $this->assertTrue($this->sa->revoke($message));
    }

}
