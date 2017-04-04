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
     * @return RateLimiterInterface
     */
    abstract public function getRequestRateLmiter();

    public function testImplements() {
        $this->assertInstanceOf('GodsDev\RateLimiter\RateLimiterInterface', $this->getRequestRateLmiter());
    }

    public function testFlowReal() {
        $lim = $this->getRequestRateLmiter();
        $ltw = new LimiterTimeWrapper($lim, true);
        $this->doTheLimiterFlow($ltw);

        $ltw->wait($lim->getPeriod());

        $this->assertEquals(0, $lim->getHits());
        //shoud pass again
        $this->doTheLimiterFlow($ltw);
    }

    public function testReset() {
        $lim = $this->getRequestRateLmiter();
        $wrapper = new LimiterTimeWrapper($lim, true);
        $rate = $lim->getRate();
        $period = $lim->getPeriod();

        $this->assertEquals(0, $lim->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $lim->getTimeToWait());

        $timeDelta = ceil($period / $rate);
        $this->assertTrue($wrapper->inc( $wrapper->getTimeElapsed() ));
        $this->assertEquals(1, $lim->getHits());
        $wrapper->wait($timeDelta);
        $this->assertTrue($wrapper->inc( $wrapper->getTimeElapsed() ));
        $this->assertEquals(2, $lim->getHits());

        $lim->reset();
        $this->assertEquals(0, $lim->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $lim->getTimeToWait());
        $this->assertTrue($wrapper->inc( $wrapper->getTimeElapsed() ));
        $this->assertEquals(1, $lim->getHits());
    }


    public function doTheLimiterFlow(LimiterTimeWrapper $wrapper) {
        $lim = $wrapper->getLimiter();
        $rate = $lim->getRate();
        $period = $lim->getPeriod();

        $this->assertEquals(0, $lim->getHits());
        //can use inc successfuly
        $this->assertEquals(0, $lim->getTimeToWait());

        //n equally distributed requests within a period
        $timeDelta = ceil($period / $rate);
        for ($n = 0; $n < $rate; $n++) {
            $this->assertTrue($wrapper->inc( $wrapper->getTimeElapsed() ));
            $wrapper->wait($timeDelta);
        }
        //number of requests
        $this->assertEquals($rate, $lim->getHits());
        //one more request within a perion should return false
        $this->assertFalse($lim->inc( $wrapper->getTimeElapsed() ));
        //and again, it should return false
        $this->assertFalse($lim->inc( $wrapper->getTimeElapsed() ));

        //we must wait a while for a nest successful inc
        $this->assertTrue($lim->getTimeToWait() > 0);

        //number of requests should remain the same: a maximum
        $this->assertEquals($rate, $lim->getHits());

        //now we have roughly one $timeDelta time to end of the first period

        //sure we are over the first period, so we can inc again
        $wrapper->wait(2 * $timeDelta);

        //no waiting needed
        $this->assertEquals(0, $lim->getTimeToWait());

        $this->assertEquals(0, $lim->getHits());
        //yes we can
        $this->assertTrue($wrapper->inc( $wrapper->getTimeElapsed() ));
        //we can ever more
        $this->assertEquals(0, $lim->getTimeToWait());
    }
}

class DummyTest {
    public function __toString()
    {
    }
}

/**
 * real/synthetic time
 */
class LimiterTimeWrapper {
    private $realTimeFlag; //boolean. if true, waits truly and sends no argument to limiter's inc method
    private $limiter;
    private $timeElapsed;

    public function __construct(RateLimiterInterface $limiter, $useRealTimeFlag) {
        $this->limiter = $limiter;
        $this->realTimeFlag = $useRealTimeFlag;
        $this->timeElapsed = 0;
    }

    public function getLimiter() {
        return $this->limiter;
    }

    public function getTimeElapsed() {
        return $this->timeElapsed;
    }

    public function inc($timestamp) {
        if ($this->realTimeFlag) {
            $this->limiter->inc();
        } else {
            $this->limiter->inc($timestamp);
        }
    }

    public function wait($time) {
        if ($this->realTimeFlag) {
            sleep($time);
        }
        $this->timeElapsed += $time;
    }


}
