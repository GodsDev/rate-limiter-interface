<?php


namespace GodsDev\RateLimiter;

/**
 * Limits the number of requests per time.
 * There are two parameters: period and rate.
 * A request is a call of the inc() method. An inc() method begins to return false if number of requests per period is higher than a rate
 *
 *
 */
interface RateLimiterInterface {

    /**
     * @return integer number of seconds in a period
     */
    public function getPeriod();

    /**
     * @return integer number of request allowed within a period
     */
    public function getRate();

    /**
     * does a request
     *
     * @param integer|null $timestamp an user-defined time of a method call. Unix-like time stamp (in seconds). Mainly for testing purpose.
     *
     * @return boolean false if number of requests per period is too high (i.e. exceeds the getRate() value)
     */
    public function inc($timestamp = null);


    /**
     * @param integer|null $timestamp an user-defined time of a method call. Unix-like time stamp (in seconds). Mainly for testing purpose.
     *
     * @return integer number of successful requests made within a period.
     */
    public function getHits($timestamp = null);


    /**
     *
     * @param integer|null $timestamp an user-defined time of a method call. Unix-like time stamp (in seconds). Mainly for testing purpose.
     *
     * @return integer time to wait (in seconds) before a next successful request can be made.
     */
    public function getTimeToWait($timestamp = null);

    /**
     * Reset the limiter, so we can call inc() method rate-times with the true result.
     *
     * After a call of reset(): <ul>
     * <li> a call of inc() should return true
     * <li> a call of getHits() should return 0
     * <li> a call of getTimeToWait() should return 0
     * </ul>
     *
     * @param integer|null an user-defined time of a method call. Unix-like time stamp (in seconds). Mainly for testing purpose.
     */
    public function reset($timestamp = null);

}

