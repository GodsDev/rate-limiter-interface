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
        $lim = $this->getRateLimiter();
        $timeWrapper = $this->getLimiterTimeWrapper();
        $rate = $lim->getRate();
        $period = $lim->getPeriod();

        $this->assertEquals(0, $lim->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $lim->getTimeToWait());

        $timeDelta = ceil($period / $rate);
        $this->assertTrue($timeWrapper->incLimiter());
        $this->assertEquals(1, $lim->getHits());
        $timeWrapper->wait($timeDelta);
        $this->assertTrue($timeWrapper->incLimiter());
        $this->assertEquals(2, $lim->getHits());

        $timeWrapper->resetLimiter();

        $this->assertEquals(0, $lim->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $lim->getTimeToWait());
        $this->assertTrue($timeWrapper->incLimiter());
        $this->assertEquals(1, $lim->getHits());
    }


    public function doTheLimiterFlow() {
        $lim = $this->getRateLimiter();
        $wrapper = $this->getLimiterTimeWrapper();
        $rate = $lim->getRate();
        $period = $lim->getPeriod();

        $this->assertEquals(0, $lim->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $lim->getTimeToWait());

        $numberOfHits = $this->makeEquallyDistributedCalls($rate, $period);
        //number of requests
        $this->assertEquals($rate, $numberOfHits);
        $this->assertEquals($rate, $lim->getHits());

        //one more request within a perion should return false
        $this->assertFalse($wrapper->incLimiter());
        //and again, it should return false
        $this->assertFalse($wrapper->incLimiter());

        //we must wait a while for a next successful inc
        $this->assertTrue($lim->getTimeToWait() > 0);

        //number of requests should remain the same: a maximum
        $this->assertEquals($rate, $lim->getHits());

        //sure we are over the first period, so we can inc again
        $wrapper->wait($lim->getTimeToWait() + 1);

        //no waiting needed
        $this->assertEquals(0, $lim->getTimeToWait());

        $this->assertEquals(0, $lim->getHits());
        //yes we can
        $this->assertTrue($wrapper->incLimiter());
        //we can ever more
        $this->assertEquals(0, $lim->getTimeToWait());
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
        $wrapper = $this->getLimiterTimeWrapper();

        $timeDelta = ceil($period / $requestCount);
        $successCallCount = 0;
        for ($n = 0; $n < $requestCount; $n++) {
            if ($wrapper->incLimiter()) {
                $successCallCount++;
            }
            $wrapper->wait($timeDelta);
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

    public function resetLimiter($startTime) {
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

    public function incLimiter() {
        if ($this->realTimeFlag) {
            $this->limiter->inc();
        } else {
            $this->limiter->inc($this->getTimeElapsed());
        }
    }

    public function wait($time) {
        if ($this->realTimeFlag) {
            sleep($time);
        }
        $this->timeElapsed += $time;
    }

}
