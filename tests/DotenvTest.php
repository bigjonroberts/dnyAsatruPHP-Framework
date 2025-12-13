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
 * TestCase for Asatru\Dotenv
 */
final class DotenvTest extends TestCase
{
    protected static function getMethod($name)
    {
        $class = new ReflectionClass('Asatru\\Dotenv\\DotEnvParser');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testEnvPrivates()
    {
        $inst = Asatru\Dotenv\DotEnvParser::instance();

        $method = self::getMethod('splitItems');
        $result = $method->invokeArgs($inst, array('test="hello world"'));
        $this->assertTrue(isset($result['varname']));
        $this->assertEquals('test', $result['varname']);
        $this->assertTrue(isset($result['varvalue']));
        $this->assertEquals('"hello world"', $result['varvalue']);

        $method = self::getMethod('filterString');
        $result = $method->invokeArgs($inst, array('test="hello world"'));
        $this->assertEquals('hello world', $result);

        $method = self::getMethod('getAsDataType');
        $result = $method->invokeArgs($inst, array('"hello world"'));
        $this->assertEquals('hello world', $result);
        $result = $method->invokeArgs($inst, array('null'));
        $this->assertEquals(null, $result);
        $result = $method->invokeArgs($inst, array('true'));
        $this->assertEquals(true, $result);
        $result = $method->invokeArgs($inst, array('off'));
        $this->assertEquals(false, $result);
        $result = $method->invokeArgs($inst, array('123'));
        $this->assertEquals(123, $result);
        $result = $method->invokeArgs($inst, array('3.14'));
        $this->assertEquals(3.14, $result);
    }

    public function testEnvClass()
    {
        $inst = Asatru\Dotenv\DotEnvParser::instance();
        $inst->parse(__DIR__ . '/../.env');
        $this->assertTrue($inst->has_error() === false);
        $this->assertTrue($inst->errorStr() === '');
        $this->assertTrue($inst->query('APP_NAME') === 'Asatru PHP');
        $inst->clear();
        $this->assertTrue($inst->query('APP_NAME') === null);
        $this->assertTrue($inst->query('APP_NAME', 'fallback') === 'fallback');
    }

    public function testEnv()
    {
        $result = env_parse(__DIR__ . '/../.env');
        $this->assertTrue(env_has_error() === false);
        $this->assertTrue(env_get('APP_NAME') === 'Asatru PHP');
    }
}