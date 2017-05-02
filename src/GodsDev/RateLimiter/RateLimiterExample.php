<?php

namespace GodsDev\RateLimiter;

/*
 *
 */

class RateLimiterExample extends \GodsDev\RateLimiter\AbstractRateLimiter {

    private $cHits;
    private $cStartTime;
    private $isWrong;


    public function __construct($rate, $period, $isWrongFlag = false) {
        parent::__construct($rate, $period);
        $this->isWrong = $isWrongFlag;
    }

    protected function readDataImpl(&$hits, &$startTime) {
            $hits = $this->cHits;
            $startTime = $this->cStartTime;
    }

    protected function resetDataImpl($startTime) {
        $this->cStartTime = $startTime;
        $this->cHits = 0;

        if (!$this->isWrong) {
            return $this->cStartTime;
        }
    }

    protected function incrementHitImpl($lastKnownHitCount, $lastKnownStartTime, $sanitizedIncrement) {
        $this->cHits = $lastKnownHitCount + $sanitizedIncrement;
        return $sanitizedIncrement;
    }


}
