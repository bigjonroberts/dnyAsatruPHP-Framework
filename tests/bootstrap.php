<?php

/*
    Asatru PHP (dnyAsatruPHP) developed by Daniel Brendel
    
    (C) 2019 - 2025 by Daniel Brendel
    
    Contact: dbrendel1988<at>gmail<dot>com
    GitHub: https://github.com/danielbrendel/
    
    Released under the MIT license
*/

//Set application root directory path
// For standalone framework tests, use the framework's own app directory
define('ASATRU_APP_ROOT', __DIR__ . '/..');

//If composer is installed we utilize its autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

//Fetch constants
require_once __DIR__ . '/../src/constants.php';

//Require the controller component
require_once __DIR__ . '/../src/controller.php';

//Require the view component
require_once __DIR__ . '/../src/view.php';

//Require logging
require_once __DIR__ . '/../src/logger.php';

//Require .env config management
require_once __DIR__ . '/../src/dotenv.php';

//Parse .env file if it exists
if (file_exists(__DIR__ . '/../.env.testing')) {
    env_parse(__DIR__ . '/../.env.testing');
}

//Require autoload component
require_once __DIR__ . '/../src/autoload.php';

//Require helpers
require_once __DIR__ . '/../src/helper.php';

//Require Html helper
require_once  __DIR__ . '/../src/html.php';

//Require form helper
require_once  __DIR__ . '/../src/forms.php';

//Require mail wrapper
require_once __DIR__ . '/../src/mailwrapper.php';

//Require testing component
require_once __DIR__ . '/../src/testing.php';

//Enable debug mode error handling
$_ENV['APP_DEBUG'] = true;
error_reporting(E_ALL);

//Set DOCUMENT_ROOT for controller URL parsing
if (!isset($_SERVER['DOCUMENT_ROOT']) || empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = ASATRU_APP_ROOT . '/public';
}

//Check if we shall create/continue a session
if ((isset($_ENV['SESSION_ENABLE'])) && ($_ENV['SESSION_ENABLE'])) {
    if ((isset($_ENV['SESSION_DURATION'])) && ($_ENV['SESSION_DURATION'] !== null) && (is_numeric($_ENV['SESSION_DURATION']))) {
        ini_set('session.cookie_lifetime', $_ENV['SESSION_DURATION']);
        ini_set('session.gc_maxlifetime', $_ENV['SESSION_DURATION']);
    }

    if ((isset($_ENV['SESSION_NAME'])) && (is_string($_ENV['SESSION_NAME'])) && (strlen($_ENV['SESSION_NAME']) > 0)) {
        session_name($_ENV['SESSION_NAME']);
    }

    if (!session_start()) {
        throw new Exception('Failed to create/continue the session');
    }

    if (!isset($_SESSION['continued'])) { //Check if a new session
        //Create CSRF-token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        //Mark session
        $_SESSION['continued'] = true;
    }
}

//Set console argument variables if not exists
$argv = ($argv ?? []);
$argc = ($argc ?? 0);

//Require localization
require_once __DIR__ . '/../src/locale.php';

//Require database management
require_once __DIR__ . '/../src/database.php';

//Require modules
require_once __DIR__ . '/../src/modules.php';

//Require event manager
require_once __DIR__ . '/../src/events.php';

//Require command handler
require_once __DIR__ . '/../src/commands.php';

//Require console
require_once __DIR__ . '/../src/console.php';

// Initialize global objects for testing
global $objLocale, $objEventManager, $objLogger, $objLanguage;

if (!isset($objLocale)) {
    $objLocale = new \Asatru\Lang\Locale();
}

if (!isset($objEventManager)) {
    $objEventManager = new \Asatru\Events\EventManager(ASATRU_APP_ROOT . '/app/config/events.php');
}

if (!isset($objLogger)) {
    $objLogger = new \Asatru\Logger\Logger();
}

// $objLanguage should already be initialized by locale.php (line 94)
// But ensure it exists for tests
if (!isset($objLanguage)) {
    $objLanguage = new \Asatru\Lang\Language();
    $objLanguage->load('en');
}

//Load test model
require_once __DIR__ . '/TestModel.php';