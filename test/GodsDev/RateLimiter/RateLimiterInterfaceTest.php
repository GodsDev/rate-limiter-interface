<?php

namespace GodsDev\RateLimiter;

use GodsDev\RateLimiter\RateLimiterInterface;

/**
 * Provides a base test class for ensuring compliance with the RateLimiterInterface.
 *
 * Implementors can extend the class and implement abstract methods to run this
 * as part of their test suite.
 */
abstract class RateLimiterInterfaceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return RateLimiterInterface instance
     */
    public function getRateLimiter() {
        return $this->getLimiterTimeWrapper()->getLimiter();
    }

    /**
     * @return LimiterTimeWrapper
     */
    abstract public function getLimiterTimeWrapper();


    public function testImplements() {
        $this->assertInstanceOf('GodsDev\RateLimiter\RateLimiterInterface', $this->getRateLimiter());
    }


    public function testResetLimiter() {
        $w = $this->getLimiterTimeWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $this->assertEquals(0, $w->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $w->getTimeToWait());

        $timeDelta = ceil($period / $rate);
        $this->assertTrue($w->inc());
        $this->assertEquals(1, $w->getHits());
        $w->wait($timeDelta);
        $this->assertTrue($w->inc());
        $this->assertEquals(2, $w->getHits());

        $w->reset();

        $this->assertEquals(0, $w->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $w->getTimeToWait());
        $this->assertTrue($w->inc());
        $this->assertEquals(1, $w->getHits());
    }


    public function doTheLimiterFlow() {
        $w = $this->getLimiterTimeWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $this->assertEquals(0, $w->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $w->getTimeToWait());

        $numberOfHits = $this->makeEquallyDistributedCalls($rate, $period);
        //number of requests
        $this->assertEquals($rate, $numberOfHits);
        $this->assertEquals($rate, $w->getHits());

        //one more request within a perion should return false
        $this->assertFalse($w->inc());
        //and again, it should return false
        $this->assertFalse($w->inc());

        //we must wait a while for a next successful inc
        $this->assertTrue($w->getTimeToWait() > 0);

        //number of requests should remain the same: a maximum
        $this->assertEquals($rate, $w->getHits());

        //sure we are over the first period, so we can inc again
        $w->wait($w->getTimeToWait() + 1);

        //no waiting needed
        $this->assertEquals(0, $w->getTimeToWait());

        $this->assertEquals(0, $w->getHits());
        //yes we can
        $this->assertTrue($w->inc());
        //we can ever more
        $this->assertEquals(0, $w->getTimeToWait());
    }

    /**
     * Makes $requestCount requests within a $period
     * does not reset the rateLimiter
     *
     * @param integer $requestCount number of requests
     * @param integer $period in seconds
     *
     * @return number of successful rateLimiter calls (always <= $requestCount)
     */
    public function makeEquallyDistributedCalls($requestCount, $period) {
        $w = $this->getLimiterTimeWrapper();

        $timeDelta = ceil($period / $requestCount);
        $successCallCount = 0;
        for ($n = 0; $n < $requestCount; $n++) {
            if ($w->inc()) {
                $successCallCount++;
            }
            $w->wait($timeDelta);
        }
        return $successCallCount;
    }
}


/**
 * real/synthetic time RateLimiter wrapper
 *
 * Allows an injection of synthetic time instead a real one, to speed up time-dependent tests.
 */
class LimiterTimeWrapper {
    private $realTimeFlag; //boolean. if true, waits truly and sends no argument to limiter's inc method
    private $limiter;
    private $timeElapsed;

    /**
     *
     * @param RateLimiterInterface $limiter
     * @param boolean $useRealTimeFlag if true, waits truly and sends no argument to limiter's inc method
     * @param integer $startTime a synthetic start time offset, defaults to 0
     *
     * @return self
     */
    public function __construct(RateLimiterInterface $limiter, $useRealTimeFlag, $startTime = 0) {
        $this->limiter = $limiter;
        $this->realTimeFlag = $useRealTimeFlag;
        $this->timeElapsed = $startTime;
    }

    public function reset($startTime) {
        if ($this->realTimeFlag) {
            $this->getLimiter()->reset($startTime);
        } else {
            $this->getLimiter()->reset();
        }
        $this->timeElapsed = $startTime;
    }

    public function getLimiter() {
        return $this->limiter;
    }

    public function getTimeElapsed() {
        if ($this->realTimeFlag) {
            return null;
        } else {
            return $this->timeElapsed;
        }
    }

    public function inc() {
        if ($this->realTimeFlag) {
            return $this->limiter->inc();
        } else {
            return $this->limiter->inc($this->getTimeElapsed());
        }
    }

    public function getHits() {
        if ($this->realTimeFlag) {
            return $this->limiter->getHits();
        } else {
            return $this->limiter->getHits($this->getTimeElapsed());
        }
    }

    public function getTimeToWait() {
        if ($this->realTimeFlag) {
            return $this->limiter->getTimeToWait();
        } else {
            return $this->limiter->getTimeToWait($this->getTimeElapsed());
        }
    }

    public function wait($time) {
        if ($this->realTimeFlag) {
            sleep($time);
        }
        $this->timeElapsed += $time;
    }

}
