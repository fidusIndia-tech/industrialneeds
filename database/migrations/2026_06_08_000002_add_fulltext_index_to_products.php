<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * InnoDB FULLTEXT index on products(name, product_code) for relevance-ranked,
 * index-backed search — replacing leading-% LIKE full table scans.
 *
 * BEST-EFFORT: some MySQL 8.0.x builds (seen on 8.0.30) hit a known online-DDL
 * regression when adding the first FULLTEXT index to a table that has nullable
 * secondary indexes — the FTS_DOC_ID rebuild fails with a spurious "Duplicate
 * entry" on PRIMARY/secondary keys. We don't want that to abort a deploy, so on
 * failure we log a warning and continue. ProductManager::search_filter() detects
 * whether the index actually exists and falls back to LIKE when it does not, so
 * search keeps working either way (Meilisearch is the planned real fix).
 */
class AddFulltextIndexToProducts extends Migration
{
    private const INDEX = 'products_name_code_fulltext';

    public function up()
    {
        if ($this->indexExists()) {
            return;
        }
        try {
            DB::statement('ALTER TABLE products ADD FULLTEXT ' . self::INDEX . ' (name, product_code)');
        } catch (\Throwable $e) {
            Log::warning('Could not create FULLTEXT index ' . self::INDEX . ' (search falls back to LIKE). '
                . 'Likely the MySQL 8.0.x ADD FULLTEXT regression. Error: ' . $e->getMessage());
        }
    }

    public function down()
    {
        if ($this->indexExists()) {
            DB::statement('ALTER TABLE products DROP INDEX ' . self::INDEX);
        }
    }

    private function indexExists(): bool
    {
        return !empty(DB::select('SHOW INDEX FROM products WHERE Key_name = ?', [self::INDEX]));
    }
}
