#!/bin/bash

# Setup minimal app structure for standalone framework testing
# Run this script from the framework root directory before running tests

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$SCRIPT_DIR/../app"

echo "Setting up minimal app structure for testing..."

# Create directories
mkdir -p "$APP_DIR/lang/en"
mkdir -p "$APP_DIR/config"
mkdir -p "$APP_DIR/models"
mkdir -p "$APP_DIR/modules"
mkdir -p "$APP_DIR/migrations"

# Create minimal language file
cat > "$APP_DIR/lang/en/app.php" << 'EOF'
<?php
return [];
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

# Create minimal commands config
cat > "$APP_DIR/config/commands.php" << 'EOF'
<?php
return [];
EOF

echo "Done! App structure created at: $APP_DIR"
echo ""
echo "You can now run tests with:"
echo "  ./vendor/bin/phpunit tests/"
