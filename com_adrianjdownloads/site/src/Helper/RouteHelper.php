<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\Helper;

\defined('_JEXEC') or die;

/**
 * Builds the non-SEF links of the component.
 */
abstract class RouteHelper
{
    public static function getCategoryRoute($catid, $language = null): string
    {
        $link = 'index.php?option=com_adrianjdownloads&view=category&id=' . $catid;

        if ($language && $language !== '*') {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

    public static function getDownloadRoute($slug, $catid, $language = null): string
    {
        $link = 'index.php?option=com_adrianjdownloads&view=download&id=' . $slug;

        if ((int) $catid > 1) {
            $link .= '&catid=' . (int) $catid;
        }

        if ($language && $language !== '*') {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

    /**
     * Link that sends the file. It points to the details page with a task,
     * so the SEF router keeps the menu path: /menu/category/12-alias?task=download.send
     */
    public static function getFileRoute($slug, $catid = 0, $language = null): string
    {
        return self::getDownloadRoute($slug, $catid, $language) . '&task=download.send';
    }
}
