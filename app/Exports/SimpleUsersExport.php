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

        if (!empty($this->filters['verified'])) {
            if ($this->filters['verified'] == '1') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        return $query->select([
            'id', 'name', 'email', 'phone', 'is_active', 'is_banned',
            'created_at', 'email_verified_at', 'updated_at'
        ])
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
