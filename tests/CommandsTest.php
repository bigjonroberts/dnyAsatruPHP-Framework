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
 * TestCase for Asatru\Commands
 */
final class CommandsTest extends TestCase
{
    protected static function getMethod($name)
    {
        $class = new ReflectionClass('Asatru\\Commands\\CustomCommands');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testLoadCommands()
    {
        $argv = ($argv ?? []);
        $argc = ($argc ?? 0);
        $cmdmgr = new Asatru\Commands\CustomCommands(app_path('/config/commands.php'), $argv);
        $method = self::getMethod('loadCommandConfig');
        $result = $method->invokeArgs($cmdmgr, array(app_path('/config/commands.php')));
        $this->addToAssertionCount(1);
        $cmdmgr->handleCommand('test:cmd');
    }
}