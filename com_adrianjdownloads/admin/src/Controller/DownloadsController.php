<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;

\defined('_JEXEC') or die;

/**
 * Controller for the list of downloads (publish, delete, ordering...).
 */
class DownloadsController extends AdminController
{
    protected $text_prefix = 'COM_ADRIANJDOWNLOADS';

    public function getModel($name = 'Download', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
