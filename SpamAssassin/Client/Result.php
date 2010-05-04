<?php

/**
 * Represents the result from an API call on the SpamAssassin server
 *
 * @category SpamAssassin 
 * @package  SpamAssassin_Client
 * @author   Pedro Padron <ppadron@w3p.com.br>
 * @license  http://www.apache.org/licenses/LICENSE-2.0.html Apache License 2.0
  */
class SpamAssassin_Client_Result
{
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
     * Response content lenght
     * 
     * @var int
     */
    public $contentLenght;

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
    public $output;

}
