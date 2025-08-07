<?php

echo "Testing admin management route access...\n\n";

// Test the actual URL path
$adminUrl = "http://localhost/admin/admins";
echo "Admin Management URL: " . $adminUrl . "\n";

echo "\nRoute should be accessible at: /admin/admins\n";
echo "Make sure you:\n";
echo "1. Are logged in as admin at: /admin/login\n";
echo "2. Use credentials: admin@connectapp.com / admin123\n";
echo "3. Have Super Admin or Admin role\n\n";

echo "If you're still getting 'Route not defined' error:\n";
echo "1. Check web server is running\n";
echo "2. Clear all caches: php artisan cache:clear\n";
echo "3. Check Laravel error logs\n";
