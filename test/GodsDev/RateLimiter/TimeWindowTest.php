<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GodsDev\RateLimiter;

/**
 * TimeWindowTest
 *
 * @author Tomáš
 */
class TimeWindowTest extends \PHPUnit_Framework_TestCase {

    private $w;
    private $timeStamps;

    protected function setUp() {
        $this->w = new TimeWindow(1000, 50);
        $this->timeStamps = [1000 - 100, 1000 - 1, 1000, 1000 + 1 , 1000 + 49, 1000 + 50, 1000 + 51,1000 + 100];
    }

    public function test_End_Time() {
        $this->assertEquals(1000 + 50, $this->w->getEndTime());
    }

    public function test_Get_Start_Time() {
        $this->assertEquals(1000, $this->w->getStartTime());
    }

    public function test_Get_Start_Period() {
        $this->assertEquals(50, $this->w->getPeriod());
    }


    public function test_Is_Active() {
        $this->assertFalse($this->w->isActive(1000 - 1));
        $this->assertTrue($this->w->isActive(1000));
        $this->assertTrue($this->w->isActive(1000 + 1));
        $this->assertTrue($this->w->isActive(1000 + 49));
        $this->assertFalse($this->w->isActive(1000 + 50));
        $this->assertFalse($this->w->isActive(1000 + 50 + 1));
    }

    public function test_Is_Elapsed() {
        $this->assertFalse($this->w->isElapsed(1000 - 1));
        $this->assertFalse($this->w->isElapsed(1000));
        $this->assertFalse($this->w->isElapsed(1000 + 1));
        $this->assertFalse($this->w->isElapsed(1000 + 49));
        $this->assertTrue($this->w->isElapsed(1000 + 50));
        $this->assertTrue($this->w->isElapsed(1000 + 50 + 1));
    }

    public function test_Is_Future() {
        $this->assertTrue($this->w->isFuture(1000 - 1));
        $this->assertFalse($this->w->isFuture(1000));
        $this->assertFalse($this->w->isFuture(1000 + 1));
        $this->assertFalse($this->w->isFuture(1000 + 49));
        $this->assertFalse($this->w->isFuture(1000 + 50));
        $this->assertFalse($this->w->isFuture(1000 + 50 + 1));
    }



    public function test_Get_Time_To_Next() {
        $this->assertEquals(150, $this->w->getTimeToNext(1000 - 100));
        $this->assertEquals(51, $this->w->getTimeToNext(1000 - 1));
        $this->assertEquals(50, $this->w->getTimeToNext(1000));
        $this->assertEquals(49, $this->w->getTimeToNext(1000 + 1));
        $this->assertEquals(1, $this->w->getTimeToNext(1000 + 49));
        $this->assertEquals(0, $this->w->getTimeToNext(1000 + 50));
        $this->assertEquals(-1, $this->w->getTimeToNext(1000 + 51));
        $this->assertEquals(-50, $this->w->getTimeToNext(1000 + 100));
    }


    public function test_EndTime_Is_StartTime_Plus_Period() {
        $this->assertEquals($this->w->getStartTime() + $this->w->getPeriod(), $this->w->getEndTime());

    }



    public function test_EndTime_TimeToNext_Relation() {
        $tw = $this->w;
        foreach ($this->timeStamps as $ts) {
            $this->assertTrue(
                    $tw->getEndTime($ts) == $ts + $tw->getTimeToNext($ts)
            );
        }
    }

}
