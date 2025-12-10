<?php

/*
    Asatru PHP (dnyAsatruPHP) developed by Daniel Brendel
    
    (C) 2019 - 2025 by Daniel Brendel
    
    Contact: dbrendel1988<at>gmail<dot>com
    GitHub: https://github.com/danielbrendel/
    
    Released under the MIT license
*/

namespace Asatru\Console {
    /**
     * Generate PostgreSQL trigger code for updated_at column
     *
     * @param string $tableName The table name
     * @return array Array with 'up' code and 'down' code
     */
    function generatePostgresUpdatedAtTrigger($tableName)
    {
        $funcName = 'update_' . strtolower($tableName) . '_updated_at_column';
        $triggerName = 'update_' . strtolower($tableName) . '_updated_at';

        $upCode = "
        // Create PostgreSQL trigger function for updated_at
        \$this->connection->exec('
            CREATE OR REPLACE FUNCTION {$funcName}()
            RETURNS TRIGGER AS \$trigger\$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            \$trigger\$ LANGUAGE plpgsql
        ');

        // Create trigger
        \$this->connection->exec('
            DROP TRIGGER IF EXISTS {$triggerName} ON \"{$tableName}\";
            CREATE TRIGGER {$triggerName}
            BEFORE UPDATE ON \"{$tableName}\"
            FOR EACH ROW
            EXECUTE FUNCTION {$funcName}()
        ');";

        $downCode = "
            // Drop PostgreSQL trigger and function
            \$this->connection->exec('DROP TRIGGER IF EXISTS {$triggerName} ON \"{$tableName}\"');
            \$this->connection->exec('DROP FUNCTION IF EXISTS {$funcName}()');";

        return ['up' => $upCode, 'down' => $downCode];
    }

    /**
     * Create a model with the associated migration
     *
     * @param string $name The name of the model and migration
     * @return boolean
     */
    function createModel($name)
    {
        $driver = isset($_ENV['DB_DRIVER']) ? $_ENV['DB_DRIVER'] : 'mysql';

        // Generate driver-specific SQL
        if ($driver === 'pgsql') {
            $idColumn = 'id SERIAL PRIMARY KEY';
            $updatedAtColumn = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $createdAtColumn = 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $triggerCode = generatePostgresUpdatedAtTrigger(ucfirst($name));
            $upTrigger = $triggerCode['up'];
            $downTrigger = $triggerCode['down'];
        } else {
            $idColumn = 'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY';
            $updatedAtColumn = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            $createdAtColumn = 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $upTrigger = '';
            $downTrigger = '';
        }

        $content1 = "<?php

    /*
        Asatru PHP - Migration for " . $name . "
    */

    /**
     * This class specifies a migration
     */
    class " . ucfirst($name) . "_Migration {
        private \$database = null;
        private \$connection = null;

        /**
         * Store the PDO connection handle
         *
         * @param \\PDO \$pdo The PDO connection handle
         * @return void
         */
        public function __construct(\$pdo)
        {
            \$this->connection = \$pdo;
        }

        /**
         * Called when the table shall be created or modified
         *
         * @return void
         */
        public function up()
        {
            \$this->database = new Asatru\Database\Migration('" . ucfirst($name) . "', \$this->connection);
            \$this->database->drop();
            \$this->database->add('" . $idColumn . "');
            \$this->database->add('" . $updatedAtColumn . "');
            \$this->database->add('" . $createdAtColumn . "');
            \$this->database->create();
" . $upTrigger . "
        }

        /**
         * Called when the table shall be dropped
         *
         * @return void
         */
        public function down()
        {
" . $downTrigger . "
            if (\$this->database)
                \$this->database->drop();
        }
    }";

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/migrations/' . $name . '.php', $content1)) {
            return false;
        }

        $content2 = "<?php

    /*
        Asatru PHP - Model
    */

    /**
     * This class extends the base model class and represents your associated table
     */ 
    class " . $name . " extends \Asatru\Database\Model {
        //
    }";

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/models/' . $name . '.php', $content2)) {
            return false;
        }

        return true;
    }

    /**
     * Create a module
     * 
     * @param string $name The name of the module
     * @param array $args Optional arguments
     * @return boolean
     */
    function createModule($name, $args = [])
    {
        $final = '';
        $base = '';
        $extends = '';

        foreach ($args as $key => $value) {
            if ($value === '--base') {
                $base = 'Base';
            } else if ($value === '--final') {
                $final = 'final ';
            } else if ($value === '--extends') {
                $from_base = 'Base' . ucfirst($name);

                if ((isset($args[$key + 1])) && (strpos($args[$key + 1], '--') === false)) {
                    $from_base = $args[$key + 1];
                }

                $extends = ' extends ' . $from_base;
            }
        }

        $content = "<?php

    /*
        Asatru PHP - Module
    */

    /**
     * This class represents your module
     */
    " . $final . "class " . $base . ucfirst($name) . $extends . " {
        //
    }
    ";

        return file_put_contents(ASATRU_APP_ROOT . '/app/modules/' . $base . $name . '.php', $content) !== false;
    }

