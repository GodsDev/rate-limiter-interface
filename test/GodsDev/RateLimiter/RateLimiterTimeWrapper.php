<?php

namespace GodsDev\RateLimiter;

/**
 * synthetic time RateLimiter wrapper
 *
 * Allows an injection of synthetic time instead a real one, to cover edge cases and speed up time-dependent tests.
 */
class RateLimiterTimeWrapper {
    private $limiter;
    private $time;
    private $startTime;

    /**
     *
     * @param RateLimiterInterface $limiter
     * @param integer $startTime a synthetic start time offset
     *
     * @return self
     */
    public function __construct(RateLimiterInterface $limiter, $startTime) {
        $this->limiter = $limiter;
        $this->time = $startTime;
        $this->startTime = $startTime;
    }

    public function getTime() {
            return $this->time;
    }

    public function getStartTime() {
        return $this->startTime;
    }

    public function resetLimiter() {
        $this->limiter->reset($this->getTime());
    }

    /**
     *
     * @return \GodsDev\RateLimiter\RateLimiterInterface
     */
    public function getLimiter() {
        return $this->limiter;
    }

    public function inc() {
        return ($this->limiter->inc($this->getTime()) > 0);
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
        $this->time += $duration;
    }

}
