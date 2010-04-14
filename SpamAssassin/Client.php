<?php

require_once 'SpamAssassin/Exception.php';

class SpamAssassin_Client
{

    const PROCESS = 'PROCESS';
    const CHECK   = 'CHECK';

    public function __construct($hostname = 'localhost', $port = '783')
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));
        socket_connect($this->socket, $hostname, $port);
//        socket_set_nonblock($this->socket);
    }

    private function _exec($cmd)
    {
        $this->_write($cmd, false);
        return $this->_read();
    }

    private function _write($data)
    {
        socket_write($this->socket, $data, strlen($data));
    }

    private function _read()
    {
        $return = '';
        do {
            $buffer = socket_read($this->socket, 1024, PHP_NORMAL_READ);

            if (trim($buffer) == "") {
                break;
            }

            echo $buffer;
            $return .= $buffer;

        } while (true);

        return $return;
    }

    public function ping()
    {

        $return = $this->_exec("PING SPAMC/1.3\n");

        if (strpos($return, "PONG") == false) {
            return false;
        }

        return true;

    }

    public function process($message, $processMethod = self::CHECK)
    {
        $lenght = strlen($message . "\n");

        $cmd  = "CHECK " . "SPAMC/1.2\r\n";
        $cmd .= "Content-lenght: $lenght\r\n";
        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n";

        $output = $this->_exec($cmd);

        $lines = explode("\r\n", $output);

        $matches = array();

        preg_match(
            '/^Spam: (True|False) ; (\S+) \/ (\S+)/',
            $lines[1],
            &$matches
        );

        if (empty($matches)) {
            throw new SpamAssassin_Exception("Could not parse response for $processMethod command");
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
