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

        return array("headers" => $headers, "message" => $message);

    }

    protected function filterResponseHeader($header)
    {
        $result = array();

        if (preg_match('/Content-length: (\d+)/', $header, $matches)) {
            $result['content_lenght'] = $matches[1];
        }

        preg_match(
            '/Spam: (True|False) ; (\S+) \/ (\S+)/',
            $header,
            $matches
        );

        if (empty($matches)) {
            throw new SpamAssassin_Exception("Could not parse 'Spam:' header");
        }

        ($matches[1] == 'True') ?
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

}
