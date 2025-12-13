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
 * TestCase for helpers
 */
final class HelperTest extends TestCase
{
    public function testFlashMessenger()
    {
        $key = 'TestCase';
        $value = 'From helper test case';

        $result = FlashMessage::hasMsg($key);
        $this->assertFalse($result);

        FlashMessage::setMsg($key, $value);
        $this->assertTrue(isset($_SESSION[$key]));
        $this->assertInstanceOf('FlashMsgItem', $_SESSION[$key]);
        $this->assertEquals($value, $_SESSION[$key]->getMsg());

        $result = FlashMessage::hasMsg($key);
        $this->assertTrue($result);

        $result = FlashMessage::getMsg($key);
        $this->assertEquals($value, $result);

        FlashMessage::clearAll();
        $this->assertFalse(isset($_SESSION[$key]));
    }

    public function testBasePath()
    {
        $result = base_path();
        $this->assertTrue(is_dir($result));
    }

    public function testAppPath()
    {
        $result = app_path();
        $this->assertTrue(is_dir($result));
    }

    public function testPublicPath()
    {
        $result = public_path();
        $this->assertTrue(is_dir($result));
    }

    public function testBaseUrl()
    {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_ENV['APP_BASEDIR'] = '/testdir';

        $result = base_url();

        $this->assertEquals('http://localhost/testdir', $result);
    }

    public function testUrl()
    {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_ENV['APP_BASEDIR'] = '/testdir';

        $result = url('/path/to/resource');

        $this->assertEquals('http://localhost/testdir/path/to/resource', $result);
    }

    public function testAsset()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 8000;
        $_ENV['APP_BASEDIR'] = '/testdir';

        $result = asset('js/app.js');

        $this->assertEquals('https://localhost:8000/testdir/js/app.js', $result);
    }

    public function testCsrfToken()
    {
        $_SESSION['csrf_token'] = 'Hello World';

        $this->assertEquals($_SESSION['csrf_token'], csrf_token());
    }

    public function testTemplateCommand()
    {
        $result = template_command('sometest', function(string $code, array $args){ return '<?php echo "Just a test"; ?>';});
        $this->assertTrue($result);
    }

    public function testView()
    {
        $result = view('layout', array(array('yield', 'index')));
        $this->assertInstanceOf('Asatru\View\ViewHandler', $result);
    }
	
	public function testJson()
    {
        $result = json(array('name' => 'value'));
        $this->assertInstanceOf('Asatru\View\JsonHandler', $result);
    }
	
	public function testXml()
    {
        $result = xml(array('name' => 'value'));
        $this->assertInstanceOf('Asatru\View\XmlHandler', $result);
    }
	
	public function testCsv()
	{
		$result = csv(array(
			array('one', 'two', 'three', 'four', 'five')
		));
		$this->assertInstanceOf('Asatru\View\CsvHandler', $result);
	}
	
	public function testText()
	{
		$result = text('Hello World');
        $this->assertInstanceOf('Asatru\View\PlainHandler', $result);
	}
	
	public function testCustom()
    {
        $result = custom('text/plain', 'Hello World');
        $this->assertInstanceOf('Asatru\View\CustomHandler', $result);
    }
	
	public function testRedirect()
    {
        $result = redirect('/');
        $this->assertInstanceOf('Asatru\View\RedirectHandler', $result);
    }
	
	public function testBack()
    {
		$_SERVER['REQUEST_URI'] = '/';
        $result = back();
        $this->assertInstanceOf('Asatru\View\RedirectHandler', $result);
    }
	
	public function testEnv()
	{
		env_parse(__DIR__ . '/../.env');
		$this->assertTrue(env_has_error() === false);
		$this->assertTrue(env('APP_NAME') === 'Asatru PHP');
	}
}