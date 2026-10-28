<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or die;

/**
 * Default site controller.
 */
class DisplayController extends BaseController
{
    protected $default_view = 'categories';

    public function display($cachable = false, $urlparams = [])
    {
        $cachable = $this->app->getIdentity()->guest;

        $safeurlparams = [
            'id'               => 'INT',
            'catid'            => 'INT',
            'limit'            => 'UINT',
            'limitstart'       => 'UINT',
            'filter-search'    => 'STRING',
            'lang'             => 'CMD',
            'Itemid'           => 'INT',
        ];

        return parent::display($cachable, $safeurlparams);
    }
}
