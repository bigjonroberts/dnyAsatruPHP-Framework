<?php

/*
    Asatru PHP (dnyAsatruPHP) developed by Daniel Brendel
    
    (C) 2019 - 2025 by Daniel Brendel
    
    Contact: dbrendel1988<at>gmail<dot>com
    GitHub: https://github.com/danielbrendel/
    
    Released under the MIT license
*/

use PHPUnit\Framework\TestCase;

/**
 * TestCase for Asatru\Events
 */
final class EventsTest extends TestCase
{
    protected static function getMethod($name)
    {
        $class = new ReflectionClass('Asatru\\Events\\EventManager');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testLoadAndRaiseEvent()
    {
        $evtmgr = new Asatru\Events\EventManager(app_path('/config/events.php'));
        $method = self::getMethod('loadEventConfig');
        $result = $method->invokeArgs($evtmgr, array(app_path('/config/events.php')));
        $this->addToAssertionCount(1);
        $evtmgr->raiseEvent('my_event');
    }

    public function testRaiseEvent()
    {
        event('my_event');
        $this->addToAssertionCount(1);
    }
}