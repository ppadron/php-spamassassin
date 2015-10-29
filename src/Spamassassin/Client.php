<?php
namespace Spamassassin;

use Spamassassin\Client\Result,
    Spamassassin\Client\Exception;

/**
 * @category SpamAssassin
 *
 * @author   Pedro Padron <ppadron@w3p.com.br>
 * @license  http://www.apache.org/licenses/LICENSE-2.0.html Apache License 2.0
 */
class Client
{
    const LEARN_SPAM   = 0;
    const LEARN_HAM    = 1;
    const LEARN_FORGET = 2;

    protected $learnTypes = array(
        self::LEARN_SPAM,
        self::LEARN_HAM,
        self::LEARN_FORGET
    );

    protected $hostname = 'localhost';
    protected $port     = '783';

    protected $socketPath;
    protected $socket;
    protected $protocolVersion = '1.5';
    protected $enableZlib;

    /**
     * Class constructor
     *
     * Accepts an associative array with the following keys:
     *
     * socketPath      - mandatory only if using UNIX sockets to local server
     * hostname        - mandatory only if using remote SpamAssassin server
     * user            - optional parameter
     * protocolVersion - spamd protocol version (defaults to 1.5)
     *
     * @param array $params SpamAssassin parameters
     */
    public function __construct(array $params)
    {
        foreach ($params as $param => $value) {
            $this->$param = $value;
        }
    }

    /**
     * Creates a new socket connection with data provided in the constructor
     */
    protected function getSocket()
    {
        if (!empty($this->socketPath)) {
            $socket = fsockopen('unix://' . $this->socketPath, NULL, $errno, $errstr);
        } else {
            $socket = fsockopen($this->hostname, $this->port, $errno, $errstr);
        }

        if (!$socket) {
            throw new Exception(
                "Could not connect to SpamAssassin: {$errstr}", $errno
            );
        }

        return $socket;
    }

    /**
     * Sends a command to the server and returns an object with the result
     *
     * @param string $cmd               Protocol command to be executed
     * @param string $message           Full email message
     * @param array  $additionalHeaders Associative array with additional headers
     *
     * @throws Exception
     *
     * @return Result
     */
    protected function exec($cmd, $message, array $additionalHeaders = array())
    {
        $socket        = $this->getSocket();
        $message      .= "\r\n";
        $contentLength = strlen($message);

        if (!empty($this->maxSize)) {
            if ($contentLength > $this->maxSize) {
                throw new Exception(
                    "Message exceeds the maximum allowed size of {$this->maxSize} kbytes"
                );
            }
        }

        $cmd  = $cmd . " SPAMC/" . $this->protocolVersion . "\r\n";
        $cmd .= "Content-length: {$contentLength}\r\n";

        if ($this->enableZlib && function_exists('gzcompress')) {
            $cmd    .= "Compress: zlib\r\n";
            $message = gzcompress($message);
        }

        if (!empty($this->user)) {
            $cmd .= "User: " .$this->user . "\r\n";
        }

        if (!empty($additionalHeaders)) {
            foreach ($additionalHeaders as $headerName => $val) {
                $cmd .= $headerName . ": " . $val . "\r\n";
            }
        }

        $cmd .= "\r\n";
        $cmd .= $message;
        $cmd .= "\r\n";

        $this->write($socket, $cmd);

        list($headers, $message) = $this->read($socket);

        return $this->parseOutput($headers, $message);
    }

    /**
     * Writes data to the socket
     *
     * @param resource $socket Socket returned by getSocket()
     * @param string   $data   Data to be written
     *
     * @return void
     */
    protected function write($socket, $data)
    {
        fwrite($socket, $data);
    }

    /**
     * Reads all input from the SpamAssassin server after data was written
     *
     * @param resource $socket Socket connection created by getSocket()
     *
     * @return array Array containing output headers and message
     */
    protected function read($socket)
    {
        $headers = '';
        $message = '';

        while (true) {
            $buffer   = fgets($socket, 128);
            $headers .= $buffer;
            if ($buffer == "\r\n" || feof($socket)) {
                break;
            }
        }

        while (!feof($socket)) {
            $message .= fgets($socket, 128);
        }

        fclose($socket);

        return array(trim($headers), trim($message));
    }

    /**
     * Parses SpamAssassin output ($header and $message)
     *
     * @param string $header  Output headers
     * @param string $message Output message
     *
     * @return Result Object containing the result
     */
    protected function parseOutput($header, $message)
    {
        $result = new Result();

        /*
         * Matches the first line in the output. Something like this:
         *
         * SPAMD/1.5 0 EX_OK
         * SPAMD/1.5 68 service unavailable: TELL commands have not been enabled
         */
        if (preg_match('/SPAMD\/(\d\.\d) (\d+) (.*)/', $header, $matches)) {
            $result->protocolVersion = $matches[1];
            $result->responseCode    = $matches[2];
            $result->responseMessage = $matches[3];

            if ($result->responseCode != 0) {
                throw new Exception(
                    $result->responseMessage,
                    $result->responseCode
                );
            }

        } else {
            throw new Exception('Could not parse response header');
        }

        if (preg_match('/Content-length: (\d+)/', $header, $matches)) {
            $result->contentLength = $matches[1];
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
        } else {

            /**
             * In PROCESS method with protocol version before 1.3, SpamAssassin
             * won't return the 'Spam:' field in the response header. In this case,
             * it is necessary to check for the X-Spam-Status: header in the
             * processed message headers.
             */
            if (preg_match(
                '/X-Spam-Status: (Yes|No)\, score=(\d+\.\d) required=(\d+\.\d)/',
                $header.$message,
                $matches)) {

                    ($matches[1] == 'Yes') ?
                        $result->isSpam = true :
                        $result->isSpam = false;

                    $result->score    = (float) $matches[2];
                    $result->thresold = (float) $matches[3];
                }

        }

        /* Used for report/revoke/learn */
        if (preg_match('/DidSet: (\S+)/', $header, $matches)) {
            $result->didSet = true;
        } else {
            $result->didSet = false;
        }

        /* Used for report/revoke/learn */
        if (preg_match('/DidRemove: (\S+)/', $header, $matches)) {
            $result->didRemove = true;
        } else {
            $result->didRemove = false;
        }

        $result->headers = $header;
        $result->message = $message;

        return $result;

    }

