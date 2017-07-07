<?php

namespace GodsDev\RateLimiter;

/**
 * Description of RateLimiterException
 *
 * @author Tomáš Kraus
 */
class RateLimiterException extends \Exception {

    /**
     * (PHP 5 &gt;= 5.1.0, PHP 7)<br/>
     * Construct the exception
     * @link http://php.net/manual/en/exception.construct.php
     * @param string $message [optional] <p>
     * The Exception message to throw.
     * </p>
     * @param int $code [optional] <p>
     * The Exception code.
     * </p>
     * @param Throwable $previous [optional] <p>
     * The previous exception used for the exception chaining.
     * </p>
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}