    /**
     * Create a controller
     * 
     * @param string $name The name of the controller
     * @return boolean
     */
    function createController($name)
    {
        $content = "<?php

    /*
        Asatru PHP - " . ucfirst($name) . " Controller
    */

    /**
     * This class represents your controller
     */
    class " . ucfirst($name) . "Controller extends BaseController {
        //
    }
    ";

        return file_put_contents(ASATRU_APP_ROOT . '/app/controller/' . $name . '.php', $content) !== false;
    }

    /**
     * Create language structure for ident
     * 
     * @param string $ident The identifier of the locale
     */
    function createLang($ident)
    {
        if (is_dir(ASATRU_APP_ROOT . '/app/lang/' . $ident)) {
            return false;
        }

        mkdir(ASATRU_APP_ROOT . '/app/lang/' . $ident);

        $content = "<?php

    /*
        Asatru PHP - Language file for " . $ident . "
    */

    return [
        //
    ];";
        
        if (!file_put_contents(ASATRU_APP_ROOT . '/app/lang/' . $ident . '/app.php', $content)) {
            return false;
        }

        return true;
    }

    /**
     * Create a new custom validator
     * 
     * @param string $name The name of the validator
     * @param string $ident The identifier to be used in the validation call
     * @return boolean
     */
    function createValidator($name, $ident)
    {
        if (!is_dir(ASATRU_APP_ROOT . '/app/validators')) {
            mkdir(ASATRU_APP_ROOT . '/app/validators');
        }

        $content = "<?php

    /*
        Asatru PHP - Validator for validation ident " . $ident . "
    */

    /**
     * This class implements your validator
     */
    class " . ucfirst($name) . "Validator extends Asatru\Controller\BaseValidator {
        protected \$error;

        /**
         * Return the identifier of the validator
         * 
         * @return string
         */
        public function getIdent()
        {
            return '" . $ident . "';
        }

        /**
         * Validate the actual input data
         * 
         * @param mixed \$value The input value
         * @param mixed \$args optional Arguments for the validator
         * @return boolean True if valid, otherwise false
         */
        public function verify(\$value, \$args = null)
        {
            return true;
        }

        /**
         * Return error description of the validation process if the data is invalid
         * 
         * @return string
         */
        public function getError()
        {
            return \$this->error;
        }
    }
        ";

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/validators/' . strtolower($name) . '.php', $content)) {
            return false;
        }

