<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'SpamAssassin/Client.php';

class BaseTestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        /* @see phpunit.xml */
        $this->sa = new SpamAssassin_Client(
            $GLOBALS["PHPUNIT_SA_HOST"],
            (int) $GLOBALS["PHPUNIT_SA_PORT"],
            $GLOBALS["PHPUNIT_SA_USER"]
        );

        $this->gtube = $this->getMessage('Spam_GTUBE.txt');
        $this->ham   = $this->getMessage('Ham_testCheckHamMessage.txt');
    }

    protected function getMessage($filename)
    {
        return file_get_contents(dirname(__FILE__) . '/files/' . $filename);
    }

}

