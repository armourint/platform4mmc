<?php

namespace App\Jobs;

use App\Models\DataImport;
use App\Services\ImportRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDataImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public DataImport $import) {}

    public function handle(): void
    {
        $import = $this->import->fresh();
        if (!$import) return;

        $import->update(['status' => 'processing', 'error' => null]);

        try {
            // Run importer (ignore row counts entirely)
            ImportRouter::handle($import);

            // ✅ Do NOT set rows_processed to null; leave it as-is (likely 0 by default)
            $import->update([
                'status' => 'completed',
            ]);
        } catch (\Throwable $e) {
            Log::error('Import failed', [
                'import_id' => $import->id,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);

            // ✅ Do NOT touch rows_processed here either
            $import->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
