<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
     * @param integer $hits
     * @param integer $startTime
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
     * @return integer trueStartTime can be aligned, for example
     */
    abstract protected function resetDataImpl($startTime);

    /**
     * does the incremetation of hits
     *
     * @param integer $hits number of hits
     *
     * @return boolean true if the incrementation was successful, false otherwise
     */
    abstract protected function incrementHitImpl();


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

    public function inc($timestamp) {
        $this->refreshState($timestamp);
        if ($this->timeToWait == 0 && $this->hits < $this->rate) {
            return $this->incrementHitImpl();
        } else {
            return false;
        }
    }


    /**
     *
     * @param integer $timestamp
     * @return integer startTime
     */
    public function reset($timestamp) {
        $startTime = $this->resetDataImpl($timestamp);
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
