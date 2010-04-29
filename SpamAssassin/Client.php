<?php

require_once 'SpamAssassin/Exception.php';
require_once 'SpamAssassin/Client/Result.php';

class SpamAssassin_Client
{

    const LEARN_SPAM   = 0;
    const LEARN_HAM    = 1;
    const LEARN_FORGET = 2;

    protected $learnTypes = array(
        self::LEARN_SPAM,
        self::LEARN_HAM,
        self::LEARN_FORGET
    );

    protected $hostname;
    protected $port;
    protected $socket;
    protected $protocolVersion;

    public function __construct($hostname, $port, $user, $protocolVersion = '1.3')
    {
        $this->hostname        = $hostname;
        $this->port            = $port;
        $this->user            = $user;
        $this->protocolVersion = $protocolVersion;
    }

    protected function getSocket()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));
        socket_connect($socket, $this->hostname, $this->port);
        socket_set_nonblock($socket);
        return $socket;
    }

    protected function exec($cmd, $message, array $additionalHeaders = array())
    {
        $socket = $this->getSocket();

        $contentLenght = strlen($message);

        $cmd  = $cmd . " SPAMC/" . $this->protocolVersion . "\r\n";
        $cmd .= "Content-lenght: " . $contentLenght . "\r\n";
        $cmd .= "User: " .$this->user . "\r\n";

        if (!empty($additionalHeaders)) {
            foreach ($additionalHeaders as $headerName => $val) {
                $cmd .= $headerName . ": " . $val . "\r\n";
            }
        }

        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n\r\n";

        $this->write($socket, $cmd);

        list($headers, $message) = $this->read($socket);

        return $this->parseOutput($headers, $message);
    }

    protected function write($socket, $data)
    {
        socket_write($socket, $data, strlen($data));
        socket_shutdown($socket, 1);
    }

    protected function read($socket)
    {
        $headers = '';

        while (($buffer = @socket_read($socket, 128, PHP_NORMAL_READ)) !== '') {

            if ($buffer == "\r") {
                break;
            }

            $headers .= $buffer;

        }

        $message = '';

        while (($buffer = socket_read($socket, 128, PHP_NORMAL_READ)) !== '') {

            if ($buffer == "\r") {
                break;
            }

            $message .= $buffer;

        }

        socket_close($socket);

        return array($headers, $message);

    }

    protected function parseOutput($header, $message)
    {
        $result = new SpamAssassin_Client_Result();

        if (preg_match('/SPAMD\/(\d\.\d) (\d+) (.*)/', $header, $matches)) {
            $result->protocolVersion = $matches[1];
            $result->responseCode    = $matches[2];
            $result->responseMessage = $matches[3];

            if ($result->responseCode != 0) {
                throw new SpamAssassin_Exception(
                    $result->responseMessage,
                    $result->responseCode
                );
            }
            
        } else {
            throw new SpamAssassin_Exception('Could not parse response header');
        }

        if (preg_match('/Content-length: (\d+)/', $header, $matches)) {
            $result->contentLenght = $matches[1];
        }

        if (preg_match(
            '/Spam: (True|False|Yes|No) ; (\S+) \/ (\S+)/',
            $header,
            $matches
        )) {

            ($matches[1] == 'True' || $matches[1] == 'Yes') ?
                $result->isSpam = true :
                $result->isSpam = false;

            $result->score    = (float) $matches[2];
            $result->thresold = (float) $matches[3];
        }

        if (preg_match('/DidSet: (\S+)/', $header, $matches)) {
            $result->didSet = true;
        } else {
            $result->didSet = false;
        }

        if (preg_match('/DidRemove: (\S+)/', $header, $matches)) {
            $result->didRemove = true;
        } else {
            $result->didRemove = false;
        }

        $result->message = $message;

        return $result;
        
    }

    public function ping()
    {
        $socket = $this->getSocket();

        $this->write($socket, "PING SPAMC/1.3\n");

        list($headers, $message) = $this->read($socket);

        if (strpos($headers, "PONG") == false) {
            return false;
        }

        return true;

    }

    public function getSpamReport($message)
    {
        $result = $this->exec('REPORT_IFSPAM', $message);

        // should return null if message is not spam
        if ($result->isSpam === false) {
            return null;
        }

        return $result->message;
    }


    public function headers($message)
    {
        return $this->exec('HEADERS', $message)->message;
    }

    public function check($message)
    {
        return $this->exec('CHECK', $message);

    }

    public function process($message)
    {
        return $this->exec('PROCESS', $message);
    }

    public function symbols($message)
    {
        $result = $this->exec('SYMBOLS', $message);

        if (empty($result->message)) {
            return array();
        }

        $symbols = explode(",", $result->message);

        return array_map('trim', $symbols);
    }

    public function learn($message, $learnType = self::LEARN_SPAM)
    {
        if (!in_array($learnType, $this->learnTypes)) {
            throw new SpamAssassin_Exception("Invalid learn type ($learnType)");
        }

        if ($learnType == self::LEARN_SPAM) {
            $additionalHeaders = array(
                "Message-class" => "spam",
                "Set"           => "local"
            );
        } else if ($learnType == self::LEARN_HAM) {
            $additionalHeaders = array(
                "Message-class" => "ham",
                "Set"           => "local"
            );
        } else if ($learnType == self::LEARN_FORGET) {
            $additionalHeaders = array(
                "Remove" => "local"
            );
        }

        $result = $this->exec('TELL', $message, $additionalHeaders);
        
        if ($learnType == self::LEARN_SPAM || $learnType == self::LEARN_HAM) {
            return $result->didSet;
        } else {
            return $result->didRemove;
        }

    }

    public function isSpam($message)
    {
        return $this->check($message)->isSpam;
    }

}
