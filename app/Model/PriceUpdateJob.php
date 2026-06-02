<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks a chunked background price-only update so progress survives page refreshes. Rows to apply
 * live in a temp NDJSON file (file_path); chunks are processed by App\CPU\PriceUpdater::processChunk
 * and the counters here are updated after every chunk. Parallel to ProductImportJob.
 */
class PriceUpdateJob extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_rows'      => 'integer',
        'processed_rows'  => 'integer',
        'updated_count'   => 'integer',
        'skipped_count'   => 'integer',
        'not_found_count' => 'integer',
        'failed_count'    => 'integer',
        'import_options'  => 'array',
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
    ];

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled'], true);
    }

    public function percentage(): float
    {
        if ($this->total_rows <= 0) {
            return 0.0;
        }
        return round(min(100, ($this->processed_rows / $this->total_rows) * 100), 2);
    }
}
