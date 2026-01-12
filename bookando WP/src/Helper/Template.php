<?php

namespace Bookando\Helper;

use RuntimeException;

class Template
{
    /** @var callable */
    private $getOption;

    /** @var callable */
    private $filterInput;

    /** @var callable */
    private $sanitizeTextField;

    /** @var callable */
    private $sanitizeFileName;

    /** @var callable */
    private $fileExists;

    /** @var callable */
    private $includeFile;

    /** @var callable|null */
    private $logger;

    /** @var callable */
    private $stylesheetDirectoryProvider;

    private string $pluginDir;

    private string $modulesDir;

    /** @var array<string, list<string>> */
    private array $templateCache = [];

    /** @var list<string>|null */
    private ?array $availableModules = null;

    public function __construct(
        string $pluginDir,
        callable $stylesheetDirectoryProvider,
        callable $getOption,
        callable $filterInput,
        callable $sanitizeTextField,
        callable $sanitizeFileName,
        callable $fileExists,
        callable $includeFile,
        ?callable $logger = null
    ) {
        $this->pluginDir                    = rtrim($pluginDir, "\\/\0");
        $this->modulesDir                   = $this->pluginDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'modules';
        $this->stylesheetDirectoryProvider  = $stylesheetDirectoryProvider;
        $this->getOption                    = $getOption;
        $this->filterInput                  = $filterInput;
        $this->sanitizeTextField            = $sanitizeTextField;
        $this->sanitizeFileName             = $sanitizeFileName;
        $this->fileExists                   = $fileExists;
        $this->includeFile                  = $includeFile;
        $this->logger                       = $logger;
    }

    public function shouldUseFallback(): bool
    {
        $optionValue = ($this->getOption)('bookando_fallback_mode');
        if (!empty($optionValue)) {
            return true;
        }

        $fallbackRaw = ($this->filterInput)(INPUT_GET, 'fallback', FILTER_UNSAFE_RAW);
        if ($fallbackRaw === null || $fallbackRaw === false) {
            return false;
        }

        if (is_array($fallbackRaw)) {
            $fallbackRaw = reset($fallbackRaw) ?: '';
        }

        $fallback = ($this->sanitizeTextField)($fallbackRaw);

        if ($fallback === '' || $fallback === '0') {
            return false;
        }

        return true;
    }

    public function render(string $module, string $template): void
    {
        $normalizedModule   = $this->normalizeSegment($module);
        $normalizedTemplate = $this->normalizeSegment($template);

        if ($normalizedModule === '' || $normalizedTemplate === '') {
            $this->log('Invalid template identifiers provided', [
                'module'   => $module,
                'template' => $template,
            ]);
            return;
        }

        if (!in_array($normalizedModule, $this->getAvailableModules(), true)) {
            $this->log('Module not whitelisted for template rendering', [
                'module' => $normalizedModule,
            ]);
            return;
        }

        if (!in_array($normalizedTemplate, $this->getTemplatesForModule($normalizedModule), true)) {
            $this->log('Template not whitelisted for rendering', [
                'module'   => $normalizedModule,
                'template' => $normalizedTemplate,
            ]);
            return;
        }

        $paths = $this->candidatePaths($normalizedModule, $normalizedTemplate);
        foreach ($paths as $path) {
            if (($this->fileExists)($path)) {
                ($this->includeFile)($path);
                return;
            }
        }

        $this->log('Template file not found in any allowed path', [
            'module'   => $normalizedModule,
            'template' => $normalizedTemplate,
            'paths'    => $paths,
        ]);
    }

    /**
     * @return list<string>
     */
    private function getAvailableModules(): array
    {
        if ($this->availableModules !== null) {
            return $this->availableModules;
        }

        $modules = $this->listDirectories($this->modulesDir);

        $stylesheetDir = $this->getStylesheetDirectory();
        if ($stylesheetDir !== '') {
            $themeModulesDir = $this->joinPaths($stylesheetDir, 'bookando');
            if (is_dir($themeModulesDir)) {
                $modules = array_merge($modules, $this->listDirectories($themeModulesDir));
            }
        }

        $modules = array_values(array_unique($modules));
        sort($modules);

        $this->availableModules = $modules;

        return $this->availableModules;
    }

