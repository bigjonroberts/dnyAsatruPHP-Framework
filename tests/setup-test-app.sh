#!/bin/bash

# Setup minimal app structure for standalone framework testing
# Run this script from the framework root directory before running tests

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FRAMEWORK_ROOT="$SCRIPT_DIR/.."
APP_DIR="$FRAMEWORK_ROOT/app"

echo "Setting up minimal app structure for testing..."

# Create all required directories
mkdir -p "$APP_DIR/lang/en"
mkdir -p "$APP_DIR/config"
mkdir -p "$APP_DIR/models"
mkdir -p "$APP_DIR/modules"
mkdir -p "$APP_DIR/migrations"
mkdir -p "$APP_DIR/controller"
mkdir -p "$APP_DIR/validators"
mkdir -p "$APP_DIR/views"
mkdir -p "$APP_DIR/tests"
mkdir -p "$FRAMEWORK_ROOT/public/js"
mkdir -p "$FRAMEWORK_ROOT/public/css"

# Clean up any leftover files from previous test runs
rm -f "$APP_DIR/models/"*.php 2>/dev/null
rm -f "$APP_DIR/migrations/"*.php 2>/dev/null
rm -f "$APP_DIR/modules/"*.php 2>/dev/null
rm -f "$APP_DIR/controller/"*.php 2>/dev/null
rm -f "$APP_DIR/validators/"*.php 2>/dev/null
rm -rf "$APP_DIR/lang/de" 2>/dev/null

# Create main .env file with required variables
cat > "$FRAMEWORK_ROOT/.env" << 'EOF'
APP_NAME=Asatru PHP
APP_DEBUG=true
APP_LANG=en

DB_DRIVER=mysql
DB_ENABLE=false

SESSION_ENABLE=false
EOF

# Create language files with test data
cat > "$APP_DIR/lang/en/app.php" << 'EOF'
<?php
return [
    'welcome' => 'Welcome to Asatru PHP Framework'
];
EOF

cat > "$APP_DIR/lang/en/errors.php" << 'EOF'
<?php
return [
    'csrf_token_invalid' => 'CSRF token is invalid',
    'validation_failed' => 'Validation failed'
];
EOF

# Create minimal autoload config
cat > "$APP_DIR/config/autoload.php" << 'EOF'
<?php
return [];
EOF

# Create minimal events config
cat > "$APP_DIR/config/events.php" << 'EOF'
<?php
return [];
EOF

# Create commands config with test command
mkdir -p "$APP_DIR/commands"
cat > "$APP_DIR/commands/TestCmd.php" << 'EOF'
<?php

class TestCmd implements \Asatru\Commands\Command {
    public function handle($args)
    {
        echo "Test command executed\n";
        return true;
    }
}
EOF

cat > "$APP_DIR/config/commands.php" << 'EOF'
<?php
return [
    ['test:cmd', 'Test command description', 'TestCmd']
];
EOF

# Create routes config with test routes using controllers
cat > "$APP_DIR/config/routes.php" << 'EOF'
<?php
return [
    ['/index', 'GET', 'index@index'],
    ['/test/{id}/another/{id2}', 'GET', 'test@show']
];
EOF

# Create test asset files
echo "/* Test CSS */" > "$FRAMEWORK_ROOT/public/css/app.css"
echo "/* Test JS */" > "$FRAMEWORK_ROOT/public/js/app.js"

# Create view templates for tests
cat > "$APP_DIR/views/layout.php" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-with, initial-scale=1.0">
    <title>{title}</title>
</head>
<body>
    {%yield%}
</body>
</html>
EOF

cat > "$APP_DIR/views/index.php" << 'EOF'
<div class="outer">
    <h1>Example yield file</h1>
    <?= htmlspecialchars( __('app.welcome') , ENT_QUOTES | ENT_HTML401); ?>
</div>
EOF

# Create test controllers
cat > "$APP_DIR/controller/index.php" << 'EOF'
<?php

class IndexController extends \Asatru\Controller\Controller {
    public function index($ctrl)
    {
        $view = new \Asatru\View\ViewHandler();
        return $view;
    }
}
EOF

cat > "$APP_DIR/controller/test.php" << 'EOF'
<?php

class TestController extends \Asatru\Controller\Controller {
    public function show($ctrl)
    {
        // Extract path parameters from URL
        $id = $ctrl->arg('id');
        $id2 = $ctrl->arg('id2');

        $view = new \Asatru\View\ViewHandler();
        return $view;
    }
}
EOF

# Create test validator with correct ident
cat > "$APP_DIR/validators/Testvalidator.php" << 'EOF'
<?php

class TestvalidatorValidator {
    private $error = null;

    public function getIdent()
    {
        return 'testvalidator';
    }

    public function verify($data)
    {
        return true;
    }

    public function getError()
    {
        return $this->error;
    }
}
EOF

echo "Done! App structure created at: $APP_DIR"
echo ""
echo "Created:"
echo "  - Application directories (controller, validators, views, tests)"
echo "  - Public assets directory (js, css)"
echo "  - Main .env file with APP_NAME"
echo "  - Language files with test data"
echo "  - Config files (autoload, events, commands, routes)"
echo "  - View templates (layout, index)"
echo ""
echo "You can now run tests with:"
echo "  composer test:mysql  # Test with MySQL"
echo "  composer test:pgsql  # Test with PostgreSQL"
echo "  composer test        # Test with both databases"
