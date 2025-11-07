#!/bin/bash

echo "============================================"
echo "Task Scheduler - CRON Job Setup"
echo "============================================"
echo ""

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CRON_FILE="$SCRIPT_DIR/cron.php"

if [ ! -f "$CRON_FILE" ]; then
    echo "ERROR: cron.php not found at $CRON_FILE"
    exit 1
fi

PHP_PATH=$(which php)
if [ -z "$PHP_PATH" ]; then
    echo "ERROR: PHP is not installed or not in PATH"
    exit 1
fi

echo "PHP found at: $PHP_PATH"
echo "CRON script: $CRON_FILE"
echo ""

CRON_COMMAND="0 * * * * $PHP_PATH $CRON_FILE >> $SCRIPT_DIR/cron_output.log 2>&1"

echo "Checking existing CRON jobs..."
EXISTING_CRON=$(crontab -l 2>/dev/null | grep -F "$CRON_FILE")

if [ -n "$EXISTING_CRON" ]; then
    echo "CRON job already exists:"
    echo "  $EXISTING_CRON"
    echo ""
    read -p "Do you want to replace it? (y/n): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Setup cancelled."
        exit 0
    fi

    crontab -l 2>/dev/null | grep -v -F "$CRON_FILE" | crontab -
    echo "Existing CRON job removed."
fi

echo "Adding new CRON job..."
(crontab -l 2>/dev/null; echo "$CRON_COMMAND") | crontab -

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ CRON job successfully configured!"
    echo ""
    echo "The following job has been added:"
    echo "  $CRON_COMMAND"
    echo ""
    echo "This will run cron.php every hour at minute 0."
    echo ""
    echo "To view all CRON jobs: crontab -l"
    echo "To remove this CRON job: crontab -e (then delete the line)"
    echo ""
    echo "Logs will be saved to:"
    echo "  - $SCRIPT_DIR/cron_log.txt (application log)"
    echo "  - $SCRIPT_DIR/cron_output.log (cron output)"
else
    echo ""
    echo "✗ ERROR: Failed to set up CRON job"
    exit 1
fi

chmod +x "$CRON_FILE"

echo ""
echo "Testing cron.php execution..."
$PHP_PATH "$CRON_FILE"
echo ""
echo "If you see output above, the script is working correctly."
echo ""
echo "Setup complete!"
