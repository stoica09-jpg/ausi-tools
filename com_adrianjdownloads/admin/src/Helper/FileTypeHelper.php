<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\Helper;

use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

/**
 * File type icons (inline SVG, so they do not depend on the site template).
 */
abstract class FileTypeHelper
{
    /**
     * Type groups: extensions => [group, colour].
     */
    private const GROUPS = [
        'pdf'   => [['pdf'], '#d32f2f'],
        'word'  => [['doc', 'docx', 'odt', 'rtf'], '#1e5bb8'],
        'excel' => [['xls', 'xlsx', 'ods', 'csv'], '#1d7044'],
        'ppt'   => [['ppt', 'pptx', 'odp', 'pps', 'ppsx'], '#c4501b'],
        'zip'   => [['zip', 'rar', '7z', 'gz', 'tar', 'tgz', 'bz2'], '#8a6100'],
        'image' => [['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tif', 'tiff'], '#7b3fb0'],
        'audio' => [['mp3', 'wav', 'ogg', 'm4a', 'flac'], '#00796b'],
        'video' => [['mp4', 'avi', 'mov', 'webm', 'mkv', 'mpg', 'mpeg'], '#ad1457'],
        'text'  => [['txt', 'md', 'xml', 'json'], '#546e7a'],
    ];

    /**
     * @return  array{group: string, label: string, color: string, ext: string}
     */
    public static function getType(?string $fileName, ?string $externalUrl = ''): array
    {
        $fileName = (string) $fileName;

        if ($fileName === '' && $externalUrl) {
            $path     = (string) parse_url($externalUrl, PHP_URL_PATH);
            $ext      = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $type     = self::fromExtension($ext);

            // A link without a known file type gets the "link" icon.
            return $type['group'] === 'file' ? ['group' => 'link', 'label' => 'URL', 'color' => '#455a64', 'ext' => ''] : $type;
        }

        return self::fromExtension(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)));
    }

    private static function fromExtension(string $ext): array
    {
        $label = $ext !== '' ? strtoupper(substr($ext, 0, 4)) : 'FILE';

        foreach (self::GROUPS as $group => [$extensions, $color]) {
            if (\in_array($ext, $extensions, true)) {
                return ['group' => $group, 'label' => $label, 'color' => $color, 'ext' => $ext];
            }
        }

        return ['group' => 'file', 'label' => $label, 'color' => '#607d8b', 'ext' => $ext];
    }

    /**
     * Inline SVG icon with an accessible name, e.g. "Document PDF (pdf)".
     */
    public static function icon(?string $fileName, ?string $externalUrl = '', string $class = ''): string
    {
        $type  = self::getType($fileName, $externalUrl);
        $name  = Text::_('COM_ADRIANJDOWNLOADS_TYPE_' . strtoupper($type['group']));
        $name .= $type['ext'] !== '' ? ' (.' . $type['ext'] . ')' : '';
        $name  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $font  = \strlen($type['label']) > 3 ? '6.4' : '7.6';
        $color = $type['color'];

        return '<svg class="adrianjd-ficon adrianjd-ficon-' . $type['group'] . ($class ? ' ' . $class : '') . '"'
            . ' width="28" height="34" viewBox="0 0 28 34" role="img" aria-label="' . $name . '" focusable="false">'
            . '<title>' . $name . '</title>'
            . '<path d="M3 .75h15.5l8.75 8.75V31A2.25 2.25 0 0 1 25 33.25H3A2.25 2.25 0 0 1 .75 31V3A2.25 2.25 0 0 1 3 .75z" fill="#fff" stroke="' . $color . '" stroke-width="1.5"/>'
            . '<path d="M18.5.75V7.5a2 2 0 0 0 2 2h6.75" fill="none" stroke="' . $color . '" stroke-width="1.5"/>'
            . '<rect x="0" y="16.5" width="24" height="11" rx="1.5" fill="' . $color . '"/>'
            . '<text x="12" y="24.6" font-family="Arial, Helvetica, sans-serif" font-size="' . $font . '" font-weight="700" fill="#fff" text-anchor="middle">'
            . htmlspecialchars($type['label'], ENT_QUOTES, 'UTF-8') . '</text>'
            . '</svg>';
    }
}
