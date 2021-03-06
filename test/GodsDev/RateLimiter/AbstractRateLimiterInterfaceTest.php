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
abstract class AbstractRateLimiterInterfaceTest extends \PHPUnit_Framework_TestCase {

    private $timeWrapper;

    /**
     * @return \GodsDev\RateLimiter\RateLimiterInterface new RateLimiterInterface instance
     */
    abstract public function createRateLimiter($rate, $period);

    /**
     * 
     * @return int seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
     */
    protected function getInitialTime() {
        return time();
    }

    protected function setUp() {
        $limiter = $this->createRateLimiter(30, 10);
        $this->timeWrapper = new \GodsDev\RateLimiter\RateLimiterTimeWrapper($limiter, $this->getInitialTime());
    }

    /**
     *
     * @return \GodsDev\RateLimiter\RateLimiterTimeWrapper
     */
    protected function getLimiterWrapper() {
        return $this->timeWrapper;
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
     * @param integer $period in time-units
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

    public function test_At_Least_1_Hit_Per_1_TimeUnit() {
        $w = $this->getLimiterWrapper();

        $this->assertGreaterThanOrEqual(1, $w->getLimiter()->getRate(), 'less than a minimal rate value is provided');
        $this->assertGreaterThanOrEqual(1, $w->getLimiter()->getPeriod(), 'less than a minimal period value is provided');
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

        $w->resetLimiter();
        $this->test_Zero_Hits_Zero_TimeToWait();
    }

    public function test_Inc_Increments_Hits_By_One() {
        $w = $this->getLimiterWrapper();

        $currentHits = $w->getHits();
        $newHits = $w->inc();
        $this->assertTrue($newHits == $currentHits + 1, 'call of inc() increments hits by one');
    }

    public function test_StartTime_Plus_Period_Means_New_Fresh_Period() {
        $w = $this->getLimiterWrapper();
        $period = $w->getLimiter()->getPeriod();

        $w->inc();
        $w->inc();
        $this->assertEquals(2, $w->getHits(), "a hit is made");
        $w->wait($period - 1);
        $this->assertEquals(2, $w->getHits(), "a hit count remain the same before end of a period");
        $w->wait(1);
        $this->test_Zero_Hits_Zero_TimeToWait();
    }

    public function test_Can_Increment_Before_And_After_Reset_In_Period() {
        $w = $this->getLimiterWrapper();

        $this->test_Inc_Increments_Hits_By_One();
        $w->resetLimiter();
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

    public function test_Too_Many_Requests_In_One_Period_Do_Not_Affect_Number_Of_Hits() {
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

        $timeBefore = $w->getStartTime() - 1;
        $this->assertEquals(0, $w->getLimiter()->getHits($timeBefore), "if timestamp before start time, reset the limiter to have 0 hits");
        $this->assertEquals(0, $w->getLimiter()->getTimeToWait($timeBefore), "if timestamp before start time, reset the limiter to have 0 timeToWait");
    }

    public function test_StartTime_Is_Within_TimeWindow_Active_State() {
        $l = $this->getLimiterWrapper()->getLimiter();

        $t = $this->getInitialTime();

        $this->assertLessThanOrEqual($t, $l->getStartTime($t));
        $this->assertGreaterThan($t - $l->getPeriod(), $l->getStartTime($t));
    }

    protected function consume_More_Hits_At_Once($hitsToBeConsumed) {
        $l = $this->getLimiterWrapper()->getLimiter();
        $t = $this->getLimiterWrapper()->getTime();

        $hitsReallyConsumed = $l->inc($t, $hitsToBeConsumed);
        $this->assertLessThanOrEqual($hitsToBeConsumed, $hitsReallyConsumed);
        return $hitsReallyConsumed;
    }

    public function test_Consume_More_Hits_At_Once() {
        $l = $this->getLimiterWrapper()->getLimiter();
        $t = $this->getLimiterWrapper()->getTime();

        $this->assertEquals(2, $this->consume_More_Hits_At_Once(2));
        $this->assertEquals(2, $l->getHits($t));
        $this->assertEquals(0, $l->getTimeToWait($t));

        $this->assertEquals(3, $this->consume_More_Hits_At_Once(3));
        $this->assertEquals(5, $l->getHits($t));
        $this->assertEquals(0, $l->getTimeToWait($t));
    }

    public function test_Consume_All_Hits_At_Once() {
        $l = $this->getLimiterWrapper()->getLimiter();
        $t = $this->getLimiterWrapper()->getTime();
        $fullHitCount = $l->getRate();

        $this->assertEquals($fullHitCount, $this->consume_More_Hits_At_Once($fullHitCount));
        $this->assertEquals($fullHitCount, $l->getHits($t));
        $this->assertEquals($l->getPeriod(), $l->getTimeToWait($t));
    }

    public function test_Consume_Only_Some_Hits_If_Not_Sufficient_Capacity_On_Empty_Limiter() {
        $l = $this->getLimiterWrapper()->getLimiter();
        $t = $this->getLimiterWrapper()->getTime();

        $this->assertEquals($l->getRate(), $this->consume_More_Hits_At_Once($l->getRate() + 1));
        $this->assertEquals($l->getRate(), $l->getHits($t));
        $this->assertEquals($l->getPeriod(), $l->getTimeToWait($t));
    }

    public function test_Consume_Only_Some_Hits_If_Not_Sufficient_Capacity() {
        $l = $this->getLimiterWrapper()->getLimiter();
        $t = $this->getLimiterWrapper()->getTime();

        $this->assertEquals(2, $this->consume_More_Hits_At_Once(2));
        $this->assertEquals(2, $l->getHits($t));
        $this->assertEquals(0, $l->getTimeToWait($t));

        $nextHitCount = 2 + $l->getRate();

        $this->assertEquals($l->getRate() - 2, $this->consume_More_Hits_At_Once($nextHitCount));
        $this->assertEquals($l->getRate(), $l->getHits($t));
        $this->assertEquals($l->getPeriod(), $l->getTimeToWait($t));
    }

//------------------------------------------------------------------------------




    public function test_Limiter_Flow() {
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
