<?php

namespace App\Exports;

use App\Models\StreamPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class StreamPaymentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $streamId;

    public function __construct($streamId = null)
    {
        $this->streamId = $streamId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = StreamPayment::with(['user', 'stream'])
            ->orderBy('created_at', 'desc');

        if ($this->streamId) {
            $query->where('stream_id', $this->streamId);
        }

        return $query->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Payment ID',
            'Reference',
            'User Name',
            'User Email',
            'Stream Title',
            'Amount',
            'Currency',
            'Payment Gateway',
            'Transaction ID',
            'Status',
            'Paid At',
            'Created At',
        ];
    }

    /**
     * @param mixed $payment
     * @return array
     */
    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->reference,
            $payment->user->name ?? 'N/A',
            $payment->user->email ?? 'N/A',
            $payment->stream->title ?? 'N/A',
            number_format($payment->amount, 2),
            strtoupper($payment->currency),
            ucfirst($payment->payment_gateway),
            $payment->gateway_transaction_id ?? 'N/A',
            ucfirst($payment->status),
            $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : 'N/A',
            $payment->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
