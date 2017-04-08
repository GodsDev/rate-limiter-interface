<?php

namespace GodsDev\RateLimiter;

/**
 * real/synthetic time RateLimiter wrapper
 *
 * Allows an injection of synthetic time instead a real one, to speed up time-dependent tests.
 */
class RateLimiterTimeWrapper {
    private $realTimeFlag; //boolean. if true, waits truly
    private $limiter;
    private $time;
    private $startTime;

    /**
     *
     * @param RateLimiterInterface $limiter
     * @param boolean $useRealTimeFlag if true, waits truly and sends no argument to limiter's inc method
     * @param integer $startTime a synthetic start time offset, (only valid if $useRealTimeFlag is set to false)
     *
     * @return self
     */
    public function __construct(RateLimiterInterface $limiter, $useRealTimeFlag, $startTime) {
        $this->limiter = $limiter;
        $this->realTimeFlag = $useRealTimeFlag;
        if (!$this->realTimeFlag) {
            $this->time = $startTime;
            $this->startTime = $startTime;
        }
    }

    public function getTime() {
        if ($this->realTimeFlag) {
            return time();
        } else {
            return $this->time;
        }
    }

    public function getStartTime() {
        return $this->startTime;
    }

    public function reset($startTime = null) {
        if ($startTime) {
            $resetTime = $startTime;
        } else {
            $resetTime = $this->getTime();
        }
        $this->time = $resetTime;
        $this->startTime = $resetTime;
        $this->getLimiter()->reset($resetTime);
    }

    public function getLimiter() {
        return $this->limiter;
    }

    public function inc() {
        return $this->limiter->inc($this->getTime());
    }

    public function getHits() {
        return $this->limiter->getHits($this->getTime());
    }

    public function getTimeToWait() {
        return $this->limiter->getTimeToWait($this->getTime());
    }

    /**
     *
     * @param integer $duration duration in seconds
     */
    public function wait($duration, $methodName = "") {
        //echo("\n---wait for [$duration] seconds in [$methodName] ...");
        if ($this->realTimeFlag) {
            sleep($duration);
        }
        $this->time += $duration;
    }

}