    /**
     * @return list<string>
     */
    private function getTemplatesForModule(string $module): array
    {
        if (isset($this->templateCache[$module])) {
            return $this->templateCache[$module];
        }

        $templates = [];

        foreach (['Templates', 'templates'] as $directory) {
            $templatesDir = $this->joinPaths($this->modulesDir, $module, $directory);
            if (is_dir($templatesDir)) {
                $templates = array_merge($templates, $this->listTemplateFiles($templatesDir));
            }
        }

        $stylesheetDir = $this->getStylesheetDirectory();
        if ($stylesheetDir !== '') {
            $themeTemplatesDir = $this->joinPaths($stylesheetDir, 'bookando', $module);
            if (is_dir($themeTemplatesDir)) {
                $templates = array_merge($templates, $this->listTemplateFiles($themeTemplatesDir));
            }
        }

        $templates = array_values(array_unique($templates));
        sort($templates);

        $this->templateCache[$module] = $templates;

        return $this->templateCache[$module];
    }

    /**
     * @return list<string>
     */
    private function listDirectories(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $directories = [];
        $handle      = opendir($path);
        if ($handle === false) {
            return [];
        }

        try {
            while (($entry = readdir($handle)) !== false) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $fullPath = $path . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($fullPath)) {
                    $normalized = $this->normalizeSegment($entry);
                    if ($normalized !== '') {
                        $directories[] = $normalized;
                    }
                }
            }
        } finally {
            closedir($handle);
        }

        return $directories;
    }

    /**
     * @return list<string>
     */
    private function listTemplateFiles(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $files  = [];
        $handle = opendir($path);
        if ($handle === false) {
            return [];
        }

        try {
            while (($entry = readdir($handle)) !== false) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $fullPath = $path . DIRECTORY_SEPARATOR . $entry;
                if (!is_file($fullPath)) {
                    continue;
                }

                if (!str_ends_with($entry, '.php')) {
                    continue;
                }

                $name       = pathinfo($entry, PATHINFO_FILENAME);
                $normalized = $this->normalizeSegment($name);
                if ($normalized !== '') {
                    $files[] = $normalized;
                }
            }
        } finally {
            closedir($handle);
        }

        return $files;
    }

    private function normalizeSegment(string $value): string
    {
        $sanitized = ($this->sanitizeFileName)($value);
        $sanitized = trim((string) $sanitized);

        return strtolower($sanitized);
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(string $module, string $template): array
    {
        $filename = $template . '.php';

        $paths = [];

        $themeDir = $this->getStylesheetDirectory();
        if ($themeDir !== '') {
            $paths[] = $this->joinPaths($themeDir, 'bookando', $module, $filename);
        }

        $paths[] = $this->joinPaths($this->modulesDir, $module, 'Templates', $filename);
        $paths[] = $this->joinPaths($this->modulesDir, $module, 'templates', $filename);

        return $paths;
    }

    private function joinPaths(string ...$segments): string
    {
        $path = '';

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            if ($path === '') {
                $path = rtrim($segment, "\\/\0");
                continue;
            }

            $path .= DIRECTORY_SEPARATOR . trim($segment, "\\/\0");
        }

        return $path;
    }

    private function getStylesheetDirectory(): string
    {
        $path = ($this->stylesheetDirectoryProvider)();
        if (!is_string($path)) {
            throw new RuntimeException('Stylesheet directory provider must return string path.');
        }

        return rtrim($path, "\\/\0");
    }

    private function log(string $message, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        $contextString = '';
        if ($context !== []) {
            $encoder = function_exists('wp_json_encode') ? 'wp_json_encode' : 'json_encode';
            $encoded = $encoder($context);
            if (is_string($encoded)) {
                $contextString = ' ' . $encoded;
            }
        }

        ($this->logger)($message . $contextString);
    }
}
