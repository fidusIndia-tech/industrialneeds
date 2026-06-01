<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks a chunked background product bulk-import so progress survives page refreshes.
 * Rows to import live in a temp NDJSON file (see file_path); chunks are processed by
 * App\CPU\BulkImportProcessor and the counts here are updated after every chunk.
 */
class ProductImportJob extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_rows'                      => 'integer',
        'processed_rows'                  => 'integer',
        'created_count'                   => 'integer',
        'updated_count'                   => 'integer',
        'skipped_count'                   => 'integer',
        'failed_count'                    => 'integer',
        'new_brands_count'                => 'integer',
        'new_categories_count'            => 'integer',
        'new_sub_categories_count'        => 'integer',
        'new_sub_sub_categories_count'    => 'integer',
        'calculated_purchase_price_count' => 'integer',
        'calculated_selling_price_count'  => 'integer',
        'default_unit_used_count'         => 'integer',
        'default_moq_used_count'          => 'integer',
        'default_stock_used_count'        => 'integer',
        'with_images_count'               => 'integer',
        'without_images_count'            => 'integer',
        'images_from_zip_count'           => 'integer',
        'images_downloaded_count'         => 'integer',
        'failed_image_downloads_count'    => 'integer',
        'invalid_images_count'            => 'integer',
        'failed_rows'                     => 'array',
        'import_options'                  => 'array',
        'locked_at'                       => 'datetime',
        'started_at'                      => 'datetime',
        'completed_at'                    => 'datetime',
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
