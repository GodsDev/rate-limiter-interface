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
    abstract protected function readDataImpl(&$hits, &$startTime);


    /*
     * This method is called when a limiter's data is not found
     *
     * @param integer $startTime
     *
     * @see readDataImpl
     * @see resetDataImpl
     */
    abstract protected function createDataImpl($startTime);

    /**
     * does the incremetation of hits
     *
     * @param integer $hits number of hits
     *
     * @return boolean true if the incrementation was successful, false otherwise
     */
    abstract protected function incrementHitImpl();

    /**
     * resets the limiter. Should set hits to 0.
     *
     * @param integer startTime
     */
    abstract protected function resetDataImpl($startTime);

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
        return $this->getWindow()->getStartTime();
    }

    public function inc($timestamp) {
        $this->refreshState($timestamp);
        if ($this->timeToWait == 0 && $this->hits < $this->rate) {
            return $this->incrementHitImpl();
        } else {
            return false;
        }
    }

    public function reset($timestamp) {
        $this->resetInner($timestamp);
        $this->resetDataImpl($timestamp);
    }


    /**
     *
     * @return \GodsDev\RateLimiter\TimeWindow instance
     */
    protected function getWindow() {
        return $this->window;
    }


    private function refreshState($timestamp) {
        $fetchSuccess = $this->readDataImpl($this->hits, $this->startTime);
        if ($fetchSuccess == false) {
            $this->resetInner($timestamp);
            $this->createDataImpl($this->startTime);
        }

        if ($this->getWindow()->isActive($timestamp) == false) {
            //a new, clean period
            $this->reset($timestamp);
        } else if ($this->hits < $this->rate) {
            //within the period, and there are free hits
            $this->timeToWait = 0;
        } else {
            //within the period, and there are no free hits to use
            $this->timeToWait = $this->getWindow()->getTimeToNext($timestamp);
        }
    }


    private function resetInner($timestamp) {
        $this->window = new TimeWindow($timestamp, $this->period);
        $this->timeToWait = 0;
        $this->hits = 0;
    }


}
