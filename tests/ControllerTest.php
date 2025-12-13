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
 * TestCase for Asatru\Controller
 */
final class ControllerTest extends TestCase
{
    protected static function getMethod($name)
    {
        $class = new ReflectionClass('Asatru\\Controller\\ControllerHandler');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testParseUrl()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $controller = new Asatru\Controller\ControllerHandler(app_path('/config/routes.php'));
        $result = $controller->parse('/index');
        $this->assertInstanceOf('Asatru\\View\\ViewHandler', $result);
    }

    public function testPHPArgs()
    {
        $_GET['test_get'] = 'TestCase';
        $_POST['test_post'] = 'TestCase';

        $phpargs = new Asatru\Controller\PHPArgs();
        
        $result = $phpargs->query('test_get');
        $this->assertEquals($_GET['test_get'], $result);

        $result = $phpargs->query('test_post');
        $this->assertEquals($_POST['test_post'], $result);

        $result = $phpargs->all();
        $this->assertIsArray($result);
        $this->assertTrue(count($result) === 2);
        $this->assertTrue(isset($result['test_get']));
        $this->assertTrue(isset($result['test_post']));
    }

    public function testControllerArg()
    {
        $ctrlarg = new Asatru\Controller\ControllerArg('some/test');

        $this->assertEquals(2, $ctrlarg->count());

        $ctrlarg->addArg('another', 'value');
        $this->assertEquals('value', $ctrlarg->arg('another'));
        $this->assertEquals(3, $ctrlarg->count());
    }

    public function testPostValidator()
    {
        $_SESSION['csrf_token'] = 'TestCase';

        $_POST['test_text'] = 'hello world!';
        $_POST['test_email'] = 'test@test.de';
        $_POST['test_number'] = '1000';
        $_POST['test_datetime'] = '24.01.2020';
        $_POST['test_regex'] = '1234567890';
        $_POST['test_validator'] = 'test_validator';
        $_POST['csrf_token'] = $_SESSION['csrf_token'];

        Asatru\Controller\CustomPostValidators::load(app_path('/validators'));
        $this->addToAssertionCount(1);

        $validator = Asatru\Controller\CustomPostValidators::findValidator('testvalidator');
        $this->assertTrue($validator !== null);

        $attribs = [
            'test_text' => 'required|min:3|max:50',
            'test_email' => 'required|email',
            'test_number' => 'required|number',
            'test_datetime' => 'required|datetime:d.m.Y',
            'test_regex' => 'required|regex:/^[0-9]*$/im',
            'test_validator' => 'required|testvalidator:test_validator'
        ];
        
        $pv = new Asatru\Controller\PostValidator($attribs);
        $this->assertTrue($pv->isValid());
    }

    public function testUrlMatches()
    {
        $url = '/test/var1/another/var2';
        $_GET['test1'] = 'hello';
        $_GET['test2'] = 'world';
        $method = self::getMethod('urlMatches');
        $obj = new Asatru\Controller\ControllerHandler(app_path('/config/routes.php'));
        $ctrl = new Asatru\Controller\ControllerArg($url);
        $result = $method->invokeArgs($obj, array($url, 'GET', '/test/{first}/another/{second}', $ctrl));
        $this->assertTrue($result);
        $this->assertEquals('var1', $ctrl->arg('first'));
        $this->assertEquals('var2', $ctrl->arg('second'));
        $this->assertEquals($_GET['test1'], $ctrl->params()->query('test1'));
        $this->assertEquals($_GET['test2'], $ctrl->params()->query('test2'));
    }
}