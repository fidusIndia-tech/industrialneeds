<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Make storage/app/public a SYMLINK to a directory OUTSIDE the git / deploy tree, so a deployment
 * (git clean -x, a fresh re-clone, or a panel "Deploy") can never wipe uploaded images again.
 *
 * The real files live in STORAGE_PERSISTENT_PATH — by default a sibling of the project root, e.g.
 *   /home/<user>/domains/<site>/persistent_public        (OUTSIDE public_html)
 * and storage/app/public becomes a symlink pointing at it. Because the target is outside the
 * deployed directory, nothing the deploy does to public_html can delete the images.
 *
 * Self-healing: if a deploy replaces the symlink with a fresh empty directory, this command (also
 * scheduled every minute) migrates any stray files written there into the persistent dir and
 * re-creates the symlink. Worst case after a deploy is a ~1 minute gap, with no data loss.
 *
 * OPT-IN: only acts when STORAGE_PERSISTENT_LINK=true (set on the live server only). On local dev
 * it is a no-op, so Laragon keeps using a normal directory and nothing changes locally.
 */
class EnsurePersistentStorage extends Command
{
    protected $signature = 'storage:persist {--force : run even when STORAGE_PERSISTENT_LINK is not set}';

    protected $description = 'Symlink storage/app/public to a persistent dir outside the deploy tree (deploy-proof images).';

    public function handle(): int
    {
        if (!$this->option('force') && !filter_var(env('STORAGE_PERSISTENT_LINK', false), FILTER_VALIDATE_BOOLEAN)) {
            return self::SUCCESS; // disabled (e.g. local dev) — do nothing
        }

        // Shared hosts (CloudLinux/Hostinger) routinely put symlink() in disable_functions.
        // Bail out BEFORE step 3 touches anything: that step deletes the real directory and
        // then calls symlink(), so without this guard a disabled symlink() leaves NOTHING at
        // storage/app/public and every image on the site 404s. Note @ does not suppress the
        // resulting "Call to undefined function" Error on PHP 8.
        if (!function_exists('symlink')) {
            $this->error('PHP symlink() is disabled on this host (see disable_functions in php -i).');
            $this->line('Nothing was changed. PHP cannot manage the media link here — use a');
            $this->line('shell-level cron instead. See DEPLOY.md section 5a.');
            return self::FAILURE;
        }

        $link   = storage_path('app/public');
        $target = rtrim(env('STORAGE_PERSISTENT_PATH', dirname(base_path()) . DIRECTORY_SEPARATOR . 'persistent_public'), DIRECTORY_SEPARATOR);

        // 1) Ensure the persistent target exists.
        if (!is_dir($target)) {
            File::makeDirectory($target, 0755, true);
            $this->info("Created persistent storage: {$target}");
        }

        // 2) Already correctly linked? Nothing to do.
        if (is_link($link) && rtrim((string) readlink($link), DIRECTORY_SEPARATOR) === $target) {
            return self::SUCCESS;
        }

        // 3) A REAL directory is sitting where the symlink should be (fresh deploy): migrate any
        //    files the app may have written there into the persistent dir, then remove the dir.
        if (is_dir($link) && !is_link($link)) {
            $moved = $this->migrate($link, $target);
            File::deleteDirectory($link);
            $this->info("Migrated {$moved} stray file(s) and removed the real dir at {$link}");
        } elseif (is_link($link)) {
            @unlink($link); // a symlink pointing at the wrong target
        }

        // 4) (Re)create the symlink.
        @symlink($target, $link);
        if (is_link($link)) {
            $this->info("Linked {$link} -> {$target}");
            return self::SUCCESS;
        }

        $this->error("Failed to create symlink {$link} -> {$target}");
        return self::FAILURE;
    }

    /** Recursively move files from $from into $to without overwriting existing files. Returns count moved. */
    private function migrate(string $from, string $to): int
    {
        $moved = 0;
        foreach (File::allFiles($from) as $file) {
            $dest = $to . DIRECTORY_SEPARATOR . $file->getRelativePathname();
            if (!is_dir(dirname($dest))) {
                File::makeDirectory(dirname($dest), 0755, true);
            }
            if (!file_exists($dest)) {
                File::move($file->getPathname(), $dest);
                $moved++;
            }
        }
        return $moved;
    }
}
