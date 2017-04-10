<?php

namespace GodsDev\RateLimiter;

/*
 * Represents a time interval (running or passed).
 * Consists of:
 * <ul>
 *   <li>start time
 *   <li>period
 * </ul>
 *
 * @author Tomáš
 */
class TimeWindow {

//    const ELAPSED = 0;
//    const ACTIVE = 1;
//    const FUTURE = 2;

    private $startTime;
    private $period;


    /**
     * creates a new instance
     *
     * @param integer $startTime time stamp like value (in time-units)
     * @param integer $period duration of a window (in time-units)
     */
    public function __construct($startTime, $period) {
        $this->startTime = $startTime;
        $this->period = $period;
    }

    public function getStartTime() {
        return $this->startTime;
    }

    public function getPeriod() {
        return $this->period;
    }

    /**
     *
     *
     * @return integer a time stamp of immediately following next TimeWindow
     */
    public function getEndTime() {
        return $this->startTime + $this->period;
    }

    /**
     * Tells whether is elapsed against the $timeStamp given.
     *
     * @param integer $timeStamp a reference time stamp (in time-units)
     * @return boolean true if, false otherwise
     */
    public function isElapsed($timeStamp) {
        return ($timeStamp >= $this->getEndTime());
    }

    /**
     * Tells whether is in the future against the $timeStamp given.
     *
     * @param integer $timeStamp a reference time stamp (in time-units)
     * @return boolean true if, false otherwise
     */
    public function isFuture($timeStamp) {
        return ($timeStamp < $this->startTime);
    }

    /**
     *
     * Tells whether is within the $timeStamp given.
     *
     * @param integer $timeStamp a reference time stamp (in time-units)
     * @return boolean true if, false otherwise
     */
    public function isActive($timeStamp) {
        return (!$this->isElapsed($timeStamp) && (!$this->isFuture($timeStamp)));
    }

    /**
     * Elapsed time since startTime, based on timestamp
     *
     * @param integer $timestamp a reference time stamp (in time-units)
     * @return integer >= 0 for active or elapsed window. < 0 for future window.
     *
     * This expression is true for a window $w and timestamp $ts:
     *   $w->getStartTime($ts) == $ts - $w->getTimeElapsed($ts)
     */
    public function getTimeElapsed($timestamp) {
        return ($timestamp - $this->startTime);
    }


    /**
     * Time to wait until the next window is active, based on timestamp
     *
     * @param integer $timestamp a reference time stamp (in time-units)
     * @return integer >= 0 for active or future window. < 0 for elapsed window.
     *
     * This expression is true for a window $w and timestamp $ts:
     *   $tw->getEndTime($ts) == $ts + $tw->getTimeToNext($ts)
     */
    public function getTimeToNext($timestamp) {
        return ($this->getEndTime() - $timestamp);
    }
}
