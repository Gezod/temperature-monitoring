#!/bin/bash
echo "=== BLACKBOX TESTING - Temperature Monitoring System ==="
echo "Start Time: $(date)"
echo ""

# Start server in background if not running
if ! lsof -i:8000 > /dev/null; then
    echo "Starting Laravel server..."
    php artisan serve > /dev/null 2>&1 &
    SERVER_PID=$!
    sleep 3
fi

# Base URL
BASE_URL="http://localhost:8000"
CSRF_TOKEN=$(curl -s $BASE_URL | grep -o 'csrf-token" content="[^"]*"' | cut -d'"' -f3)
echo "CSRF Token: $CSRF_TOKEN"
echo ""

# Test Results Log
LOG_FILE="blackbox_test_results_$(date +%Y%m%d_%H%M%S).log"
echo "Test Log: $LOG_FILE"
echo ""