        return true;
    }

    /**
     * Create a new event handler
     * 
     * @param string $name The name of the event handler
     * @param string $initial_handler An initial handler to be provided
     * @return boolean
     */
    function createEvent($name, $initial_handler)
    {
        if (!is_dir(ASATRU_APP_ROOT . '/app/events')) {
            mkdir(ASATRU_APP_ROOT . '/app/events');
        }

        $content = "<?php

    /*
        Asatru PHP - Event handler
    */

    /**
     * Event handler class
     */
    class $name {
        /**
         * An initial event handler method
         * 
         * @param \$data optional
         * @return void
         */
        public function $initial_handler(\$data = null)
        {
        }
    }
        ";

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/events/' . strtolower($name) . '.php', $content)) {
            return false;
        }

        return true;
    }

    /**
     * Create a new command class
     * 
     * @param string $name The class name
     * @return boolean
     */
    function createCommand($name)
    {
        if (!is_dir(ASATRU_APP_ROOT . '/app/commands')) {
            mkdir(ASATRU_APP_ROOT . '/app/commands');
        }

        $content = "<?php

    /*
        Asatru PHP - Command handler
    */

    /**
     * Command handler class
     */
    class $name implements Asatru\Commands\Command {
        /**
         * Command handler method
         * 
         * @param \$args
         * @return void
         */
        public function handle(\$args)
        {
        }
    }
        ";

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/commands/' . $name . '.php', $content)) {
            return false;
        }

        return true;
    }

    /**
     * Create authentication models and migrations
     * 
     * @return boolean
     */
    function createAuth()
    {
        $driver = isset($_ENV['DB_DRIVER']) ? $_ENV['DB_DRIVER'] : 'mysql';

        // Generate driver-specific SQL for Session table
        if ($driver === 'pgsql') {
            $sessionIdCol = 'id SERIAL PRIMARY KEY';
            $sessionUpdatedAt = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $sessionTrigger = generatePostgresUpdatedAtTrigger('Session');
            $sessionUpTrigger = $sessionTrigger['up'];
            $sessionDownTrigger = $sessionTrigger['down'];
        } else {
            $sessionIdCol = 'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY';
            $sessionUpdatedAt = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            $sessionUpTrigger = '';
            $sessionDownTrigger = '';
        }

        // Generate driver-specific SQL for Auth table
        if ($driver === 'pgsql') {
            $authIdCol = 'id SERIAL PRIMARY KEY';
            $authUpdatedAt = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $authTrigger = generatePostgresUpdatedAtTrigger('Auth');
            $authUpTrigger = $authTrigger['up'];
            $authDownTrigger = $authTrigger['down'];
        } else {
            $authIdCol = 'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY';
            $authUpdatedAt = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            $authUpTrigger = '';
            $authDownTrigger = '';
        }

        $contentmig1 = "<?php

    /*
        Asatru PHP - Migration for Session
    */

    /**
     * Session migration
     */
    class Session_Migration {
        private \$database = null;
        private \$connection = null;

        /**
         * Store the PDO connection handle
         *
         * @param \PDO \$pdo The PDO connection handle
         * @return void
         */
        public function __construct(\$pdo)
        {
            \$this->connection = \$pdo;
        }

        /**
         * Called when the table shall be created or modified
         *
         * @return void
         */
        public function up()
        {
            \$this->database = new Asatru\Database\Migration('Session', \$this->connection);
            \$this->database->drop();
            \$this->database->add('" . $sessionIdCol . "');
            \$this->database->add('userId INT NOT NULL');
            \$this->database->add('session VARCHAR(512) NOT NULL');
            \$this->database->add('status BOOLEAN NOT NULL DEFAULT 0');
            \$this->database->add('" . $sessionUpdatedAt . "');
            \$this->database->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
            \$this->database->create();
" . $sessionUpTrigger . "
        }

        /**
         * Called when the table shall be dropped
         *
         * @return void
         */
        public function down()
        {
" . $sessionDownTrigger . "
            if (\$this->database)
                \$this->database->drop();
        }
    }";

        $contentmig2 = "<?php

    /*
        Asatru PHP - Migration for Auth
    */

    /**
     * Authentication migration
     */
    class Auth_Migration {
        private \$database = null;
        private \$connection = null;

        /**
         * Set PDO connection handle
         *
         * @param \PDO \$pdo The PDO connection handle
         * @return void
         */
        public function __construct(\$pdo)
        {
            \$this->connection = \$pdo;
        }

        /**
         * Create the authentication database table
         *
         * @return void
         */
        public function up()
        {
            \$this->database = new Asatru\Database\Migration('Auth', \$this->connection);
            \$this->database->drop();
            \$this->database->add('" . $authIdCol . "');
            \$this->database->add('email VARCHAR(512) NOT NULL');
            \$this->database->add('username VARCHAR(512) NOT NULL');
            \$this->database->add('password VARCHAR(512) NOT NULL');
            \$this->database->add('account_confirm VARCHAR(512) NOT NULL');
            \$this->database->add('" . $authUpdatedAt . "');
            \$this->database->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
            \$this->database->create();
" . $authUpTrigger . "
        }

        /**
         * Drop the authentication database table
         *
         * @return void
         */
        public function down()
        {
" . $authDownTrigger . "
            if (\$this->database)
                \$this->database->drop();
        }
    }
        ";

        $contentmdl1 = "<?php

    /*
        Asatru PHP - Model for Session

        Default session model
    */

    /**
     * Model representing the session table
     */ 
    class Session extends \Asatru\Database\Model {
        /**
         * Create an active session of that given user with the given session
         * 
         * @param \$userId
         * @param \$session
         * @return void
         * @throws \Exception
         */
        public static function loginSession(\$userId, \$session)
        {
            try {
                if (!Session::hasSession(\$userId, \$session)) {
                    Session::insert('userId', \$userId)->insert('session', \$session)->insert('status', 1)->go();
                }
            } catch (\Exception \$e) {
                throw \$e;
            }
        }

        /**
         * Ends the given session if active
         * 
         * @param \$session
         * @return void
         * @throws \Exception
         */
        public static function logoutSession(\$session)
        {
            try {
                Session::where('session', '=', \$session)->where('status', '=', 1)->delete();
            } catch (\Exception \$e) {
                throw \$e;
            }
        }

        /**
         * Returns the data object of the given session if exists
         * 
         * @param \$session
         * @return mixed
         * @throws \Exception
         */
        public static function findSession(\$session)
        {
            try {
                return Session::where('session', '=', \$session)->where('status', '=', 1)->first();
            } catch (\Exception \$e) {
                throw \$e;
            }
        }

        /**
         * Checks if a session of that given user exists
         * 
         * @param \$userId
         * @param \$session
         * @return bool
         * @throws \Exception
         */
        public static function hasSession(\$userId, \$session)
        {
            try {
                return Session::where('userId', '=', \$userId)->where('session', '=', \$session)->get()->count();
            } catch (\Exception \$e) {
                throw \$e;
            }
        }

        /**
         * Clears all sessions of that given user
         * 
         * @param \$userId
         * @return void
         * @throws \Exception
         */
        public static function clearForUser(\$userId)
        {
            try {
                Session::where('userId', '=', \$userId)->delete();
            } catch (\Exception \$e) {
                throw \$e;
            }
        }
    }";

        $contentmdl2 = "<?php
        
    /*
        Asatru PHP - Model for Auth

        Default authentication model
    */

    /**
     * Model representing the authentication table
     */
    class Auth extends \Asatru\Database\Model {
        /**
         * Add a new user registration
         * 
         * @param string \$username The name of the user
         * @param string \$email The user E-Mail address
         * @param string \$password The password given by the user
         * @return boolean
         */
        public static function register(string \$username, string \$email, string \$password)
        {
            if ((\$username === '') || (\$password === '') || (filter_var(\$email, FILTER_VALIDATE_EMAIL) === false))
                return false;

            \$byemail = Auth::getByEmail(\$email);
            if ((\$byemail) && (\$byemail->count() > 0))
                return false;

            \$user_password = password_hash(\$password, PASSWORD_DEFAULT);
            \$account_confirm = md5(\$username . \$email . date('Y-m-d H:i:s') . random_bytes(55));

            try {
                Auth::insert('username', \$username)->insert('email', \$email)->insert('password', \$user_password)->insert('account_confirm', \$account_confirm)->go();
            } catch (\Exception \$e) {
                return false;
            }

            //To-do: Send a confirmation e-mail with the account confirmation token in order to verify the e-mail address

            return true;
        }

        /**
         * Confirm user account
         * 
         * @param string \$token Account token that was generated upon registration
         * @return bool
         */
        public static function confirm(\$token)
        {
            \$user = Auth::where('account_confirm', '=', \$token)->first();
            if (!\$user)
                return false;

            try {
                Auth::update('account_confirm', '_confirmed')->where('id', '=', \$user->get('id'))->go();
            } catch (\Exception \$e) {
                return false;
            }

            return true;
        }

        /**
         * Log the user in
         * 
         * @param string \$email The E-Mail address of the user
         * @param string \$password The user password
         * @return boolean
         */
        public static function login(string \$email, string \$password)
        {
            \$byemail = Auth::getByEmail(\$email);
            
            if (\$byemail->count() === 0)
                return false;

            if (\$byemail->get('account_confirm') !== '_confirmed')
                return false;

            if (!password_verify(\$password, \$byemail->get('password')))
                return false;

            try {
                Session::loginSession(\$byemail->get('id'), session_id());
            } catch (\Exception \$e) {
                return false;
            }

            return true;
        }

        /**
         * Log the user out
         * 
         * @return boolean
         */
        public static function logout()
        {
            try {
                Session::logoutSession(session_id());
            } catch (\Exception \$e) {
                return false;
            }

            return true;
        }

        /**
         * Get current authenticated user of this session if exists
         * 
         * @return Asatru\Database\Collection|null User data collection on success, otherwise null
         */
        public static function getAuthUser()
        {
            try {
                \$session = Session::findSession(session_id());
                if (!\$session) {
                    return null;
                }

                \$data = Auth::where('id', '=', \$session->get('userId'))->first();
                if (!\$data) {
                    return null;
                }

                return \$data;
            } catch (\Exception \$e) {
                return null;
            }
        }

        /**
         * Get user by email
         * 
         * @param string \$email The users E-Mail address
         * @return Asatru\Database\Collection|null User data collection on success, otherwise null
         */
        public static function getByEmail(string \$email)
        {
            try {
                \$result = Auth::where('email', '=', \$email)->first();
            } catch (\Exception \$e) {
                return null;
            }

            return \$result;
        }

        /**
         * Get user by ID
         * 
         * @param int \$userId The user ID
         * @return Asatru\Database\Collection|null User data collection on success, otherwise null
         */
        public static function getById(int \$userId)
        {
            try {
                \$result = Auth::where('id', '=', \$userId)->first();
            } catch (\Exception \$e) {
                return null;
            }

            return \$result;
        }
    }
        ";

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/migrations/Session.php', $contentmig1)) {
            return false;
        }

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/migrations/Auth.php', $contentmig2)) {
            return false;
        }

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/models/Session.php', $contentmdl1)) {
            return false;
        }

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/models/Auth.php', $contentmdl2)) {
            return false;
        }

        return true;
    }

    /**
     * Create caching model and migration
     * 
     * @return boolean
     */
    function createCache()
    {
        $driver = isset($_ENV['DB_DRIVER']) ? $_ENV['DB_DRIVER'] : 'mysql';

        // Generate driver-specific SQL
        if ($driver === 'pgsql') {
            $cacheIdCol = 'id SERIAL PRIMARY KEY';
            $cacheValueCol = 'value BYTEA NULL';  // PostgreSQL uses BYTEA instead of BLOB
        } else {
            $cacheIdCol = 'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY';
            $cacheValueCol = 'value BLOB NULL';
        }

        $content1 = "<?php

    /*
        Asatru PHP - Migration vor Caching
    */

    class Cache_Migration {
        private \$database = null;
        private \$connection = null;

        /**
         * Construct class and store PDO connection handle
         *
         * @param \PDO \$pdo
         * @return void
         */
        public function __construct(\$pdo)
        {
            \$this->connection = \$pdo;
        }

        /**
         * Called when the table shall be created or modified
         *
         * @return void
         */
        public function up()
        {
            \$this->database = new Asatru\Database\Migration('Cache', \$this->connection);
            \$this->database->drop();
            \$this->database->add('" . $cacheIdCol . "');
            \$this->database->add('ident VARCHAR(260) NOT NULL');
            \$this->database->add('" . $cacheValueCol . "');
            \$this->database->add('updated_at TIMESTAMP');
            \$this->database->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
            \$this->database->create();
        }

        /**
         * Called when the table shall be dropped
         *
         * @return void
         */
        public function down()
        {
            \$this->database = new Asatru\Database\Migration('Cache', \$this->connection);
            \$this->database->drop();
        }
    }";

        $content2 = "<?php

    /*
        Asatru PHP - Model for Caching
    */

    class Cache extends \Asatru\Database\Model {
        /**
         * Obtain value either from cache or from closure
         *	
            *	@param string \$ident The cache item identifier
            *	@param int \$timeInSeconds Amount of seconds the item shall be cached
            *	@param \$closure Function to be called for the actual value
            *	@return mixed
            */
        public static function remember(\$ident, \$timeInSeconds, \$closure)
        {
            \$item = Cache::find(\$ident, 'ident');
            if (\$item->count() == 0) {
                \$value = \$closure();
                
                \$data = array(
                    'ident' => \$ident,
                    'value' => \$value,
                    'updated_at' => date('Y-m-d H:i:s')
                );
                
                foreach (\$data as \$key => \$val) {
                    Cache::insert(\$key, \$val);
                }
                
                Cache::go();
                
                return \$value;
            } else {
                \$data = \$item->get(0);
                \$dtLast = new DateTime(date('Y-m-d H:i:s', strtotime(\$data->get('updated_at'))));
                \$dtLast->add(new DateInterval('PT' . \$timeInSeconds . 'S'));
                \$dtNow = new DateTime('now');

                if (\$dtNow < \$dtLast) {
                    return \$data->get('value');
                } else {
                    \$value = \$closure();
                    
                    \$updData = array(
                        'value' => \$value,
                        'updated_at' => date('Y-m-d H:i:s')
                    );
                    
                    foreach (\$updData as \$key => \$val) {
                        Cache::update(\$key, \$val);
                    }

                    Cache::where('id', '=', \$data->get('id'));
                    
                    Cache::go();
                    
                    return \$value;
                }
            }
            
            return null;
        }

        /**
         * Query an entire item
         * 
         * @param \$ident
         * @param \$default
         * @return mixed
         */
        public static function query(\$ident, \$default = null)
        {
            \$what = null;
            if (strpos(\$ident, '.') !== false) {
                \$what = explode('.', \$ident);
            }

            \$result = Cache::where('ident', '=', (((is_array(\$what)) && (count(\$what) > 0)) ? \$what[0] : \$ident))->first();
            if (!\$result) {
                return \$default;
            }

            if ((is_array(\$what)) && (count(\$what) > 1)) {
                return \$result->get(\$what[1]);
            }

            return \$result->get('value');
        }
        
        /**
         * Check for item existence
         *
         *	@param \$ident
            *  @return bool
            */
        public static function has(\$ident)
        {
            \$item = Cache::find(\$ident, 'ident');
            if (\$item->count() > 0) {
                return true;
            }
            
            return false;
        }

        /**
         * Check if item cache time is elapsed
         * 
         * @param \$ident
         * @param \$timeInSeconds
         * @return bool
         */
        public static function elapsed(\$ident, \$timeInSeconds)
        {
            if (!Cache::has(\$ident)) {
                return false;
            }

            \$data = Cache::where('ident', '=', \$ident)->first();

            \$dtLast = new DateTime(date('Y-m-d H:i:s', strtotime(\$data->get('updated_at'))));
            \$dtLast->add(new DateInterval('PT' . \$timeInSeconds . 'S'));
            \$dtNow = new DateTime('now');

            return (\$dtNow >= \$dtLast);
        }
        
        /**
         * Get item and then delete it
         *
         *	@param \$ident
            *  @return mixed
            */
        public static function pull(\$ident)
        {
            \$item = Cache::find(\$ident, 'ident');
            if (\$item->count() > 0) {
                \$data = \$item->get(0);
                
                Cache::where('id', '=', \$item->get(0)->get('id'))->delete();
                
                return \$data->get('value');
            }
            
            return null;
        }

        /**
         * Write item to table
         * 
         * @param \$ident
         * @param \$value
         * @return bool
         */
        public static function put(\$ident, \$value)
        {
            if (Cache::has(\$ident)) {
                return false;
            }

            \$data = array(
                'ident' => \$ident,
                'value' => \$value,
                'updated_at' => date('Y-m-d H:i:s')
            );
            
            foreach (\$data as \$key => \$val) {
                Cache::insert(\$key, \$val);
            }

            Cache::go();

            return true;
        }
        
        /**
         * Forget cache item
         * 
         * @param string \$ident The item identifier
         * @return bool
         */
        public static function forget(\$ident)
        {
            \$item = Cache::find(\$ident, 'ident');
            if (\$item->count() > 0) {
                Cache::where('id', '=', \$item->get(0)->get('id'))->delete();
                
                return true;
            }
            
            return false;
        }
    }
        ";

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/migrations/Cache.php', $content1)) {
            return false;
        }

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/models/Cache.php', $content2)) {
            return false;
        }

        return true;
    }

    /**
     * Create a new test
     * 
     * @param string $name The name of the test case
     * @return boolean
     */
    function createTest($name)
    {
        if (!is_dir(ASATRU_APP_ROOT . '/app/tests')) {
            mkdir(ASATRU_APP_ROOT . '/app/tests');
        }

        if (!file_exists(ASATRU_APP_ROOT . '/app/tests/bootstrap.php')) {
            $bootstrap = "<?php

    /*
        Asatru PHP (dnyAsatruPHP) developed by Daniel Brendel
        
        (C) 2019 - 2025 by Daniel Brendel
        
        Contact: dbrendel1988<at>gmail<dot>com
        GitHub: https://github.com/danielbrendel/
        
        Released under the MIT license
    */

    //If composer is installed we utilize its autoloader
    if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        require_once __DIR__ . '/../../vendor/autoload.php';
    }

    //Set application root directory path
    define('ASATRU_APP_ROOT', __DIR__ . '/../..');

    //Fetch constants
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/constants.php';

    //Require the controller component
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/controller.php';

    //Require logging
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/logger.php';

    //Require .env config management
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/dotenv.php';

    //Require autoload component
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/autoload.php';

    //Require config component
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/config.php';

    //Require helpers
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/helper.php';

    //Require Html helper
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/html.php';

    //Require form helper
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/forms.php';

    //Require mail wrapper
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/mailwrapper.php';

    //Require testing component
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/testing.php';

    //Parse .env file if it exists
    if (file_exists(__DIR__ . '/../../.env.testing')) {
        env_parse(__DIR__ . '/../../.env.testing');
    }

    //Enable debug mode error handling
    \$_ENV['APP_DEBUG'] = true;
    error_reporting(E_ALL);

    //Check if we shall create/continue a session
    if ((isset(\$_ENV['SESSION_ENABLE'])) && (\$_ENV['SESSION_ENABLE'])) {
        if ((isset(\$_ENV['SESSION_DURATION'])) && (\$_ENV['SESSION_DURATION'] !== null) && (is_numeric(\$_ENV['SESSION_DURATION']))) {
            ini_set('session.cookie_lifetime', \$_ENV['SESSION_DURATION']);
            ini_set('session.gc_maxlifetime', \$_ENV['SESSION_DURATION']);
        }

        if ((isset(\$_ENV['SESSION_NAME'])) && (is_string(\$_ENV['SESSION_NAME'])) && (strlen(\$_ENV['SESSION_NAME']) > 0)) {
            session_name(\$_ENV['SESSION_NAME']);
        }

        if (!session_start()) {
            throw new Exception('Failed to create/continue the session');
        }

        if (!isset(\$_SESSION['continued'])) { //Check if a new session
            //Create CSRF-token
            \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            //Mark session
            \$_SESSION['continued'] = true;
        }
    }

    //Require localization
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/locale.php';

    //Require database management
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/database.php';

    //Require modules
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/modules.php';

    //Require event manager
    require_once __DIR__ . '/../../vendor/danielbrendel/asatru-php-framework/src/events.php';

    //Perform autoloading
    \$auto = new Asatru\Autoload\Autoloader(__DIR__ . '/../config/autoload.php');
    \$auto->load();

    //Load validators if any
    Asatru\Controller\CustomPostValidators::load(__DIR__ . '/../validators');
            ";

            if (!file_put_contents(ASATRU_APP_ROOT . '/app/tests/bootstrap.php', $bootstrap)) {
                return false;
            }
        }

        $content = "<?php

    /*
        Testcase for Test " . ucfirst($name) . "
    */

    use PHPUnit\Framework\TestCase;

    /**
     * This class holds your test methods
     */
    class " . ucfirst($name) . "Test extends Asatru\Testing\Test
    {
        //
    }
        ";

        if (!file_put_contents(ASATRU_APP_ROOT . '/app/tests/' . ucfirst($name) . 'Test.php', $content)) {
            return false;
        }

        return true;
    }

    /**
     * List all registered routes
     * 
     * @return void
     */
    function list_registered_routes()
    {
        $routes_config_file = '/app/config/routes.php';

        $default_color = "\033[39m";
        $method_colors = [
            'GET' => "\033[0;34m",
            'POST' => "\033[0;33m",
            'PATCH' => "\033[0;33m",
            'PUT' => "\033[0;33m",
            'DELETE' => "\033[0;31m",
            'HEAD' => "\033[0;36m",
            'OPTIONS' => "\033[0;36m",
            'ANY' => "\033[0;35m"
        ];

        $routes = require(ASATRU_APP_ROOT . $routes_config_file);
        foreach ($routes as $route) {
            $path = $route[0];
            $method = $route[1];
            $action = $route[2];
            $name = (isset($route[3]) ? " (\033[3m" . $route[3] . "\033[0m)" : '');

            $color = (isset($method_colors[$method]) ? $method_colors[$method] : $default_color);

            echo "[" . $color . strtoupper($method) . $default_color . "] " . $path . " -> \033[0;32m" . $action . "\033[39m" . $name . "\n";
        }

        echo "\n" . strval(count($routes)) . " routes found in " . $routes_config_file . "\n";
    }

    /**
     * Validate if database feature is enabled
     * 
     * @return void
     */
    function validate_database_enabled()
    {
        if ((!isset($_ENV['DB_ENABLE'])) || (!$_ENV['DB_ENABLE'])) {
            echo "\033[31mDatabase feature is disabled in environment config\033[39m\n";
            exit(1);
        }
    }

    /**
     * Generate argument array from start
     * 
     * @param array $argv The array with arguments
     * @param int $start The position from which to generate the array
     * @return array
     */
    function generate_arguments($argv, $start)
    {
        $result = [];

        if (count($argv) > $start) {
            foreach ($argv as $key => $value) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Process the input of the console
     * 
     * @param array $argv The arguments of the execution
     * @return void
     */
    function handleInput($argv)
    {
        //Handle console input

        if ((!isset($argv[1])) || ($argv[1] === 'help')) {
            echo "\033[33m" . ASATRU_FW_NAME . " by " . ASATRU_FW_AUTHOR . " (" . ASATRU_FW_CONTACT . ") - CLI interface\n\033[39m\n";
            echo "The following commands are available:\n";
            echo "+ help: Displays this help text\n";
            echo "+ make:model <name>: Creates a new model with migration\n";
            echo "+ make:module <name> <opt:args[]>: Creates a new module for business logics\n";
            echo "+ make:controller <name>: Creates a new controller\n";
            echo "+ make:language <ident>: Creates a new language folder with app.php\n"; 
            echo "+ make:validator <name> <ident>: Creates a new validator\n";
            echo "+ make:event <name> <handler>: Creates a new event handler\n";
            echo "+ make:command <name>: Creates a new command\n";
            echo "+ make:auth: Creates new authentication model and migration\n";
            echo "+ make:cache: Creates new cache model and migration\n";
            echo "+ make:test <name>: Creates a new test case\n";
            echo "+ migrate:fresh: Drops all migrations and creates all new\n";
            echo "+ migrate:list: Creates only all new created migrations\n";
            echo "+ migrate:drop: Drops all migrations\n";
            echo "+ routes:list: Lists all registered routes\n";
            echo "+ serve <port>: Spawns a development server. If port is not provided it uses port " . DEVSERV_DEFAULT_PORT . "\n";

            $custom_commands = get_custom_commands();
            foreach ($custom_commands as $key => $custom_cmd) {
                echo "+ " . $key . ": " . $custom_cmd['description'] . "\n"; 
            }
        } else if ($argv[1] === 'make:model') {
            if (!isset($argv[2])) {
                echo "\033[31mYou must specify the model name\033[39m\n";
                exit(0);
            }

            if (!createModel($argv[2])) {
                echo "\033[31mFailed to create model and migration\033[39m\n";
            } else {
                echo "\033[32mModel and migration files have been created!\033[39m\n";
            }
        } else if ($argv[1] === 'make:module') {
            if (!isset($argv[2])) {
                echo "\033[31mYou must specify the module name\033[39m\n";
                exit(0);
            }

            if (!createModule($argv[2], generate_arguments($argv, 3))) {
                echo "\033[31mFailed to create module\033[39m\n";
            } else {
                echo "\033[32mModule has been created!\033[39m\n";
            }
        } else if ($argv[1] === 'make:controller') {
            if (!isset($argv[2])) {
                echo "\033[31mYou must specify the controller name\033[39m\n";
                exit(0);
            }

            if (!createController($argv[2])) {
                echo "\033[31mFailed to create the controller\033[39m\n";
            } else {
                echo "\033[32mThe controller has been created!\033[39m\n";
            }
        } else if ($argv[1] === 'make:language') {
            if (!isset($argv[2])) {
                echo "\033[31mYou must specify the language identifier\033[39m\n";
                exit(0);
            }

            if (!createLang($argv[2])) {
                echo "\033[31mFailed to create the language content\033[39m\n";
            } else {
                echo "\033[32mThe language content has been created!\033[39m\n";
            }
        } else if ($argv[1] === 'make:validator') {
            if ((!isset($argv[2])) || (!isset($argv[3]))) {
                echo "\033[31mYou must specify the name of the validator and also its identifier\033[39m\n";
                exit(0);
            }

            if (!createValidator($argv[2], $argv[3])) {
                echo "\033[31mFailed to create the validator\033[39m\n";
            } else {
                echo "\033[32mThe validator has been created!\033[39m\n";
            }
        } else if ($argv[1] === 'make:event') {
            if ((!isset($argv[2])) || (!isset($argv[3]))) {
                echo "\033[31mYou must specify the name of the event handler and also its initial handler method\033[39m\n";
                exit(0);
            }

            if (!createEvent($argv[2], $argv[3])) {
                echo "\033[31mFailed to create the event handler\033[39m\n";
            } else {
                echo "\033[32mThe event handler has been created!\033[39m\n";
            }
        } else if ($argv[1] === 'make:command') {
            if (!isset($argv[2])) {
                echo "\033[31mYou must specify the class name of the command\033[39m\n";
                exit(0);
            }

            if (!createCommand($argv[2])) {
                echo "\033[31mFailed to create new command class\033[39m\n";
            } else {
                echo "\033[32mCommand class has been created!\033[39m\n";
            }
        } else if ($argv[1] === 'make:auth') {
            if (!createAuth()) {
                echo "\033[31mFailed to create auth objects\033[39m\n";
            } else {
                echo "\033[32mThe auth objects have been created!\033[39m\n";
            }
        } else if ($argv[1] === 'make:cache') {
            if (!createCache()) {
                echo "\033[31mFailed to create cache objects\033[39m\n";
            } else {
                echo "\033[32mThe cache objects have been created!\033[39m\n";
            }
        } else if ($argv[1] === 'make:test') {
            if (!createTest($argv[2])) {
                echo "\033[31mFailed to create test case\033[39m\n";
            } else {
                echo "\033[32mThe test case has been created!\033[39m\n";
            }
        } else if ($argv[1] === 'migrate:fresh') {
            validate_database_enabled();
            migrate_fresh(true);

            echo "\033[32mThe database has been freshly migrated!\033[39m\n";
        } else if ($argv[1] === 'migrate:list') {
            validate_database_enabled();
            migrate_list(true);

            echo "\033[32mThe database has been listly migrated!\033[39m\n";
        } else if ($argv[1] === 'migrate:drop') {
            validate_database_enabled();
            migrate_drop(true);

            echo "\033[32mThe database has been cleared!\033[39m\n";
        } else if ($argv[1] === 'routes:list') {
            list_registered_routes();
        } else if ($argv[1] === 'serve') {
            $retval = 0;
            $port = isset($argv[2]) ? $argv[2] : DEVSERV_DEFAULT_PORT;
            echo "\033[32mLocal development server started at localhost:" . $port . "\033[39m\n";
            system('php -S localhost:' . $port . ' -t public/', $retval);
        } else {
            try {
                handle_custom_command($argv[1]);
            } catch (\Exception $e) {
                echo "\033[31mCommand " . $argv[1] . " is unknown\033[39m\n";
            }
        }
    }
}

namespace {
    /**
     * Parse console command expression and execute it
     * 
     * @param $input
     * @return mixed
     */
    function asatru($input)
    {
        $tokens = preg_split('/\s+/', trim('asatru ' . $input));

        foreach ($tokens as &$token) {
            $token = str_replace('%20', ' ', $token);
        }

        ob_start();

        reset_custom_commands($tokens);

        Asatru\Console\handleInput($tokens);
        
        $output = ob_get_clean();

        return trim($output);
    }
}