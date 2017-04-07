<?php

namespace GodsDev\RateLimiter;

use GodsDev\RateLimiter\RateLimiterInterface;

/**
 * Provides a base test class for ensuring compliance with the RateLimiterInterface.
 *
 * Implementors can extend the class and implement abstract methods to run this
 * as part of their test suite.
 *
 * override the setUp() method, call a parent->setUp() in it.
 *
 */
abstract class RateLimiterInterfaceTest extends \PHPUnit_Framework_TestCase
{
    private $limiterWrapper;

    /**
     * @return \GodsDev\RateLimiter\RateLimiterInterface new RateLimiterInterface instance
     */
    abstract public function createRateLimiter();

    /**
     * @param \GodsDev\RateLimiter\RateLimiterInterface RateLimiter instance
     *
     * @return \GodsDev\RateLimiter\LimiterTimeWrapper new LimiterTimeWrapper instance
     */
    abstract public function createLimiterTimeWrapper(\GodsDev\RateLimiter\RateLimiterInterface $rateLimiter);


    protected function setUp() {
        //echo("==sUp start==");
        $limiter = $this->createRateLimiter();
        $this->limiterWrapper = $this->createLimiterTimeWrapper($limiter);
        //echo("==sUp end==");
    }

    /**
     *
     * @return \GodsDev\RateLimiter\LimiterTimeWrapper
     */
    protected function getLimiterWrapper() {
        return $this->limiterWrapper;
    }



    //-AUXILIARY-------------------------------------------------------------------------

    /**
     * Makes $requestCount requests within a custom time $period
     * does not reset the rateLimiter
     * <ul>
     *   <li>Assures a time period is fully consumed
     *   <li>Assures a requestCount requests are made
     * </ul>
     * @param integer $requestCount number of requests
     * @param integer $period in seconds
     * @param \GodsDev\RateLimiter\LimiterTimeWrapper $w LimiterTimeWrapper instance
     *
     * @return number of successful rateLimiter calls (always <= $requestCount)
     */
    protected function makeEquallyDistributedCalls($requestCount, $period, \GodsDev\RateLimiter\LimiterTimeWrapper $w, $methodName) {
        $timeSpentOnWaiting = 0;
        $timeDelta = floor($period / $requestCount);
        $successCallCount = 0;
        for ($n = 0; $n < $requestCount; $n++) {
            if ($w->inc()) {
                $successCallCount++;
            }
            $w->wait($timeDelta, "makeEquallyDistributedCalls in [$methodName]");
            $timeSpentOnWaiting += $timeDelta;
        }

        $additionalTimeToWait = ($period - $timeSpentOnWaiting) + 1;
        if ($additionalTimeToWait > 0) {
            $w->wait($additionalTimeToWait, "makeEquallyDistributedCalls additionalTime in [$methodName]");
        }
        return $successCallCount;
    }


    //-TESTS-------------------------------------------------------------------------

    public function testImplements() {
        $this->assertInstanceOf('GodsDev\RateLimiter\RateLimiterInterface', $this->getLimiterWrapper()->getLimiter());
    }

    public function testMinimalRateAndPeriodValues() {
        $w = $this->getLimiterWrapper();

        $this->assertGreaterThanOrEqual(2, $w->getLimiter()->getRate(), 'less than a minimal rate value is provided');
        $this->assertGreaterThanOrEqual(4, $w->getLimiter()->getPeriod(), 'less than a minimal period value is provided');
    }


    public function testInitialLimiterValues() {
        $w = $this->getLimiterWrapper();

        $this->assertEquals(0, $w->getHits(), 'zero hits after creation');
        $this->assertEquals(0, $w->getTimeToWait(), 'needs no wait after creation');
    }

    public function testResetLimiter() {
        $w = $this->getLimiterWrapper();

        $this->assertTrue($w->inc(), 'can inc at the first time');
        $this->assertEquals(1, $w->getHits(), 'hits have incremeted after inc');

        $w->reset();

        $this->assertEquals(0, $w->getHits(), 'zero hits after a reset');
        $this->assertEquals(0, $w->getTimeToWait(), 'no need to wait after a reset');
        $this->assertTrue($w->inc(), 'can inc after a reset');
        $this->assertEquals(1, $w->getHits(), 'hits have incremeted after inc after reset');
    }

    public function testNoHitsZeroWaitAfterPeriodHasGone() {
        $w = $this->getLimiterWrapper();
        $period = $w->getLimiter()->getPeriod();


        $this->assertTrue($w->inc(), 'can inc on a start');
        $this->assertEquals(1, $w->getHits(), 'hits have incremeted after inc');
        $w->wait($period + 1, "testNoHitsZeroWaitAfterPeriodHasGone"); //we exceed the limiter's period
        $this->assertEquals(0, $w->getHits(), 'zero hits after period was exceeded');
        $this->assertEquals(0, $w->getTimeToWait(), 'zero time to wait after period was exceeded');
    }

    public function testTimeToWait() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $halfPeriod = ceil($period / 2);
        $this->makeEquallyDistributedCalls($rate, $halfPeriod, $w, "testTimeToWait");

