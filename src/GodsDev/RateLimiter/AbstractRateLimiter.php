<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// TODO catch possible exceptions from impl methods and rethrow them in the wrapped RateLimiterException

namespace GodsDev\RateLimiter;

/**
 * Description of RateLimiterAdapter
 *
 * @author Tomáš
 */
abstract class AbstractRateLimiter implements \GodsDev\RateLimiter\RateLimiterInterface {

    private $rate;
    private $period;

    private $window;

    private $timeToWait;
    private $hits;

    /**
     * new instance
     *
     * @param type $rate
     * @param type $period
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
     * @param integer $lastKnownHitCount number of hits retrieved by a readDataImpl method
     * @param integer $lastKnownStartTime start time retrieved by a readDataImpl method
     * @param integer $increment number of hits consumed out of $increment. 0 if number of hits consumed per period is too high (i.e. exceeds the getRate() value)
     *
     * @return boolean true if the incrementation was successful, false otherwise
     */
    abstract protected function incrementHitImpl($lastKnownHitCount, $lastKnownStartTime, $increment);


    //--------------------------------------------------------------------------


    public function getPeriod() {
        return $this->period;
    }

    public function getRate() {
        return $this->rate;
    }

    public function getHits($timestamp) {
        $this->refreshState($timestamp);
        return $this->hits;
    }

    public function getTimeToWait($timestamp) {
        $this->refreshState($timestamp);
        return $this->timeToWait;
    }

    public function getStartTime($timestamp) {
        $this->refreshState($timestamp);
        return $this->window->getStartTime();
    }

    public function inc($timestamp, $increment = 1) {
        $this->refreshState($timestamp);
        if ($this->timeToWait == 0 && $this->hits < $this->rate) {
            return $this->incrementHitImpl($this->hits, $this->window->getStartTime(), $increment);
        } else {
            return 0;
        }
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
            //within the period, and there are no free hits to use
            $this->timeToWait = $this->window->getTimeToNext($timestamp);
        }
    }

}
