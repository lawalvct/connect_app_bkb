<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialCircle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SocialCircleController extends Controller
{
    /**
     * Display a listing of social circles.
     */
    public function index()
    {
        return view('admin.social-circles.index');
    }

    /**
     * Show the form for creating a new social circle.
     */
    public function create()
    {
        return view('admin.social-circles.create');
    }

    /**
     * Store a newly created social circle in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:social_circles',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'order_by' => 'nullable|integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_private' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['created_by'] = auth('admin')->id();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoName = time() . '_' . $logoFile->getClientOriginalName();

            // Ensure directory exists
            $uploadDir = public_path('uploads/logo');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Store in public/uploads/logo/ directory
            $logoFile->move($uploadDir, $logoName);

            $data['logo'] = $logoName;
            $data['logo_url'] = 'uploads/logo';
        }

        $socialCircle = SocialCircle::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Social circle created successfully',
                'data' => $socialCircle
            ]);
        }

        return redirect()->route('admin.social-circles.index')
                         ->with('success', 'Social circle created successfully.');
    }

    /**
     * Display the specified social circle.
     */
    public function show(SocialCircle $socialCircle)
    {
        $socialCircle->load(['users', 'creator']);

        return view('admin.social-circles.show', compact('socialCircle'));
    }

    /**
     * Show the form for editing the specified social circle.
     */
    public function edit(SocialCircle $socialCircle)
    {
        return view('admin.social-circles.edit', compact('socialCircle'));
    }

    /**
     * Update the specified social circle in storage.
     */
    public function update(Request $request, SocialCircle $socialCircle)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:social_circles,name,' . $socialCircle->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'order_by' => 'nullable|integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_private' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['updated_by'] = auth('admin')->id();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($socialCircle->logo && $socialCircle->logo_url) {
                $oldLogoPath = public_path($socialCircle->logo_url . '/' . $socialCircle->logo);
                if (file_exists($oldLogoPath)) {
                    unlink($oldLogoPath);
                }
            }

            $logoFile = $request->file('logo');
            $logoName = time() . '_' . $logoFile->getClientOriginalName();

            // Ensure directory exists
            $uploadDir = public_path('uploads/logo');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Store in public/uploads/logo/ directory
            $logoFile->move($uploadDir, $logoName);

            $data['logo'] = $logoName;
            $data['logo_url'] = 'uploads/logo';
        }        $socialCircle->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Social circle updated successfully',
                'data' => $socialCircle
            ]);
        }

        return redirect()->route('admin.social-circles.index')
                         ->with('success', 'Social circle updated successfully.');
    }

    /**
     * Remove the specified social circle from storage.
     */
    public function destroy(SocialCircle $socialCircle)
    {
        // Delete logo if exists
        if ($socialCircle->logo && $socialCircle->logo_url) {
            $logoPath = public_path($socialCircle->logo_url . '/' . $socialCircle->logo);
            if (file_exists($logoPath)) {
                unlink($logoPath);
            }
        }

        $socialCircle->deleted_by = auth('admin')->id();
        $socialCircle->deleted_flag = 'Y';
        $socialCircle->save();
        $socialCircle->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'Social circle deleted successfully'
        ]);
    }

    /**
     * Update the status of a social circle.
     */
    public function updateStatus(Request $request, SocialCircle $socialCircle)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $socialCircle->update([
            'is_active' => $request->is_active,
            'updated_by' => auth('admin')->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Social circle status updated successfully',
            'data' => $socialCircle
        ]);
    }

    /**
     * Export social circles data.
     */
    public function export(Request $request)
    {
        $socialCircles = SocialCircle::withoutGlobalScope('active')
                                   ->with(['creator'])
                                   ->orderBy('order_by')
                                   ->orderBy('name')
                                   ->get();

        $filename = 'social-circles-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($socialCircles) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID',
                'Name',
                'Description',
                'Color',
                'Order',
                'Is Default',
                'Is Active',
                'Is Private',
                'Users Count',
                'Created By',
                'Created At'
            ]);

            foreach ($socialCircles as $circle) {
                fputcsv($file, [
                    $circle->id,
                    $circle->name,
                    $circle->description,
                    $circle->color,
                    $circle->order_by,
                    $circle->is_default ? 'Yes' : 'No',
                    $circle->is_active ? 'Yes' : 'No',
                    $circle->is_private ? 'Yes' : 'No',
                    $circle->users()->count(),
                    $circle->creator ? $circle->creator->name : 'N/A',
                    $circle->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get social circles data for API
     */
    public function getSocialCircles(Request $request)
    {
        // Use withoutGlobalScope to see all social circles in admin, but still filter deleted ones
        $query = SocialCircle::withoutGlobalScope('active')
            ->where('deleted_flag', 'N')
            ->withCount('users');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $status = $request->status === 'active' || $request->status === '1';
            $query->where('is_active', $status);
        }

        // Apply type filter
        if ($request->filled('type')) {
            switch ($request->type) {
                case 'default':
                    $query->where('is_default', true);
                    break;
                case 'private':
                    $query->where('is_private', true);
                    break;
                case 'custom':
                    $query->where('is_default', false)->where('is_private', false);
                    break;
            }
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply member count range filter
        if ($request->filled('min_members')) {
            $query->having('users_count', '>=', (int)$request->min_members);
        }
        if ($request->filled('max_members')) {
            $query->having('users_count', '<=', (int)$request->max_members);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        switch($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'users_count':
                $query->orderBy('users_count', $sortDirection);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                break;
        }

        // Get stats
        $stats = [
            'total' => SocialCircle::withoutGlobalScope('active')->where('deleted_flag', 'N')->count(),
            'active' => SocialCircle::withoutGlobalScope('active')->where('deleted_flag', 'N')->where('is_active', true)->count(),
            'inactive' => SocialCircle::withoutGlobalScope('active')->where('deleted_flag', 'N')->where('is_active', false)->count(),
            'default' => SocialCircle::withoutGlobalScope('active')->where('deleted_flag', 'N')->where('is_default', true)->count(),
            'private' => SocialCircle::withoutGlobalScope('active')->where('deleted_flag', 'N')->where('is_private', true)->count(),
            'total_users' => SocialCircle::withoutGlobalScope('active')->where('deleted_flag', 'N')->withCount('users')->get()->sum('users_count'),
            'avg_members' => (int) SocialCircle::withoutGlobalScope('active')->where('deleted_flag', 'N')->withCount('users')->get()->avg('users_count'),
        ];

        // Paginate results
        $perPage = $request->get('per_page', 20);
        $socialCircles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $socialCircles,
            'stats' => $stats
        ]);
    }
}
