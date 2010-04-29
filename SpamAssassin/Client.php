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

    public function __construct($hostname, $port, $user)
    {
        $this->hostname = $hostname;
        $this->port     = $port;
        $this->user     = $user;
    }

    protected function getSocket()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));
        socket_connect($socket, $this->hostname, $this->port);
        socket_set_nonblock($socket);
        return $socket;
    }

    protected function exec($cmd) 
    {
        $socket = $this->getSocket();
        $this->write($socket, $cmd);
        $result = $this->read($socket);

        return $result;
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

        return array("headers" => trim($headers), "message" => trim($message));

    }

    protected function parseResponseHeaders($header)
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

        return $result;
        
    }

    public function ping()
    {

        $return = $this->exec("PING SPAMC/1.3\n");

        if (strpos($return["headers"], "PONG") == false) {
            return false;
        }

        return true;

    }

    public function getSpamReport($message)
    {
        $lenght = strlen($message . "\r\n");

        $cmd  = "REPORT_IFSPAM " . "SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: $this->user\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n\r\n";

        $output  = $this->exec($cmd);

        $headers = $this->parseResponseHeaders($output["headers"]);

        // should return null if message is not spam
        if ($response->isSpam === false) {
            return null;
        }

        return $output["message"];
    }


    public function headers($message)
    {
        $lenght = strlen($message . "\r\n");

        $cmd  = "HEADERS SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: $this->user\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n\r\n";

        $output = $this->exec($cmd);

        return $output["message"];
    
    }

    public function check($message)
    {
        $lenght = strlen($message . "\n");

        $cmd  = "CHECK SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: $this->user\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n";
        $cmd .= "\r\n";

        $output   = $this->exec($cmd);
        $response = $this->parseResponseHeaders($output["headers"]);
        $response->output = $output["message"];

        return $response;

    }

    public function process($message)
    {

        $lenght = strlen($message . "\n");      

        $cmd  = "PROCESS " . "SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: $this->user\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n";
        $cmd .= "\r\n";

        $output = $this->exec($cmd);
        $result = $this->parseResponseHeaders($output["headers"]);

        $result->output = $output["message"];

        return $result;
    }

    public function symbols($message)
    {
        $lenght = strlen($message . "\n");      

        $cmd  = "SYMBOLS " . "SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: $this->user\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n";
        $cmd .= "\r\n";

        $output = $this->exec($cmd);

        if (empty($output["message"])) {
            return array();
        }

        $return = explode(",", $output["message"]);
        $return = array_map('trim', $return);

        return $return;

    }

    public function learn($message, $learnType = self::LEARN_SPAM)
    {
        if (!in_array($learnType, $this->learnTypes)) {
            throw new SpamAssassin_Exception("Invalid learn type ($learnType)");
        }

        $lenght = strlen($message . "\n");

        $cmd  = "TELL " . "SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: $this->user\r\n";

        if ($learnType == self::LEARN_SPAM) {
            $cmd .= "Message-class: spam\r\n";
            $cmd .= "Set: local\r\n";
        } else if ($learnType == self::LEARN_HAM) {
            $cmd .= "Message-class: ham\r\n";
            $cmd .= "Set: local\r\n";
        } else if ($learnType == self::LEARN_FORGET) {
            $cmd .= "Remove: local\r\n";
        }

        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n";
        $cmd .= "\r\n";

        $result   = $this->exec($cmd);

        $response = $this->parseResponseHeaders($result["headers"]);
        
        if ($learnType == self::LEARN_SPAM || $learnType == self::LEARN_HAM) {
            return $response->didSet;
        } else {
            return $response->didRemove;
        }

    }

    public function isSpam($message)
    {
        $result = $this->check($message);
        return $result->isSpam;
    }

}
