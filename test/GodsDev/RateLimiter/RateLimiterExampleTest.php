<?php

namespace GodsDev\RateLimiter;


/**
 * Description of RateLimiterMysqlTest
 *
 * @author Tomáš
 */
class RateLimiterExampleTest extends AbstractRateLimiterInterfaceTest {

    public function createRateLimiter($rate, $period) {
        return new \GodsDev\RateLimiter\RateLimiterExample($rate, $period);
    }

    protected function setUp() {
        parent::setUp();
    }

}
