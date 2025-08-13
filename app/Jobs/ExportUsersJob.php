<?php

namespace App\Jobs;

use App\Exports\SimpleUsersExport;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\ExportReadyMail;

class ExportUsersJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $filters;
    protected $format;
    protected $adminUser;
    protected $filename;

    /**
     * Create a new job instance.
     */
    public function __construct($filters, $format, $adminUser)
    {
        $this->filters = $filters;
        $this->format = $format;
        $this->adminUser = $adminUser;
        $this->filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.' . ($format === 'csv' ? 'csv' : 'xlsx');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting queued user export', [
                'format' => $this->format,
                'filters' => $this->filters,
                'admin_user' => $this->adminUser->id,
                'filename' => $this->filename
            ]);

            // Create the export
            if ($this->format === 'csv') {
                Excel::store(
                    new SimpleUsersExport($this->filters),
                    'exports/' . $this->filename,
                    'public',
                    \Maatwebsite\Excel\Excel::CSV
                );
            } else {
                Excel::store(
                    new SimpleUsersExport($this->filters),
                    'exports/' . $this->filename,
                    'public',
                    \Maatwebsite\Excel\Excel::XLSX
                );
            }

            // Send email notification to admin
            if ($this->adminUser->email) {
                try {
                    Log::info('Attempting to send export notification email', [
                        'admin_email' => $this->adminUser->email,
                        'filename' => $this->filename
                    ]);

                    Mail::to($this->adminUser->email)->send(new ExportReadyMail($this->filename, $this->format));

                    Log::info('Export notification email sent successfully', [
                        'admin_email' => $this->adminUser->email,
                        'filename' => $this->filename
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to send export notification email', [
                        'error' => $e->getMessage(),
                        'admin_email' => $this->adminUser->email,
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Don't fail the job if email fails, just log it
                }
            } else {
                Log::warning('No admin email found for export notification', [
                    'admin_user_id' => $this->adminUser->id
                ]);
            }

            Log::info('User export completed successfully', [
                'filename' => $this->filename,
                'path' => 'storage/exports/' . $this->filename
            ]);

        } catch (\Exception $e) {
            Log::error('User export job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $this->filters,
                'format' => $this->format
            ]);

            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Export job failed permanently', [
            'error' => $exception->getMessage(),
            'filters' => $this->filters,
            'format' => $this->format,
            'admin_user' => $this->adminUser->id
        ]);
    }
}
