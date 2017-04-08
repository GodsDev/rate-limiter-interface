<?php

namespace GodsDev\RateLimiter;


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
     * @return \GodsDev\RateLimiter\RateLimiterTimeWrapper new LimiterTimeWrapper instance
     */
    abstract public function createRateLimiterTimeWrapper(\GodsDev\RateLimiter\RateLimiterInterface $rateLimiter);


    protected function setUp() {
        $limiter = $this->createRateLimiter();
        $this->limiterWrapper = $this->createRateLimiterTimeWrapper($limiter);
    }

    /**
     *
     * @return \GodsDev\RateLimiter\RateLimiterTimeWrapper
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
     * @param \GodsDev\RateLimiter\RateLimiterTimeWrapper $w LimiterTimeWrapper instance
     *
     * @return number of successful rateLimiter calls (always <= $requestCount)
     */
    protected function makeEquallyDistributedCalls($requestCount, $period, \GodsDev\RateLimiter\RateLimiterTimeWrapper $w, $methodName) {
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


    protected function ensureMaximumHitsIsMade() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $this->assertEquals($rate, $w->getHits(), '<rate> hits consumed within the period');
    }

    //-TESTS-------------------------------------------------------------------------

    public function testMinimalRateAndPeriodValues() {
        $w = $this->getLimiterWrapper();

        $this->assertGreaterThanOrEqual(2, $w->getLimiter()->getRate(), 'less than a minimal rate value is provided');
        $this->assertGreaterThanOrEqual(4, $w->getLimiter()->getPeriod(), 'less than a minimal period value is provided');
    }


    public function test_Implements_RateLimiterInterface() {
        $this->assertInstanceOf('GodsDev\RateLimiter\RateLimiterInterface', $this->getLimiterWrapper()->getLimiter());
    }

    //---------------

    public function test_Zero_Hits_Zero_TimeToWait() {
        $w = $this->getLimiterWrapper();

        $this->assertEquals(0, $w->getHits(), 'zero hits after creation');
        $this->assertEquals(0, $w->getTimeToWait(), 'needs no wait after creation');
    }

    public function test_Zero_Hits_Zero_TimeToWait_After_Reset() {
        $w = $this->getLimiterWrapper();

        $w->reset();
        $this->test_Zero_Hits_Zero_TimeToWait();
    }

    public function test_Inc_Increments_Hits_By_One() {
        $w = $this->getLimiterWrapper();

        $currentHits = $w->getHits();
        $newHits = $w->inc();
        $this->assertTrue($newHits == $currentHits + 1, 'call of inc() increments hits by one');
    }

    public function test_Can_Increment_Before_And_After_Reset_In_Period() {
        $w = $this->getLimiterWrapper();

        $this->test_Inc_Increments_Hits_By_One();
        $w->reset();
        $this->test_Zero_Hits_Zero_TimeToWait();
        $this->test_Inc_Increments_Hits_By_One();
    }

    public function test_No_Hits_Zero_Wait_After_End_Of_Period() {
        $w = $this->getLimiterWrapper();
        $period = $w->getLimiter()->getPeriod();

        $this->test_Inc_Increments_Hits_By_One();

        $w->wait($period + 1, "test_No_Hits_Zero_Wait_After_End_Of_Period"); //we exceed the limiter's period

        $this->test_Zero_Hits_Zero_TimeToWait();
    }

    public function test_Can_Increment_After_End_Of_Period() {
        $this->test_No_Hits_Zero_Wait_After_End_Of_Period();
        $this->test_Inc_Increments_Hits_By_One();
    }

    public function test_TimeToWait_Half_A_Period() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $halfPeriod = ceil($period / 2);
        $this->makeEquallyDistributedCalls($rate, $halfPeriod, $w, "test_TimeToWait_Half_A_Period");

        $this->assertGreaterThan(0, $w->getTimeToWait(), 'has to wait for a positive-amount of time within an exhausted period');
        $this->assertLessThanOrEqual($halfPeriod, $w->getTimeToWait(), 'has to wait for less than a half of the period within an exhausted period');
    }


    public function test_TimeToWait_Almost_A_Period_If_Burst() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $this->makeEquallyDistributedCalls($rate, 0, $w, "test_TimeToWait_Almost_A_Period_If_Burst");

        $this->assertGreaterThan(0, $w->getTimeToWait(), 'has to wait almost all the time within an exhausted period');
        $this->assertLessThanOrEqual($period - 1, $w->getTimeToWait(), 'has to wait for less than whole period within an exhausted period');
    }

    public function test_Too_Many_Requests_In_One_Period_Do_NotAffectNumber_Of_Hits() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $successCalls = $this->makeEquallyDistributedCalls($rate * 3, $period - 3, $w, "Too_Many_Requests_In_One_Period_Do_NotAffectNumber_Of_Hits");

        $this->ensureMaximumHitsIsMade();
        $this->assertEquals($rate, $successCalls, 'allowed <rate> successful requests within one period');
    }


    public function test_Ready_In_The_Next_Period() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $successCalls = $this->makeEquallyDistributedCalls($rate, $period - 3, $w, "testReadyInTheNextPeriod");

        $this->ensureMaximumHitsIsMade();
        $this->assertEquals($rate, $successCalls, 'allowed <rate> successful requests within one period');

        $w->wait(3, "testConsumeNextPeriod");

        $this->test_Zero_Hits_Zero_TimeToWait();

        $successCallsInNextPeriod = $this->makeEquallyDistributedCalls($rate, $period - 3, $w, "testReadyInTheNextPeriod");

        $this->ensureMaximumHitsIsMade();
        $this->assertEquals($rate, $successCallsInNextPeriod, 'allowed <rate> successful requests within the next period');
    }


    public function test_No_Hits_No_TimeToWait_If_Timestamp_Before_StartTime() {
        $w = $this->getLimiterWrapper();
        $rate = $w->getLimiter()->getRate();
        $period = $w->getLimiter()->getPeriod();

        $halfPeriod = floor($period / 2);
        $this->makeEquallyDistributedCalls($rate, $halfPeriod, $w, "test_No_Hits_No_TimeToWait_If_Timestamp_Before_StartTime");

        $this->ensureMaximumHitsIsMade();

        $timeBefore = $w->getTime() - $period - 1;
        $this->assertEquals(0, $w->getLimiter()->getHits($timeBefore), "if timestamp before start time, reset the limiter to have 0 hits");
        $this->assertEquals(0, $w->getLimiter()->getTimeToWait($timeBefore), "if timestamp before start time, reset the limiter to have 0 timeToWait");
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
