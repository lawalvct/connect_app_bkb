<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SimpleUsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = User::query();

        // Log the filters being applied
        \Illuminate\Support\Facades\Log::info('SimpleUsersExport: Applying filters', $this->filters);

        // Apply same filters as getUsers
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['status'])) {
            $status = $this->filters['status'];
            if ($status === 'active') {
                $query->where('is_active', true)->where('is_banned', false);
            } elseif ($status === 'suspended') {
                $query->where('is_active', false);
            } elseif ($status === 'banned') {
                $query->where('is_banned', true);
            }
        }

        // Add verification filtering
        if (!empty($this->filters['verification'])) {
            $verification = $this->filters['verification'];
            if ($verification === 'verified') {
                // Users with approved verification
                $query->whereHas('verifications', function($q) {
                    $q->where('admin_status', 'approved');
                });
            } elseif ($verification === 'pending') {
                // Users with pending verification
                $query->whereHas('verifications', function($q) {
                    $q->where('admin_status', 'pending');
                });
            } elseif ($verification === 'rejected') {
                // Users with rejected verification
                $query->whereHas('verifications', function($q) {
                    $q->where('admin_status', 'rejected');
                });
            } elseif ($verification === 'none') {
                // Users without any verification submission
                $query->whereDoesntHave('verifications');
            }
        }

        // Add country filtering
        if (!empty($this->filters['country'])) {
            $countryId = $this->filters['country'];
            if (is_numeric($countryId)) {
                $query->where('country_id', $countryId);
            }
        }

        // Comment out verified filter
        /*
        if (!empty($this->filters['verified'])) {
            if ($this->filters['verified'] == '1') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }
        */

        // Add date range filtering
        if (!empty($this->filters['date_from'])) {
            try {
                $query->whereDate('created_at', '>=', $this->filters['date_from']);
            } catch (\Exception $e) {
                // Log error but continue
            }
        }

        if (!empty($this->filters['date_to'])) {
            try {
                $query->whereDate('created_at', '<=', $this->filters['date_to']);
            } catch (\Exception $e) {
                // Log error but continue
            }
        }

        // Add social circles filter if it exists
        if (!empty($this->filters['social_circles'])) {
            $socialCircleFilter = $this->filters['social_circles'];
            if ($socialCircleFilter === 'has_circles') {
                $query->whereHas('socialCircles');
            } elseif ($socialCircleFilter === 'no_circles') {
                $query->whereDoesntHave('socialCircles');
            } elseif (is_numeric($socialCircleFilter)) {
                // Filter by specific social circle ID
                $query->whereHas('socialCircles', function($q) use ($socialCircleFilter) {
                    $q->where('social_circles.id', $socialCircleFilter);
                });
            }
        }

        $users = $query->select([
            'id', 'name', 'email', 'phone', 'is_active', 'is_banned',
            'created_at', 'email_verified_at', 'updated_at'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        // Log the number of records found
        \Illuminate\Support\Facades\Log::info('SimpleUsersExport: Found ' . $users->count() . ' users with filters', $this->filters);

        return $users;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Status',
            'Email Verified',
            'Registration Date',
            'Last Updated'
        ];
    }

    /**
     * @param mixed $user
     * @return array
     */
    public function map($user): array
    {
        // Determine status
        $status = 'Active';
        if ($user->is_banned) {
            $status = 'Banned';
        } elseif (!$user->is_active) {
            $status = 'Suspended';
        }

        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?? 'N/A',
            $status,
            $user->email_verified_at ? 'Yes' : 'No',
            $user->created_at->format('Y-m-d H:i:s'),
            $user->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
