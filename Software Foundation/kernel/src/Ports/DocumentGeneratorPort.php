<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

interface DocumentGeneratorPort
{
    /**
     * Generate a document from a template.
     *
     * @param string $templateId Template identifier
     * @param array  $data       Data to populate the template with
     * @param string $format     Output format ('pdf', 'pdf_a', 'html', 'csv', 'xml')
     * @return string File path or content of the generated document
     */
    public function generate(string $templateId, array $data, string $format = 'pdf'): string;

    /**
     * Generate a PDF/A-1b document for archival purposes.
     *
     * @param string $templateId Template identifier
     * @param array  $data       Data to populate the template with
     * @return string File path or content of the generated PDF/A document
     */
    public function generatePdfA(string $templateId, array $data): string;

    /**
     * Get the list of supported output formats.
     *
     * @return string[] Supported format identifiers
     */
    public function supportedFormats(): array;
}
