<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Dossier;

/**
 * Actions that are logged when accessing a dossier.
 *
 * Every access must be logged for GeBüV compliance
 * (revisionssichere Archivierung).
 */
enum DossierAccessAction: string
{
    case VIEW = 'view';
    case DOWNLOAD = 'download';
    case UPLOAD = 'upload';
    case DELETE = 'delete';
    case CLOSE = 'close';
    case ARCHIVE = 'archive';
    case EXPORT = 'export';
    case PRINT = 'print';
}
