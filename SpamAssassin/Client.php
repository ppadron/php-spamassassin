<?php

require_once 'SpamAssassin/Exception.php';

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

    public function __construct($hostname = 'localhost', $port = '783')
    {
        $this->hostname = $hostname;
        $this->port     = $port;
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

        while (($buffer = socket_read($socket, 128, PHP_NORMAL_READ)) !== '') {

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

    protected function filterResponseHeader($header)
    {
        $result = array();

        if (preg_match('/Content-length: (\d+)/', $header, $matches)) {
            $result['content_lenght'] = $matches[1];
        }

        preg_match(
            '/Spam: (True|False|Yes|No) ; (\S+) \/ (\S+)/',
            $header,
            $matches
        );

        if (empty($matches)) {
            throw new SpamAssassin_Exception("Could not parse 'Spam:' header");
        }

        ($matches[1] == 'True' || $matches[1] == 'Yes') ?
            $result['is_spam'] = true :
            $result['is_spam'] = false;

        $result['score']    = (float) $matches[2];
        $result['thresold'] = (float) $matches[3];

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
        $cmd .= "User: ppadron\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n\r\n";

        $output  = $this->exec($cmd);

        $headers = $this->filterResponseHeader($output["headers"]);

        // should return null if message is not spam
        if ($headers["is_spam"] === false) {
            return null;
        }        

        return $output["message"];
    }

    public function headers($message)
    {
        $lenght = strlen($message . "\r\n");

        $cmd  = "HEADERS SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: ppadron\r\n";
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
        $cmd .= "User: ppadron\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n";
        $cmd .= "\r\n";

        $output = $this->exec($cmd);

        return $this->filterResponseHeader($output["headers"]);

    }

    public function process($message)
    {

        $lenght = strlen($message . "\n");      

        $cmd  = "PROCESS " . "SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: ppadron\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n";
        $cmd .= "\r\n";

        $output            = $this->exec($cmd);
        $result            = $this->filterResponseHeader($output["headers"]);
        $result['message'] = $output["message"];

        return $result;
    }

    public function symbols($message)
    {
        $lenght = strlen($message . "\n");      

        $cmd  = "SYMBOLS " . "SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: ppadron\r\n";
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
        $cmd .= "User: ppadron\r\n";

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

        $result = $this->exec($cmd);

        if ($learnType == self::LEARN_SPAM || $learnType == self::LEARN_HAM) {
            if (preg_match('/DidSet: (\S+)/', $result["headers"], $matches)) {
                if ($matches[1] == 'local') {
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new SpamAssassin_Exception("SpamAssassin could not learn the message");
            }
        }

        if ($learnType == self::LEARN_FORGET) {
            if (preg_match('/DidRemove: (\S+)/', $result["headers"], $matches)) {
                if ($matches[1] == 'local') {
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new SpamAssassin_Exception("SpamAssassin could not forget the message");
            }
        }

    }

}
