<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\Extension;

use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;

\defined('_JEXEC') or die;

/**
 * Component class for com_adrianjdownloads.
 */
class AdrianjdownloadsComponent extends MVCComponent implements CategoryServiceInterface, RouterServiceInterface
{
    use CategoryServiceTrait;
    use RouterServiceTrait;

    protected function getTableNameForSection(?string $section = null)
    {
        return $section === 'category' ? 'categories' : 'adrianjdownloads_files';
    }

    protected function getStateColumnForSection(?string $section = null)
    {
        return 'state';
    }
}
