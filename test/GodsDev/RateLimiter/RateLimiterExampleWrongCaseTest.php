<?php

namespace GodsDev\RateLimiter;

/**
 * Description of RateLimiterMysqlTest
 *
 * @author Tomáš
 */
class RateLimiterExampleWrongCaseTest extends \PHPUnit_Framework_TestCase {

    private $limiter;

    protected function setUp() {
        $this->limiter = new RateLimiterExample(10, 10, true);
    }

    public function test_Exception_If_ResetImpl_Returns_Nothing() {
        $this->setExpectedException(RateLimiterException::class);

        $this->limiter->reset(10);
    }
}
