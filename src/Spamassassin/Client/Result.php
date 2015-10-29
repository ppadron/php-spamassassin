<?php

namespace Spamassassin\Client;

/**
 * Represents the result from an API call on the SpamAssassin server
 *
 * @category SpamAssassin 
 * @package  SpamAssassin_Client
 * @author   Pedro Padron <ppadron@w3p.com.br>
 * @license  http://www.apache.org/licenses/LICENSE-2.0.html Apache License 2.0
 */
class Result
{
    /**
     * The protocol version of the server response
     *
     * @var string
     */
    public $protocolVersion;

    /**
     * Response code.
     * 
     * @var int
     */
    public $responseCode;

    /**
     * Response message. EX_OK for sucess.
     * 
     * @var string
     */
    public $responseMessage;

    /**
     * Response content length
     * 
     * @var int
     */
    public $contentLength;

    /**
     * SpamAssassin score
     * 
     * @var float
     */
    public $score;

    /**
     * How many points the message must score to be considered spam
     * 
     * @var float
     */
    public $thresold;

    /**
     * Is it spam or not?
     * 
     * @var boolean
     */
    public $isSpam;

    /**
     * Raw output from SpamAssassin server
     *
     * @var string
     */
    public $message;

    /**
     * Output headers
     *
     * @var string
     */
    public $headers;

    /**
     * @var bool
     */
    public $didSet;

    /**
     * @var bool
     */
    public $didRemove;
}
