<?php

namespace GodsDev\RateLimiter;

/**
 * Limits the number of hits per time.
 * There are two parameters: rate and period.
 * A request is a call of the inc() method. An inc() method begins to return false if number of requests per period is higher than a rate
 *
 * rate
 * period
 * window
 *   elapsed
 *   active
 *   future
 *
 *   fresh
 *   exhausted
 * hit
 * startTime
 * timeToWait
 * reset
 *
 */
interface RateLimiterInterface {

    /**
     * @return integer number of time-units in a period
     */
    public function getPeriod();

    /**
     * @return integer number of request allowed within a period
     */
    public function getRate();

    /**
     * tries to consume a hit (or hits)
     *
     * @param integer $timestamp an user-defined time of a method call. Unix-like time stamp (in time-units).
     * @param integer $increment
     *
     * @return integer number of hits consumed out of $increment. 0 if number of hits consumed per period is too high (i.e. exceeds the getRate() value)
     */
    public function inc($timestamp, $increment = 1);

    /**
     * @param integer $timestamp an user-defined time of a method call. Unix-like time stamp (in time-units).
     *
     * @return integer number of successful requests made within a period.
     */
    public function getHits($timestamp);

    /**
     *
     * @param integer $timestamp an user-defined time of a method call. Unix-like time stamp (in time-units).
     *
     * @return integer time to wait (in time-units) before a next successful request can be made.
     */
    public function getTimeToWait($timestamp);

    /**
     * Reset the limiter, so we can call inc() method rate-times with the true result.
     *
     * After a call of reset(): <ul>
     * <li> a call of inc() should return true
     * <li> a call of getHits() should return 0
     * <li> a call of getTimeToWait() should return 0
     * </ul>
     *
     * @param integer $timestamp an user-defined time of a method call. Unix-like time stamp (in time-units).
     */
    public function reset($timestamp);

    /**
     *
     * @param type $timestamp  an user-defined time of a method call. Unix-like time stamp (in time-units).
     */
    public function getStartTime($timestamp);
}
