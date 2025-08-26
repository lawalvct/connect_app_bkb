<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    /**
     * Display a listing of countries.
     */
    public function index()
    {
        return view('admin.countries.index');
    }

    /**
     * Show the form for creating a new country.
     */
    public function create()
    {
        return view('admin.countries.create');
    }

    /**
     * Store a newly created country in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:countries',
            'code' => 'required|string|size:2|unique:countries',
            'code3' => 'nullable|string|size:3|unique:countries',
            'numeric_code' => 'nullable|string|size:3|unique:countries',
            'phone_code' => 'nullable|string|max:10',
            'capital' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|size:3',
            'currency_symbol' => 'nullable|string|max:10',
            'region' => 'nullable|string|max:255',
            'subregion' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
            'timezones' => 'nullable|json',
            'timezone_offset' => 'nullable|string|max:10',
            'has_dst' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'emoji' => 'nullable|string|max:16',
            'active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Convert code to uppercase
        $data['code'] = strtoupper($data['code']);
        if (isset($data['code3'])) {
            $data['code3'] = strtoupper($data['code3']);
        }
        if (isset($data['currency_code'])) {
            $data['currency_code'] = strtoupper($data['currency_code']);
        }

        $country = Country::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Country created successfully',
                'data' => $country
            ]);
        }

        return redirect()->route('admin.countries.index')
                         ->with('success', 'Country created successfully.');
    }

    /**
     * Display the specified country.
     */
    public function show(Country $country)
    {
        $country->loadCount('users');
        $recentUsers = $country->users()->latest()->limit(10)->get();

        return view('admin.countries.show', compact('country', 'recentUsers'));
    }

    /**
     * Show the form for editing the specified country.
     */
    public function edit(Country $country)
    {
        return view('admin.countries.edit', compact('country'));
    }

    /**
     * Update the specified country in storage.
     */
    public function update(Request $request, Country $country)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:countries,name,' . $country->id,
            'code' => 'required|string|size:2|unique:countries,code,' . $country->id,
            'code3' => 'nullable|string|size:3|unique:countries,code3,' . $country->id,
            'numeric_code' => 'nullable|string|size:3|unique:countries,numeric_code,' . $country->id,
            'phone_code' => 'nullable|string|max:10',
            'capital' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|size:3',
            'currency_symbol' => 'nullable|string|max:10',
            'region' => 'nullable|string|max:255',
            'subregion' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
            'timezones' => 'nullable|json',
            'timezone_offset' => 'nullable|string|max:10',
            'has_dst' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'emoji' => 'nullable|string|max:16',
            'active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Convert code to uppercase
        $data['code'] = strtoupper($data['code']);
        if (isset($data['code3'])) {
            $data['code3'] = strtoupper($data['code3']);
        }
        if (isset($data['currency_code'])) {
            $data['currency_code'] = strtoupper($data['currency_code']);
        }

        $country->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Country updated successfully',
                'data' => $country
            ]);
        }

        return redirect()->route('admin.countries.index')
                         ->with('success', 'Country updated successfully.');
    }

    /**
     * Remove the specified country from storage.
     */
    public function destroy(Country $country)
    {
        // Check if country has users
        $userCount = $country->users()->count();
        if ($userCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete country with {$userCount} associated users"
            ], 422);
        }

        $country->delete();

        return response()->json([
            'success' => true,
            'message' => 'Country deleted successfully'
        ]);
    }

    /**
     * Update the status of a country.
     */
    public function updateStatus(Request $request, Country $country)
    {
        $validator = Validator::make($request->all(), [
            'active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $country->update([
            'active' => $request->active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Country status updated successfully',
            'data' => $country
        ]);
    }

    /**
     * Export countries data.
     */
    public function export(Request $request)
    {
        $countries = Country::withCount('users')
                           ->orderBy('name')
                           ->get();

        $filename = 'countries-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($countries) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID',
                'Name',
                'Code (ISO 2)',
                'Code (ISO 3)',
                'Numeric Code',
                'Phone Code',
                'Capital',
                'Currency',
                'Currency Code',
                'Currency Symbol',
                'Region',
                'Subregion',
                'Timezone',
                'Timezone Offset',
                'Has DST',
                'Latitude',
                'Longitude',
                'Emoji',
                'Active',
                'Users Count',
                'Created At',
                'Updated At'
            ]);

            foreach ($countries as $country) {
                fputcsv($file, [
                    $country->id,
                    $country->name,
                    $country->code,
                    $country->code3,
                    $country->numeric_code,
                    $country->phone_code,
                    $country->capital,
                    $country->currency,
                    $country->currency_code,
                    $country->currency_symbol,
                    $country->region,
                    $country->subregion,
                    $country->timezone,
                    $country->timezone_offset,
                    $country->has_dst ? 'Yes' : 'No',
                    $country->latitude,
                    $country->longitude,
                    $country->emoji,
                    $country->active ? 'Yes' : 'No',
                    $country->users_count,
                    $country->created_at ? $country->created_at->format('Y-m-d H:i:s') : '',
                    $country->updated_at ? $country->updated_at->format('Y-m-d H:i:s') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get countries data for API
     */
    public function getCountries(Request $request)
    {
        $query = Country::withCount('users');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('code3', 'like', "%{$search}%")
                  ->orWhere('capital', 'like', "%{$search}%");
            });
        }

        // Apply region filter
        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $status = $request->status === 'active';
            $query->where('active', $status);
        }

        // Get stats
        $stats = [
            'total' => Country::count(),
            'active' => Country::where('active', true)->count(),
            'with_users' => Country::has('users')->count(),
            'regions' => Country::distinct('region')->whereNotNull('region')->count(),
        ];

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $countries = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $countries,
            'stats' => $stats
        ]);
    }
}
