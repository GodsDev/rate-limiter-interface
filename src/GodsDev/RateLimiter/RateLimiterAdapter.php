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
abstract class RateLimiterAdapter implements \GodsDev\RateLimiter\RateLimiterInterface {

    protected $rate;
    protected $period;

    protected $timeToWait;

    protected $hits;
    protected $startTime;

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
     * sets actual values from implementation's data source (db, for example)
     *
     * @param integer $hits
     * @param integer $startTime
     *
     * @returns boolean false if data source does not exists, true otherwise
     *
     * Note: Do not implement a fail-safe logic if data is not found. Override the createDataImpl method instead.
     *
     * @see createDataImpl
     */
    abstract protected function fetchDataImpl(&$hits, &$startTime);


    /*
     * This method is called when a limiter's data is not found
     *
     * @param integer $hits
     * @param integer $startTime
     *
     * @see fetchDataImpl
     * @see resetDataImpl
     */
    abstract protected function createDataImpl($hits, $startTime);

    /**
     * stores the number of hits
     *
     * @param integer $hits number of hits
     */
    abstract protected function storeHitsImpl($hits);

    /**
     * resets the limiter. Should set hits to 0.
     *
     * @param integer startTime
     */
    abstract protected function resetDataImpl($hits, $startTime);

    public function getHits($timestamp = null) {
        $this->refreshState($timestamp);
        return $this->hits;
    }

    //--------------------------------------------------------------------------


    public function getPeriod() {
        return $this->period;
    }

    public function getRate() {
        return $this->rate;
    }

    /**
     *
     * @param integer $timestamp
     * @return integer time elapsed since startTime
     */
    private function timeElapsed($timestamp) {
        return $timestamp - $this->startTime;
    }

    protected function refreshState($timestamp) {
        if (is_null($timestamp)) {
            $timestamp = time();
        }
        $fetchSuccess = $this->fetchDataImpl($this->hits, $this->startTime);
        if ($fetchSuccess == false) {
            $this->resetInner($timestamp);
            $this->createDataImpl($this->hits, $this->startTime);
        }

        if ($this->timeElapsed($timestamp) >= $this->period) {
            //a new, clean period
            $this->reset($timestamp);
        } else if ($this->timeElapsed($timestamp) < 0) {
            //a new, clean period if actual $timestamp is before the starttime
            $this->reset($timestamp);
        } else if ($this->hits < $this->rate) {
            $this->timeToWait = 0;
        } else {
            $this->timeToWait = intval( ceil($this->period - ($timestamp - $this->startTime)) );
        }
    }

    public function getTimeToWait($timestamp = null) {
        $this->refreshState($timestamp);
        return $this->timeToWait;
    }

    public function inc($timestamp = null) {
        $this->refreshState($timestamp);
        if ($this->timeToWait == 0 && $this->hits < $this->rate) {
            $this->hits++;
            $this->storeHitsImpl($this->hits);
            return true;
        } else {
            return false;
        }
    }

    public function reset($timestamp = null) {
        $this->resetInner($timestamp);
        $this->resetDataImpl(0, $timestamp);
    }

    private function resetInner($timestamp) {
        if (is_null($timestamp)) {
            $timestamp = time();
        }
        $this->startTime = $timestamp;
        $this->timeToWait = 0;
        $this->hits = 0;
    }

}
