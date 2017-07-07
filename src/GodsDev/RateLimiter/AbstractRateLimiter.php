<?php

// TODO catch possible exceptions from impl methods and rethrow them in the wrapped RateLimiterException

namespace GodsDev\RateLimiter;

/**
 * Description of RateLimiterAdapter
 *
 * @author Tomáš Kraus
 */
abstract class AbstractRateLimiter implements \GodsDev\RateLimiter\RateLimiterInterface {

    /**
     * Initiated in __construct
     * @var int
     */
    private $rate;

    /**
     * Initiated in __construct
     * MAY be in seconds or any other time unit
     * 
     * @var int
     */
    private $period;

    /**
     *
     * @var \GodsDev\RateLimiter\TimeWindow
     */
    private $window;

    /**
     * Time to wait until the next window is active, against the $timestamp given.
     * Calculated by \GodsDev\RateLimiter\TimeWindow\getTimeToNext($timestamp)
     * While waiting no more hits are allowed as the quota was depleted.
     * 
     * @var int
     */
    private $timeToWait;

    /**
     *
     * @var int
     */
    private $hits;

    /**
     * new instance
     *
     * @param int $rate
     * @param int $period MAY be denominated in seconds or any other time unit
     */
    public function __construct($rate, $period) {
        $this->rate = $rate;
        $this->period = $period;
    }

    //--------------------------------------------------------------------------

    /**
     * sets actual $hits and $startTime values from implementation's data source (db, for example)
     *
     * @param integer $hits a variable where to store the number of hits read from implementation's data source
     * @param integer $startTime a variable where to store the start time read from implementation's data source
     *
     *
     * Implement a fail-safe logic if data is not found.
     *
     * @see createDataImpl
     */
    abstract protected function readDataImpl(&$hits, &$startTime);

    /**
     * resets the limiter. Should set hits to 0.
     *
     * Implement a fail-safe logic if data is not found.
     *
     * @param integer $startTime
     *
     * @return integer modified startTime. Can be aligned down, for example to the current whole hour
     */
    abstract protected function resetDataImpl($startTime);

    /**
     * does the incremetation of hits
     *
     * Abstract limiter assures that a call of readDataImpl was called before this method
     *
     * @param int $lastKnownHitCount number of hits retrieved by a readDataImpl method
     * @param int $lastKnownStartTime start time retrieved by a readDataImpl method
     * @param int $sanitizedIncrement number of hits to be consumed. It is safe: $lastKnownHitCount + $sanitizedIncrement <= rate
     *
     * @return int number of hits consumed. Always to be less or equal than $sanitizedIncrement
     */
    abstract protected function incrementHitImpl($lastKnownHitCount, $lastKnownStartTime, $sanitizedIncrement);

    //--------------------------------------------------------------------------

    
    /**
     * Period getter
     * 
     * @return int
     */
    public function getPeriod() {
        return $this->period;
    }

    /**
     * Rate getter
     * 
     * @return int
     */
    public function getRate() {
        return $this->rate;
    }

    /**
     * Return number of consumed hits during the period e.g. for quota notification calculation
     * 
     * @param int $timestamp
     * @return int
     */
    public function getHits($timestamp) {
        $this->refreshState($timestamp);
        return $this->hits;
    }

    /**
     * Returns number of time units (seconds) remaining for a next hit allowed
     * 
     * @param int $timestamp
     * @return int
     */
    public function getTimeToWait($timestamp) {
        $this->refreshState($timestamp);
        return $this->timeToWait;
    }

    /**
     * 
     * @param int $timestamp
     * @return type
     */
    public function getStartTime($timestamp) {
        $this->refreshState($timestamp);
        return $this->window->getStartTime();
    }

    /**
     * Increments usage in time and returns number of hits allowed (compared to increment)
     * 
     * @param int $timestamp
     * @param int $increment [optional]
     * @return int
     */
    public function inc($timestamp, $increment = 1) {
        $this->refreshState($timestamp);
        if ($this->timeToWait == 0 && $this->hits < $this->rate) {
            
            if ($this->hits + $increment > $this->rate) {
                $sanitizedIncrement = $this->rate - $this->hits;
            } else {
                $sanitizedIncrement = $increment;
            }
            return $this->incrementHitImpl($this->hits, $this->window->getStartTime(), $sanitizedIncrement);
        } else {
            return 0;
        }
        /* can be shortened as:
        return ($this->timeToWait == 0 && $this->hits < $this->rate)?(
                //increment value guard as not all the quota was depleted yet
                $this->incrementHitImpl($this->hits, $this->window->getStartTime(),
                        //sanitizedIncrement
                        ($this->hits + $increment > $this->rate)?($this->rate - $this->hits):$increment
                        )
                ):0;
        */
    }

    /**
     *
     * @param integer $timestamp
     * @return integer startTime
     */
    public function reset($timestamp) {
        $startTime = $this->resetDataImpl($timestamp);
        if (is_null($startTime)) {
            throw new RateLimiterException("null startTime returned by resetDataImpl()");
        }
        $this->hits = 0;
        $this->timeToWait = 0;

        return $startTime;
    }

    //------------------------------------


    /**
     * Refresh hits and timeToWait
     * 
     * @param int $timestamp
     */
    private function refreshState($timestamp) {
        $startTime = $timestamp;
        $this->hits = 0;
        $this->timeToWait = 0;
        $this->readDataImpl($this->hits, $startTime);
        $this->window = new \GodsDev\RateLimiter\TimeWindow($startTime, $this->period);

        if ($this->window->isActive($timestamp) == false) {
            //a new, clean period
            $startTime = $this->reset($timestamp);
            $this->window = new \GodsDev\RateLimiter\TimeWindow($startTime, $this->period);
        } else if ($this->hits >= $this->rate) {
            //within the period, and there are no remaining hits to use
            $this->timeToWait = $this->window->getTimeToNext($timestamp);
        }
    }

}
