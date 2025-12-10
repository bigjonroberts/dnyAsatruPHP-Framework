# Asatru PHP

(C) 2019 - 2025 by Daniel Brendel

**Codename**: dnyAsatruPHP\
**Contact**: dbrendel1988(at)gmail(dot)com\
**GitHub**: https://github.com/danielbrendel

Released under the MIT license

## Description:
This product is a lightweight PHP framework which can be used to create your own PHP apps using MVC design pattern.

## Feature overview:
+ Controllers
+ Views
+ Models
+ Modules
+ Migrations
+ Templating
+ Logging
+ .env parser
+ Localization
+ Exception handling
+ Events
+ Commands
+ Validators
+ Helpers
+ Autoloading
+ Config management
+ Security
+ Flash messages
+ Authentication
+ Caching
+ Testing
+ CLI interface
+ mail() wrapper
+ SMTP mailing
+ Html helper
+ Form helper
+ Carbon support
+ npm/webpack support

## Installation
The installation of this framework is just one composer command away:
```
composer require danielbrendel/asatru-php-framework
```

To create a new project run the command:
```
composer create-project danielbrendel/asatru-php
```

## Documentation
The documentation is located in the /doc directory.

## Testing
In order to run the framework tests you have to place the project folder so as
it would be done with Composer with an App skeleton. This is due to the fact
that the tests use the app skeleton for several input sources and output. Also be 
sure that a database is running and adjust the settings.

Then go to the framework base directory and issue the following command to run the framework tests
```sh
"vendor/bin/phpunit" --stderr
```

## Requirements
+ PHP ^8.3
+ MariaDB ^10.4 or PostgreSQL ^13
+ Composer ^2.2
