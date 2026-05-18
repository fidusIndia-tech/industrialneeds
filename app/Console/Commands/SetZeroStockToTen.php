<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetZeroStockToTen extends Command
{
    protected $signature = 'products:set-zero-stock-to-ten {--execute : Actually perform the update (otherwise runs as a dry run)}';

    protected $description = 'Set current_stock = 10 for products where current_stock = 0. Dry-run by default; pass --execute to apply.';

    public function handle()
    {
        $table = 'products';
        $column = 'current_stock';

        if (!Schema::hasTable($table)) {
            $this->error("Table `{$table}` does not exist.");
            return 1;
        }

        if (!Schema::hasColumn($table, $column)) {
            $this->error("Column `{$column}` does not exist on `{$table}`.");
            return 1;
        }

        $this->info("Stock column detected: {$table}.{$column}");

        $zeroStockCount = DB::table($table)->where($column, 0)->count();
        $this->info("Total products with {$column} = 0: {$zeroStockCount}");

        if ($zeroStockCount === 0) {
            $this->info('Nothing to update.');
            return 0;
        }

        if (!$this->option('execute')) {
            $this->warn('Dry run: no changes were made. Re-run with --execute to apply the update.');
            return 0;
        }

        if (!$this->confirm("Update {$zeroStockCount} product(s) by setting {$column} from 0 to 10?", true)) {
            $this->info('Aborted. No changes were made.');
            return 0;
        }

        $updated = DB::table($table)->where($column, 0)->update([$column => 10]);

        $this->info("Updated {$updated} product(s). {$column} is now 10 for those rows.");

        return 0;
    }
}
