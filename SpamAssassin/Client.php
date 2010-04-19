<?php

require_once 'SpamAssassin/Exception.php';

class SpamAssassin_Client
{
    const PROCESS = 'PROCESS';
    const CHECK   = 'CHECK';

    static protected $allowedProcessMethods = array(
        self::PROCESS, self::CHECK
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

            if (trim($buffer) == "") {
                break;
            }

            $headers .= $buffer;

        }

        echo $headers;

        $message = '';

        while (($buffer = socket_read($socket, 128, PHP_NORMAL_READ)) !== '') {

            if (trim($buffer) == "") {
                break;
            }

            $message .= $buffer;

        }

        echo $message;

    }

    public function ping()
    {

        $return = $this->exec("PING SPAMC/1.3\n");

        if (strpos($return, "PONG") == false) {
            return false;
        }

        return true;

    }

    public function report($message)
    {
        $lenght = strlen($message . "\r\n");

        $cmd  = "REPORT " . "SPAMC/1.4\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "User: ppadron\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n\r\n";

        $output = $this->exec($cmd);

        return $output;
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
        
        return $output;
    
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

        $lines = explode("\r\n", $output);

        preg_match(
            '/^Spam: (True|False) ; (\S+) \/ (\S+)/',
            $lines[1],
            $matches
        );

        if (empty($matches)) {
            throw new SpamAssassin_Exception("Could not parse response for CHECK command");
        }

        $result = array();

        ($matches[1] == 'True') ?
            $result['is_spam'] = true :
            $result['is_spam'] = false;

        $result['score']    = (float) $matches[2];
        $result['thresold'] = (float) $matches[3];

        return $result;

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

        $output = $this->exec($cmd);

        $lines = explode("\r\n", $output);

        print_r($lines);

        preg_match(
            '/^Spam: (True|False) ; (\S+) \/ (\S+)/',
            $lines[1],
            $matches
        );

        if (empty($matches)) {
            throw new SpamAssassin_Exception("Could not parse response for command");
        }

        $result = array();

        ($matches[1] == 'True') ?
            $result['is_spam'] = true :
            $result['is_spam'] = false;

        $result['score']    = (float) $matches[2];
        $result['thresold'] = (float) $matches[3];

        return $result;
    }

}