        $this->assertGreaterThan(0, $w->getTimeToWait(), 'has to wait for a positive-amount of time within an exhausted period');
        $this->assertLessThanOrEqual($halfPeriod, $w->getTimeToWait(), 'has to wait for less than a half of the period within an exhausted period');
    }


    public function testTimeToWaitBurst() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $this->makeEquallyDistributedCalls($rate, 0, $w, "testTimeToWaitBurst");

        $this->assertGreaterThan(0, $w->getTimeToWait(), 'has to wait almost all the time within an exhausted period');
        $this->assertLessThanOrEqual($period - 1, $w->getTimeToWait(), 'has to wait for less than whole period within an exhausted period');
    }

    public function testTooManyRequestsInOnePeriodDoNotAffectHitRate() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $successCalls = $this->makeEquallyDistributedCalls($rate * 3, $period - 3, $w, "testTooManyRequestsInOnePeriodDoNotAffectHitRate");

        $this->assertEquals($rate, $w->getHits(), 'allowed <rate> hits within one period');
        $this->assertEquals($rate, $successCalls, 'allowed <rate> successful requests within one period');
    }

    public function testReadyInTheNextPeriod() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $successCalls = $this->makeEquallyDistributedCalls($rate, $period - 3, $w, "testReadyInTheNextPeriod");

        $this->assertEquals($rate, $w->getHits(), 'allowed <rate> hits within one period');
        $this->assertEquals($rate, $successCalls, 'allowed <rate> successful requests within one period');


        $w->wait(3, "testConsumeNextPeriod");

        $this->assertEquals(0, $w->getHits(), 'was reset in the next period');
        $this->assertEquals(0, $w->getTimeToWait(), 'was reset in the next period');

        $successCallsInNextPeriod = $this->makeEquallyDistributedCalls($rate, $period - 3, $w, "testReadyInTheNextPeriod");

        $this->assertEquals($rate, $w->getHits(), 'allowed <rate> hits within the next period');
        $this->assertEquals($rate, $successCallsInNextPeriod, 'allowed <rate> successful requests within the next period');
    }

//------------------------------------------------------------------------------




    public function testTheLimiterFlow() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $this->assertEquals(0, $w->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $w->getTimeToWait());

        $numberOfHits = $this->makeEquallyDistributedCalls($rate, $period - 2, $w, "doTheLimiterFlow");
        //number of requests
        $this->assertEquals($rate, $numberOfHits, 'full count after equallyDistributed calls');
        $this->assertEquals($rate, $w->getHits());

        //one more request within a perion should return false
        $this->assertFalse($w->inc(), 'no over-increment within the period');
        //and again, it should return false
        $this->assertFalse($w->inc(), 'no over-increment within the period 2');

        //we must wait a while for a next successful inc
        $this->assertTrue($w->getTimeToWait() > 0, "must wait within a fully-consumed period");

        //number of requests should remain the same: a maximum
        $this->assertEquals($rate, $w->getHits(), "number of requests should remain the same: a maximum");


        $w->wait(2);

        //no waiting needed
        $this->assertEquals(0, $w->getTimeToWait(), "no need to wait when a period is over");

        $this->assertEquals(0, $w->getHits(), "zero hits when a period is over");
        //yes we can
        $this->assertTrue($w->inc(), "can increment again when a period is over");
        //we can ever more
        $this->assertEquals(0, $w->getTimeToWait(), "no need to wait in a new, unconsumed period");
    }


}


/**
 * real/synthetic time RateLimiter wrapper
 *
 * Allows an injection of synthetic time instead a real one, to speed up time-dependent tests.
 */
class LimiterTimeWrapper {
    private $realTimeFlag; //boolean. if true, waits truly and sends no timestamp argument to limiter's inc method
    private $limiter;
    private $time;

    /**
     *
     * @param RateLimiterInterface $limiter
     * @param boolean $useRealTimeFlag if true, waits truly and sends no argument to limiter's inc method
     * @param integer $startTime a synthetic start time offset, defaults to 0 (only valid if $useRealTimeFlag is set to false)
     *
     * @return self
     */
    public function __construct(RateLimiterInterface $limiter, $useRealTimeFlag, $startTime = 0) {
        $this->limiter = $limiter;
        $this->realTimeFlag = $useRealTimeFlag;
        if ($this->realTimeFlag == true) {
            $startTime = time();
        }
        $this->reset($startTime);
    }

    public function reset($startTime = 0) {
        if ($this->realTimeFlag == true) {
            //echo (":LimiterTimeWrapper reset startTime [$startTime]:");
            $this->getLimiter()->reset();
        } else {
            //echo (":LimiterTimeWrapper reset preset startTime [$startTime]:");
            $this->getLimiter()->reset($startTime);
        }
        $this->time = $startTime;
    }

    public function getLimiter() {
        return $this->limiter;
    }

    public function getTime() {
        if ($this->realTimeFlag) {
            return time();
        } else {
            return $this->time;
        }
    }

    public function inc() {
        if ($this->realTimeFlag) {
            return $this->limiter->inc();
        } else {
            return $this->limiter->inc($this->getTime());
        }
    }

    public function getHits() {
        if ($this->realTimeFlag) {
            return $this->limiter->getHits();
        } else {
            return $this->limiter->getHits($this->getTime());
        }
    }

    public function getTimeToWait() {
        if ($this->realTimeFlag) {
            return $this->limiter->getTimeToWait();
        } else {
            return $this->limiter->getTimeToWait($this->getTime());
        }
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
