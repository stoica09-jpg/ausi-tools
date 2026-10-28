<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\Helper;

use Joomla\CMS\Component\ComponentHelper;

\defined('_JEXEC') or die;

/**
 * Helpers for the folder where uploaded files are kept.
 */
abstract class StorageHelper
{
    public const DEFAULT_FOLDER = 'adrianjdownloads_files';

    public const DEFAULT_EXTENSIONS = 'pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,txt,rtf,csv,zip,rar,7z,jpg,jpeg,png,gif,mp3,mp4';

    /**
     * Absolute path of the storage folder (created and protected if missing).
     */
    public static function getStoragePath(): string
    {
        $relative = (string) ComponentHelper::getParams('com_adrianjdownloads')->get('storage_path', self::DEFAULT_FOLDER);
        $relative = str_replace('\\', '/', $relative);
        $parts    = array_filter(explode('/', $relative), static fn ($p) => $p !== '' && $p !== '.' && $p !== '..');
        $relative = implode('/', $parts) ?: self::DEFAULT_FOLDER;
        $path     = JPATH_ROOT . '/' . $relative;

        self::protectFolder($path);

        return $path;
    }

    /**
     * Creates the folder and denies direct web access to it.
     */
    public static function protectFolder(string $path): void
    {
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        if (!is_dir($path)) {
            return;
        }

        if (!is_file($path . '/.htaccess')) {
            @file_put_contents(
                $path . '/.htaccess',
                "# Direct access is denied: files are served through the component.\n"
                . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n"
            );
        }

        if (!is_file($path . '/index.html')) {
            @file_put_contents($path . '/index.html', '<!DOCTYPE html><title></title>');
        }
    }

    /**
     * Full path of a stored file, or null when the name is unsafe or missing.
     */
    public static function getFilePath(string $fileName): ?string
    {
        if ($fileName === '' || basename($fileName) !== $fileName || $fileName[0] === '.') {
            return null;
        }

        $path = self::getStoragePath() . '/' . $fileName;

        return is_file($path) ? $path : null;
    }

    /**
     * @return  string[]  Lower-case allowed extensions.
     */
    public static function getAllowedExtensions(): array
    {
        $list = (string) ComponentHelper::getParams('com_adrianjdownloads')->get('allowed_extensions', self::DEFAULT_EXTENSIONS);

        return array_values(array_filter(array_map(static fn ($e) => strtolower(trim($e, " .\t\n\r")), explode(',', $list))));
    }

    public static function formatSize($bytes): string
    {
        $bytes = (float) $bytes;

        if ($bytes <= 0) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i     = (int) floor(log($bytes, 1024));
        $i     = min($i, \count($units) - 1);

        return round($bytes / (1024 ** $i), $i > 1 ? 2 : 0) . ' ' . $units[$i];
    }
}
