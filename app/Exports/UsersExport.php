<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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

        return $query->select([
            'id', 'name', 'email', 'phone', 'is_active', 'is_banned',
            'banned_until', 'created_at', 'email_verified_at', 'updated_at'
        ])
        ->with(['socialCircles:id,name'])
        ->withCount(['posts', 'socialCircles'])
        ->orderBy('created_at', 'desc')
        ->get();
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
            'Social Circles Count',
            'Social Circles Names',
            'Posts Count',
            'Registration Date',
            'Last Updated',
            'Banned Until'
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

        // Get social circles names
        $socialCirclesNames = $user->socialCircles ?
            $user->socialCircles->pluck('name')->join(', ') :
            'None';

        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?? 'N/A',
            $status,
            $user->email_verified_at ? 'Yes' : 'No',
            $user->social_circles_count ?? 0,
            $socialCirclesNames,
            $user->posts_count ?? 0,
            $user->created_at->format('Y-m-d H:i:s'),
            $user->updated_at->format('Y-m-d H:i:s'),
            $user->banned_until ? $user->banned_until->format('Y-m-d H:i:s') : 'N/A'
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        try {
            return [
                // Style the first row as header with simpler styling
                1 => [
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFCCCCCC'],
                    ],
                ],
            ];
        } catch (\Exception $e) {
            // If styling fails, return empty array to prevent export failure
            return [];
        }
    }
}
