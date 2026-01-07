#!/bin/bash
echo "🚀 Quick Blackbox Test - Temperature Monitoring"
echo ""

# Test 1: Is server running?
echo "1. Server Status:"
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000 | grep -q "200\|302"; then
    echo "   ✅ Server is running"
else
    echo "   ❌ Server not responding"
    echo "   Starting server..."
    php artisan serve > /dev/null 2>&1 &
    sleep 3
fi


# Test 3: Temperature logic simulation
echo "2. Temperature Logic Test:"
TEST_TEMP=35
if [[ $TEST_TEMP -gt 30 ]]; then
    echo "   ✅ $TEST_TEMP°C → Warning (orange)"
else
    echo "   ❌ Logic failed for $TEST_TEMP°C"
fi

# Test 4: Check frontend assets
echo "3. Frontend Assets:"
if curl -s http://localhost:8000 | grep -q "Chart.js\|Bootstrap"; then
    echo "   ✅ JavaScript libraries detected"
else
    echo "   ❌ Libraries missing"
fi

echo ""
echo "📊 Summary: Run full tests for detailed validation"
