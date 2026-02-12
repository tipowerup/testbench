<?php

declare(strict_types=1);

/**
 * TI PowerUp Testbench — Smart Bootstrap
 *
 * Loads the CWD's vendor/autoload.php and detects the environment mode.
 *
 * Mode A (Host App): Parent TI project found (local dev inside a TI app).
 * Mode B (Standalone): No parent TI project (CI or independent dev).
 *
 * Both modes use Orchestra Testbench for app creation. The mode is set as an
 * env var for extensions that want to conditionally adjust test behavior.
 */
(function (): void {
    $cwd = getcwd();
    if ($cwd === false) {
        fwrite(STDERR, "TI Testbench: Unable to determine current working directory.\n");
        exit(1);
    }

    $autoloader = $cwd.'/vendor/autoload.php';
    if (!file_exists($autoloader)) {
        fwrite(STDERR, "TI Testbench: No vendor/autoload.php found in {$cwd}. Run composer install.\n");
        exit(1);
    }

    require $autoloader;

    // Walk up from CWD looking for a host TastyIgniter root.
    // A valid host root has both an `artisan` file and `vendor/tastyigniter/core/`.
    $findHostTiRoot = function (string $startDir): ?string {
        $dir = $startDir;
        $maxDepth = 10;
        $depth = 0;

        while ($depth < $maxDepth) {
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }

            $dir = $parent;
            $depth++;

            if (
                file_exists($dir.'/artisan')
                && is_dir($dir.'/vendor/tastyigniter/core')
            ) {
                return $dir;
            }
        }

        return null;
    };

    $hostRoot = $findHostTiRoot($cwd);

    if ($hostRoot !== null) {
        putenv('TI_TESTBENCH_MODE=host');
        $_ENV['TI_TESTBENCH_MODE'] = 'host';
        $_SERVER['TI_TESTBENCH_MODE'] = 'host';
        putenv("TI_TESTBENCH_HOST_ROOT={$hostRoot}");
        $_ENV['TI_TESTBENCH_HOST_ROOT'] = $hostRoot;
        $_SERVER['TI_TESTBENCH_HOST_ROOT'] = $hostRoot;
    } else {
        putenv('TI_TESTBENCH_MODE=standalone');
        $_ENV['TI_TESTBENCH_MODE'] = 'standalone';
        $_SERVER['TI_TESTBENCH_MODE'] = 'standalone';
    }
})();
