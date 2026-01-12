<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$ignoredDirectories = ['.git', 'node_modules', 'vendor', 'test-results'];
$allowedDebugFiles = [
    'tools/debug-active-modules.php',
    'tools/zz-debug-workday-sets.php',
];
$debugPatterns = ['debug-*.php', 'zz-debug-*.php'];
$docsTempPatterns = ['~$*.rtf', '~*.tmp'];

$directory = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
$filter = new RecursiveCallbackFilterIterator(
    $directory,
    static function (SplFileInfo $current) use ($root, $ignoredDirectories) {
        if ($current->isDir()) {
            $relative = substr($current->getPathname(), strlen($root) + 1);
            foreach ($ignoredDirectories as $ignored) {
                if ($relative === $ignored || strncmp($relative, $ignored . DIRECTORY_SEPARATOR, strlen($ignored) + 1) === 0) {
                    return false;
                }
            }
        }

        return true;
    }
);

$iterator = new RecursiveIteratorIterator($filter);
$issues = [];

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $relativePath = substr($fileInfo->getPathname(), strlen($root) + 1);
    $relativePath = str_replace('\\', '/', $relativePath);
    $basename = $fileInfo->getBasename();

    foreach ($debugPatterns as $pattern) {
        if (fnmatch($pattern, $basename, FNM_CASEFOLD)) {
            if (!in_array($relativePath, $allowedDebugFiles, true)) {
                $issues[] = "Debug-Hilfsdatei gefunden: {$relativePath}";
            }
            continue 2;
        }
    }

    if (strncmp($relativePath, 'docs/', 5) === 0) {
        foreach ($docsTempPatterns as $pattern) {
            if (fnmatch($pattern, $basename, FNM_CASEFOLD)) {
                $issues[] = "Temporäre Office-Datei gefunden: {$relativePath}";
                break;
            }
        }
    }
}

if (!empty($issues)) {
    fwrite(STDERR, "\nDebug- oder Temp-Dateien gefunden:\n" . implode("\n", $issues) . "\n\n");
    exit(1);
}

fwrite(STDOUT, "Keine Debug- oder temporären Dateien gefunden.\n");
