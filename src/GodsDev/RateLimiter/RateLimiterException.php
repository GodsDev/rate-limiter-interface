<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GodsDev\RateLimiter;

/**
 * Description of RateLimiterException
 *
 * @author Tomáš
 */
class RateLimiterException extends \Exception {
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
