<?php
use Spamassassin\Client;

class BaseTestCase extends PHPUnit_Framework_TestCase
{
    /** @var Client */
    protected $sa;
    protected $gtube;
    protected $ham;
    protected $params;

    public function setUp()
    {
        /* @see phpunit.xml */
        if (!empty($GLOBALS["PHPUNIT_SA_SOCKET"])) {
            $params = array(
                "socketPath" => $GLOBALS["PHPUNIT_SA_SOCKET"],
                "user"       => $GLOBALS["PHPUNIT_SA_USER"],
            );
        } else {
            $params = array(
                "hostname" => $GLOBALS["PHPUNIT_SA_HOST"],
                "port"     => (int) $GLOBALS["PHPUNIT_SA_PORT"],
                "user"     => $GLOBALS["PHPUNIT_SA_USER"]
            );
        }

        $params["protocolVersion"] = $GLOBALS["PHPUNIT_SA_PROTOCOL_VERSION"];
        $this->params = $params;
        $this->sa     = new Client($params);
        $this->gtube  = $this->getMessage('Spam_GTUBE.txt');
        $this->ham    = $this->getMessage('Ham_testCheckHamMessage.txt');
    }

    protected function getMessage($filename)
    {
        return file_get_contents(dirname(__FILE__) . '/files/' . $filename);
    }

}

