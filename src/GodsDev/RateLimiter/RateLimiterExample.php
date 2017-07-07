<?php

namespace GodsDev\RateLimiter;

/*
 *
 */

class RateLimiterExample extends \GodsDev\RateLimiter\AbstractRateLimiter {

    private $cHits;
    private $cStartTime;
    private $isWrong;

    /**
     * 
     * @param int $rate
     * @param int $period
     * @param bool $isWrongFlag
     */
    public function __construct($rate, $period, $isWrongFlag = false) {
        parent::__construct($rate, $period);
        $this->isWrong = $isWrongFlag;
    }

    /**
     * 
     * @param int $hits
     * @param int $startTime
     */
    protected function readDataImpl(&$hits, &$startTime) {
        $hits = $this->cHits;
        $startTime = $this->cStartTime;
    }

    /**
     * 
     * @param int $startTime
     * @return int
     */
    protected function resetDataImpl($startTime) {
        $this->cStartTime = $startTime;
        $this->cHits = 0;

        if (!$this->isWrong) {
            return $this->cStartTime;
        }
    }

    /**
     * 
     * @param int $lastKnownHitCount
     * @param int $lastKnownStartTime
     * @param int $sanitizedIncrement
     * @return int
     */
    protected function incrementHitImpl($lastKnownHitCount, $lastKnownStartTime, $sanitizedIncrement) {
        $this->cHits = $lastKnownHitCount + $sanitizedIncrement;
        return $sanitizedIncrement;
    }

}
