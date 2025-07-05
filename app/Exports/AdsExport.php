<?php

namespace App\Exports;

use App\Models\Ad;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $ads;
    protected $user;

    public function __construct($ads, $user)
    {
        $this->ads = $ads;
        $this->user = $user;
    }

    public function collection()
    {
        return $this->ads;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Ad Name',
            'Type',
            'Status',
            'Budget',
            'Spent',
            'Impressions',
            'Clicks',
            'CTR',
            'Start Date',
            'End Date',
        ];
    }

    public function map($ad): array
    {
        return [
            $ad->id,
            $ad->ad_name,
            ucfirst($ad->type),
            ucfirst($ad->status),
            number_format($ad->budget, 2),
            number_format($ad->total_spent, 2),
            number_format($ad->current_impressions),
            number_format($ad->clicks),
            $ad->ctr . '%',
            $ad->start_date->format('Y-m-d'),
            $ad->end_date->format('Y-m-d'),
        ];
    }

    public function title(): string
    {
        return 'Advertisements';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}