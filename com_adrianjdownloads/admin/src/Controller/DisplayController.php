<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or die;

/**
 * Default administrator controller.
 */
class DisplayController extends BaseController
{
    protected $default_view = 'downloads';

    public function display($cachable = false, $urlparams = [])
    {
        $view   = $this->input->get('view', 'downloads');
        $layout = $this->input->get('layout', 'default');
        $id     = $this->input->getInt('id');

        // Block direct access to the edit form without going through the controller.
        if ($view === 'download' && $layout === 'edit' && !$this->checkEditId('com_adrianjdownloads.edit.download', $id)) {
            if (!\count($this->app->getMessageQueue())) {
                $this->setMessage(\Joomla\CMS\Language\Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            }

            $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_adrianjdownloads&view=downloads', false));

            return false;
        }

        return parent::display($cachable, $urlparams);
    }
}
