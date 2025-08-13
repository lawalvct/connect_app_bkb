<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Country;

echo "Testing Country Analytics fixes...\n\n";

try {
    // Test country relationship exists
    $countryCount = Country::count();
    echo "✅ Countries table accessible: {$countryCount} countries found\n";

    // Test users with country_id
    $usersWithCountry = User::where('deleted_flag', 'N')
        ->whereNotNull('country_id')
        ->count();
    echo "✅ Users with country_id: {$usersWithCountry} users found\n";

    // Test the actual popular countries query
    $popularCountries = User::where('deleted_flag', 'N')
        ->whereNotNull('country_id')
        ->join('countries', 'users.country_id', '=', 'countries.id')
        ->selectRaw('countries.name as country, COUNT(*) as user_count')
        ->groupBy('countries.id', 'countries.name')
        ->orderByDesc('user_count')
        ->limit(5)
        ->get();
    echo "✅ Popular countries query works: " . $popularCountries->count() . " countries found\n";

    if ($popularCountries->count() > 0) {
        echo "   Top country: " . $popularCountries->first()->country . " ({$popularCountries->first()->user_count} users)\n";
    }

    echo "\n🎉 All country analytics queries are working correctly!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
