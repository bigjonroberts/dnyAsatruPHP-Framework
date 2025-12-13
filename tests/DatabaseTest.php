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
 * TestCase for Asatru\Database
 */
final class DatabaseTest extends TestCase
{
    private $pdo = null;
    private $mdl = null;

    protected function setUp(): void
    {
        global $objPdo, $objMigrationLoader;

        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        if ($driver === 'pgsql') {
            $dsn = 'pgsql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'];
            if (isset($_ENV['DB_SCHEMA']) && strlen($_ENV['DB_SCHEMA']) > 0) {
                $dsn .= ';options=--search_path=' . $_ENV['DB_SCHEMA'];
            }
        } else {
            $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'];
        }

        $this->pdo = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);

        // Set global PDO for migrate_fresh() and other helpers
        $objPdo = $this->pdo;
        $objMigrationLoader = new Asatru\Database\MigrationLoader($this->pdo);

        $this->mdl = TestModel::getInstance();
        $this->mdl->__setHandle($this->pdo);
    }

    public static function tearDownAfterClass(): void
    {
        // Drop test table after all tests complete
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        if ($driver === 'pgsql') {
            $dsn = 'pgsql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'];
            if (isset($_ENV['DB_SCHEMA']) && strlen($_ENV['DB_SCHEMA']) > 0) {
                $dsn .= ';options=--search_path=' . $_ENV['DB_SCHEMA'];
            }
        } else {
            $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'];
        }

        $pdo = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
        $mig = new Asatru\Database\Migration('TestModel', $pdo);
        $mig->drop();
    }

    protected static function getMethod($name)
    {
        $class = new ReflectionClass('Asatru\\Database\\Collection');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testMigration()
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        $mig = new Asatru\Database\Migration('TestModel', $this->pdo);
        $this->addToAssertionCount(1);

        $mig->drop();
        $this->addToAssertionCount(1);

        if ($driver === 'pgsql') {
            $mig->add('id SERIAL PRIMARY KEY');
        } else {
            $mig->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        }
        $mig->add('text VARCHAR(260) NULL DEFAULT \'Test\'');
        if ($driver === 'pgsql') {
            $mig->add('data BYTEA NULL');
        } else {
            $mig->add('data BLOB NULL');
        }
        $mig->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $mig->create();
        $this->addToAssertionCount(4);

        $mig->append('test VARCHAR(255) NULL');
        $result = $this->mdl->raw('INSERT INTO @THIS (text, test) VALUES(\'text\', \'test\')');
        $this->assertTrue($result !== false);

        $result = TestModel::where('text', '=', 'text')->where('test', '=', 'test')->first();
        $this->assertEquals(1, $result->get('id'));
    }

    /**
     * @depends testMigration
     */
    public function testMigrateFresh()
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        migrate_fresh();
        $this->addToAssertionCount(1);

        $mig = new Asatru\Database\Migration('TestModel', $this->pdo);
        $this->addToAssertionCount(1);

        $mig->drop();
        $this->addToAssertionCount(1);

        if ($driver === 'pgsql') {
            $mig->add('id SERIAL PRIMARY KEY');
        } else {
            $mig->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        }
        $mig->add('text VARCHAR(260) NULL DEFAULT \'Test\'');
        if ($driver === 'pgsql') {
            $mig->add('data BYTEA NULL');
        } else {
            $mig->add('data BLOB NULL');
        }
        $mig->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $mig->create();
        $this->addToAssertionCount(4);
    }

    /**
     * @depends testMigrateFresh
     */
    public function testInsertEntries()
    {
        $result = TestModel::insert('text', 'text #1')->go();
        $this->assertTrue($result !== false);

        $result = TestModel::count()->get();
        $this->assertTrue($result === 1);

        $result = TestModel::insert('text', 'text #2')->go();
        $this->assertTrue($result !== false);

        $result = TestModel::count()->get();
        $this->assertTrue($result === 2);

        $result = TestModel::insert('text', 'text #3')->go();
        $this->assertTrue($result !== false);

        $result = TestModel::count()->get();
        $this->assertTrue($result === 3);

        $result = TestModel::insert('text', 'text same')->go();
        $this->assertTrue($result !== false);

        $result = TestModel::count()->get();
        $this->assertTrue($result === 4);

        $result = TestModel::insert('text', 'text same')->go();
        $this->assertTrue($result !== false);

        $result = TestModel::count()->get();
        $this->assertTrue($result === 5);
    }

    /**
     * @depends testInsertEntries
     */
    public function testUpdateEntries()
    {
        $result = TestModel::update('text', 'New text')->where('id', '=', 1)->go();
        $this->assertTrue($result !== false);

        $result = TestModel::where('id', '=', 1)->first();
        $this->assertTrue($result->get('text') === 'New text');
    }

    /**
     * @depends testUpdateEntries
     */
    public function testQueryEntries()
    {
        $result = TestModel::where('id', '<>', '3')->orderBy('id', 'desc')->get();
        $result->each(function($ident, $item) {
            $this->assertTrue($item->get('id') !== '3');
        });

        $result = TestModel::where('text', '=', 'text #1')->whereOr('text', '=', 'text #3')->get();
        $result->each(function($ident, $item) {
            $this->assertTrue($item->get('text') !== 'New text');
        });

        $result = TestModel::all();
        $this->assertTrue($result->count() === 5);

        $result = TestModel::find(1);
        $this->assertEquals(1, $result->get(0)->get('id'));

        $result = TestModel::aggregate('max', 'id')->get();
        $this->assertEquals(5, $result->get(0)->get('id'));

        $result = TestModel::whereBetween('id', 1, 3)->orderBy('id', 'desc')->get();
        $this->assertEquals(1, $result->get(2)->get('id'));
        $this->assertEquals(2, $result->get(1)->get('id'));
        $this->assertEquals(3, $result->get(0)->get('id'));

        $result = TestModel::where('id', '<>', 4)->whereOr('id', '<>', 5)->limit(2)->get();
        $this->assertEquals(2, $result->count());
        $result->each(function($ident, $item) {
            $this->assertNotEquals(4, $item->get('id'));
            $this->assertNotEquals(5, $item->get('id'));
        });

        $result = TestModel::whereBetween('id', 1, 2)->whereBetweenOr('id', 4, 5)->get();
        $this->assertEquals(4, $result->count());
        $result->each(function($ident, $item) {
            $this->assertNotEquals(3, $item->get('id'));
        });
    }

    /**
     * @depends testQueryEntries
     */
    public function testDeleteEntry()
    {
        $result = TestModel::where('id', '=', 1)->delete();
        $this->assertTrue($result);

        $result = TestModel::whereBetween('id', 2, 3)->whereBetweenOr('id', 3, 4)->delete();
        $this->assertTrue($result);
    }

    public function testCollection()
    {
        $arrCollectionData = array('test1' => 'first test', 'test2' => 'second test', 'test3' => array('one' => 'two', 'three' => 'four'));

        $coll = new Asatru\Database\Collection(array());
        $this->addToAssertionCount(1);
        
        $method = self::getMethod('createFromArray');
        $method->invokeArgs($coll, array($arrCollectionData));
        $this->addToAssertionCount(1);

        $this->assertEquals(3, $coll->count());
        
        $entry = $coll->get('test2');
        $this->assertEquals('second test', $entry);

        $coll->each(function($ident, $item, $data) {
            $this->assertTrue(array_key_exists($ident, $data['collection_data']));
        }, array('collection_data' => $arrCollectionData));

        $this->assertEquals('two', $coll->get('test3')->get('one'));
    }

    public function testCollectionCaseInsensitiveGet()
    {
        $coll = new Asatru\Database\Collection(['columnname' => 'value']);

        // Should find 'columnname' when asking for mixed case variants
        $this->assertEquals('value', $coll->get('ColumnName'));
        $this->assertEquals('value', $coll->get('COLUMNNAME'));
        $this->assertEquals('value', $coll->get('columnname'));
    }

    public function testCollectionCaseInsensitiveSet()
    {
        $coll = new Asatru\Database\Collection(['columnname' => 'old']);

        // Should update 'columnname' when setting 'ColumnName'
        $coll->set('ColumnName', 'new');
        $this->assertEquals('new', $coll->get('columnname'));
    }

    /**
     * @depends testInsertEntries
     */
    public function testDatabaseRoundTripCaseInsensitive()
    {
        // Insert a test record with known values
        $testText = 'case_insensitive_test';
        $result = TestModel::insert('text', $testText)->go();
        $this->assertTrue($result !== false);

        // Query the data back from the actual database
        $result = TestModel::where('text', '=', $testText)->first();
        $this->assertNotNull($result);

        // Test case-insensitive access on column names
        // All these should return the same value regardless of case
        $this->assertEquals($testText, $result->get('text'));
        $this->assertEquals($testText, $result->get('Text'));
        $this->assertEquals($testText, $result->get('TEXT'));

        // Test accessing other columns with different cases
        $this->assertNotNull($result->get('id'));
        $this->assertNotNull($result->get('ID'));
        $this->assertNotNull($result->get('Id'));

        // Test accessing created_at with different cases
        $this->assertNotNull($result->get('created_at'));
        $this->assertNotNull($result->get('Created_At'));
        $this->assertNotNull($result->get('CREATED_AT'));

        // Clean up - delete the test record
        TestModel::where('text', '=', $testText)->delete();
    }

    /**
     * @depends testInsertEntries
     */
    public function testBinaryDataStorage()
    {
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD";

        TestModel::insert('data', $binaryData)->go();
        $result = TestModel::orderBy('id', 'desc')->first();

        $this->assertEquals($binaryData, $result->get('data'));
    }

    /**
     * @depends testInsertEntries
     */
    public function testSerializedObjectStorage()
    {
        $object = (object)['key' => 'value', 'num' => 123];
        $serialized = serialize($object);

        TestModel::insert('data', $serialized)->go();
        $result = TestModel::orderBy('id', 'desc')->first();

        $unserialized = unserialize($result->get('data'));
        $this->assertEquals($object->key, $unserialized->key);
        $this->assertEquals($object->num, $unserialized->num);
    }

    /**
     * @depends testInsertEntries
     */
    public function testUnicodeDataStorage()
    {
        $unicode = '日本語 Русский العربية';

        TestModel::insert('text', $unicode)->go();
        $result = TestModel::orderBy('id', 'desc')->first();

        $this->assertEquals($unicode, $result->get('text'));
    }

    /**
     * @depends testInsertEntries
     */
    public function testSpecialCharactersStorage()
    {
        $special = "Line1\nLine2\rLine3\tTabbed";

        TestModel::insert('text', $special)->go();
        $result = TestModel::orderBy('id', 'desc')->first();

        $this->assertEquals($special, $result->get('text'));
    }

    /**
     * @depends testInsertEntries
     */
    public function testJsonDataStorage()
    {
        $data = ['name' => 'Test', 'count' => 42, 'active' => true, 'tags' => ['one', 'two']];
        $json = json_encode($data);

        TestModel::insert('text', $json)->go();
        $result = TestModel::orderBy('id', 'desc')->first();

        $decoded = json_decode($result->get('text'), true);
        $this->assertEquals($data, $decoded);
    }

    /**
     * Test that columns WITHOUT auto_increment flag don't get AUTO_INCREMENT appended
     * This tests the fix for the AUTO_INCREMENT bug
     */
    public function testColumnWithoutAutoIncrement()
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        $mig = new Asatru\Database\Migration('TestNonAutoInc', $this->pdo);

        // Create a table with columns that do NOT have auto_increment
        if ($driver === 'pgsql') {
            $mig->add('id SERIAL PRIMARY KEY');
        } else {
            $mig->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        }

        // This VARCHAR column should NOT get AUTO_INCREMENT appended
        $mig->column('name', 'VARCHAR', 100)->nullable(false)->default('test')->commit();
        $mig->column('status', 'VARCHAR', 50)->nullable(true)->commit();

        // If AUTO_INCREMENT was incorrectly added to VARCHAR columns, this will throw an exception
        $mig->create();
        $this->addToAssertionCount(1);

        // Verify we can insert data
        $this->pdo->exec("INSERT INTO TestNonAutoInc (name, status) VALUES ('Alice', 'active')");
        $result = $this->pdo->query("SELECT * FROM TestNonAutoInc WHERE name = 'Alice'")->fetch();
        $this->assertEquals('Alice', $result['name']);

        // Cleanup
        $mig->drop();
    }

    /**
     * Test column comments (which use postCreateStatements)
     * This tests the fix for postCreateStatements backtick conversion
     */
    public function testColumnComments()
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        $mig = new Asatru\Database\Migration('TestComments', $this->pdo);

        if ($driver === 'pgsql') {
            $mig->add('id SERIAL PRIMARY KEY');
        } else {
            $mig->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        }

        // Add a column with a comment
        $mig->column('description', 'VARCHAR', 200)
            ->nullable(true)
            ->comment('User description field')
            ->commit();

        // Create table (PostgreSQL will use postCreateStatements for COMMENT)
        $mig->create();
        $this->addToAssertionCount(1);

        // Verify we can use the table
        $this->pdo->exec("INSERT INTO TestComments (description) VALUES ('Test comment')");
        $result = $this->pdo->query("SELECT * FROM TestComments")->fetch();
        $this->assertEquals('Test comment', $result['description']);

        // Cleanup
        $mig->drop();
    }

    /**
     * Test the append() method
     * This tests the fix for append() backtick conversion
     */
    public function testAppendColumn()
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        $mig = new Asatru\Database\Migration('TestAppend', $this->pdo);

        // Create initial table
        if ($driver === 'pgsql') {
            $mig->add('id SERIAL PRIMARY KEY');
        } else {
            $mig->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        }
        $mig->add('name VARCHAR(100) NOT NULL');
        $mig->create();

        // Use append() to add a column after table creation
        $mig->append('email VARCHAR(255) NULL');
        $this->addToAssertionCount(1);

        // Verify the new column exists
        $this->pdo->exec("INSERT INTO TestAppend (name, email) VALUES ('Bob', 'bob@example.com')");
        $result = $this->pdo->query("SELECT * FROM TestAppend WHERE name = 'Bob'")->fetch();
        $this->assertEquals('bob@example.com', $result['email']);

        // Cleanup
        $mig->drop();
    }

    /**
     * Test column builder with various modifiers
     * This tests that all column modifiers work correctly for both databases
     */
    public function testColumnBuilder()
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        $mig = new Asatru\Database\Migration('TestBuilder', $this->pdo);

        // Test various column configurations
        if ($driver === 'pgsql') {
            $mig->column('id', 'SERIAL')->primary_key()->commit();
        } else {
            $mig->column('id', 'INT')->auto_increment()->primary_key()->commit();
        }

        $mig->column('name', 'VARCHAR', 100)
            ->nullable(false)
            ->default('Unknown')
            ->commit();

        $mig->column('age', 'INT')
            ->nullable(true)
            ->unsigned(true)
            ->commit();

        $mig->column('bio', 'TEXT')
            ->nullable(true)
            ->collation($driver === 'pgsql' ? 'C' : 'utf8mb4_unicode_ci')
            ->commit();

        // Create table
        $mig->create();
        $this->addToAssertionCount(1);

        // Test inserting data
        $this->pdo->exec("INSERT INTO TestBuilder (name, age) VALUES ('Charlie', 25)");
        $result = $this->pdo->query("SELECT * FROM TestBuilder")->fetch();
        $this->assertEquals('Charlie', $result['name']);
        $this->assertEquals(25, $result['age']);

        // Cleanup
        $mig->drop();
    }
}