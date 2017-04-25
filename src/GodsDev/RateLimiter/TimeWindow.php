<?php

namespace GodsDev\RateLimiter;

/*
 * Represents a concrete time interval (active, future or passed).
 * A TimeWindow object is determined by its "start time" and its "period"
 * 
 * TODO rename active to current?
 *
 * fig1:
 * <code>
 *   
 *                         timestamp
 *                             |
 *   +---------------+---------------+---------------+     +---------------+
 *   |  TimeWindow1  |  TimeWindow2  |  TimeWindow3  |     |  TimeWindowN  |
 *   +---------------+---------------+---------------+ ... +---------------+
 *   |               |               |
 *   |   period      |           endTime2, startTime3
 *   |               |
 *  startTime1   endTime1,startTime2
 *   
 * </code>
 *   
 * Given an TimeWindow instance TimeWindow2 from a fig1: 
 * <ul>
 *   <li> A "Next TimeWindow" to TimeWindow2 is TimeWindow3
 *   <li> A startTime of TimeWindow2 has a value of startTime2, the same as the value of endTime1
 *   <li> TimeWindow1 endtime can be computed, has value of startTime1 + period
 * </ul>
 * 
 * for a timestamp given in a fig1:
 * <ul>
 *    <li> "ACTIVE TimeWindow" is TimeWindow2
 *    <li> "ELAPSED Timewindow" is TimeWindow1 and all TimeWindows before
 *    <li> "FUTURE Timewindow" is TimeWindow3 and all TimeWindows after
 * </ul>
 *
 * @author Tomáš
 */
class TimeWindow {


    private $startTime;
    private $period;


    /**
     * creates a new instance
     *
     * @param int $startTime a timestamp of a start time of this TimeWindow
     * @param int $period duration of this TimeWindow
     */
    public function __construct($startTime, $period) {
        $this->startTime = $startTime;
        $this->period = $period;
    }

    /**
     * @return int a timestamp of start time of this TimeWindow
     */
    public function getStartTime() {
        return $this->startTime;
    }

    /**
     * @return int a period of this TimeWindow
     */
     */
    public function getPeriod() {
        return $this->period;
    }

    /**
     * @return int a timestamp of start time of the next TimeWindow
     */
    public function getEndTime() {
        return $this->getStartTime() + $this->getPeriod();
    }

    /**
     * Tells whether is elapsed against the $timestamp given.
     *
     * @param int $timestamp a reference time stamp
     * @return boolean true if a timestamp given is at or after the end-time of this TimeWindow, false otherwise
     */
    public function isElapsed($timestamp) {
        return ($timestamp >= $this->getEndTime());
    }

    /**
     * Tells whether is in the future against the $timestamp given.
     *
     * @param int $timestamp a reference timestamp
     * @return boolean true if a timestamp given is before start-time of this TimeWindow, false otherwise
     */
    public function isFuture($timestamp) {
        return ($timestamp < $this->getStartTime());
    }

    /**
     *
     * Tells whether a $timestamp given falls into it.
     *
     * @param int $timestamp a reference time stamp
     * @return boolean true if a timestamp given is greater or equals to startTime and less than endTime of this TimeWindow, false otherwise
     */
    public function isActive($timestamp) {
        return (!$this->isElapsed($timestamp) && (!$this->isFuture($timestamp)));
    }


    /**
     * Time to wait until the next window is active, against the $timestamp given.
     *
     * @param int $timestamp a reference time stamp
     * @return int >= 0 for active or future window. < 0 for elapsed window.
     *
     * This expression is true for a window $w and timestamp $ts:
     *   $tw->getEndTime($ts) == $ts + $tw->getTimeToNext($ts)
     */
    public function getTimeToNext($timestamp) {
        return ($this->getEndTime() - $timestamp);
    }
}