    /**
     * Pings the server to check the connection
     * 
     * @return bool
     */
    public function ping()
    {
        $socket = $this->getSocket();

        $this->write($socket, "PING SPAMC/{$this->protocolVersion}\r\n\r\n");
        list($headers, $message) = $this->read($socket);

        if (strpos($headers, "PONG") === false) {
            return false;
        }

        return true;
    }

    /**
     * Returns a detailed report if the message is spam or null if it's ham
     *
     * @param string $message Email message
     * 
     * @return Result Detailed spam report
     */
    public function getSpamReport($message)
    {
        return $this->exec('REPORT', $message);
    }

    /**
     * Processes the message and returns it's headers
     *
     * This will check if the message is spam or not and return all headers
     * for the modified processed message. Such as X-Spam-Flag and X-Spam-Status.
     *
     * @param string $message Headers for the modified message
     *
     * @return Result Object containing the
     */
    public function headers($message)
    {
        return $this->exec('HEADERS', $message)->message;
    }

    /**
     * Checks if a message is spam with the CHECK protocol command
     *
     * @param string $message Raw email message
     *
     * @return Result Object containing the result
     */
    public function check($message)
    {
        return $this->exec('CHECK', $message);
    }

    /**
     * Shortcut to check() method that returns a boolean
     *
     * @param string $message Raw email message
     *
     * @return boolean Whether message is spam or not
     */
    public function isSpam($message)
    {
        return $this->check($message)->isSpam;
    }

    /**
     * Shortcut to check() method that returns a float score
     *
     * @param string $message Raw email message
     *
     * @return float Spam Score of the Message
     */
    public function getScore($message)
    {
        return $this->check($message)->score;
    }

    /**
     * Processes the message, checks it for spam and returning it's modified version
     *
     * @param string $message Raw email message
     *
     * @return Result Result details and modified message
     */
    public function process($message)
    {
        return $this->exec('PROCESS', $message);
    }

    /**
     * Returns all rules matched by the message
     *
     * @param string $message Raw email message
     *
     * @return array Array containing the names of the rules matched
     */
    public function symbols($message)
    {
        $result = $this->exec('SYMBOLS', $message);

        if (empty($result->message)) {
            return array();
        }

        $symbols = explode(",", $result->message);

        return array_map('trim', $symbols);
    }

    /**
     * Uses SpamAssassin learning feature with TELL. Must be enabled on the server.
     *
     * @param string $message   Raw email message
     * @param int    $learnType self::LEARN_SPAM|self::LEARN_FORGET|self::LEARN_HAM
     *
     * @throws Exception when invalid learnType is passed
     *
     * @return bool Whether it did learn or not
     */
    public function learn($message, $learnType = self::LEARN_SPAM)
    {
        if (!in_array($learnType, $this->learnTypes)) {
            throw new Exception("Invalid learn type ($learnType)");
        }

        if ($learnType === self::LEARN_SPAM) {
            $additionalHeaders = array(
                'Message-class' => 'spam',
                'Set' => 'local',
            );
        } elseif ($learnType === self::LEARN_HAM) {
            $additionalHeaders = array(
                'Message-class' => 'ham',
                'Set' => 'local',
            );
        } elseif ($learnType === self::LEARN_FORGET) {
            $additionalHeaders = array(
                'Remove' => 'local',
            );
        }

        $result = $this->exec('TELL', $message, $additionalHeaders);

        if ($learnType === self::LEARN_SPAM || $learnType === self::LEARN_HAM) {
            return $result->didSet;
        } else {
            return $result->didRemove;
        }
    }

    /**
     * Report message as spam, both local and remote.
     * 
     * @param string $message Raw email message
     * 
     * @return bool
     */
    public function report($message)
    {
        $additionalHeaders = array(
            'Message-class' => 'spam',
            'Set' => 'local,remote',
        );

        return $this->exec('TELL', $message, $additionalHeaders)->didSet;
    }

    /**
     * Revokes a message previously reported as spam.
     * 
     * @param string $message Raw email message
     * 
     * @return bool
     */
    public function revoke($message)
    {
        $additionalHeaders = array(
            'Message-class' => 'ham',
            'Set' => 'local,remote',
        );

        return $this->exec('TELL', $message, $additionalHeaders)->didSet;
    }
}
