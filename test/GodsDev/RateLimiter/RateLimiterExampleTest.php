<?php

namespace GodsDev\RateLimiter;

/**
 * Description of RateLimiterMysqlTest
 *
 * @author Tomáš Kraus
 */
class RateLimiterExampleTest extends AbstractRateLimiterInterfaceTest {

    public function createRateLimiter($rate, $period) {
        return new \GodsDev\RateLimiter\RateLimiterExample($rate, $period);
    }

    protected function getInitialTime() {
        return 1000;
    }

    protected function setUp() {
        parent::setUp();
    }

}
