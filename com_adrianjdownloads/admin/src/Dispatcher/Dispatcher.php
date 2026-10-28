<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\Dispatcher;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Dispatcher\ComponentDispatcher;

\defined('_JEXEC') or die;

/**
 * Lets article authors open the "insert file" window from the editor button
 * even when they cannot manage the downloads themselves.
 */
class Dispatcher extends ComponentDispatcher
{
    protected function checkAccess()
    {
        if ($this->isSelectModal()) {
            $user = $this->app->getIdentity();

            if (
                $user->authorise('core.manage', $this->option)
                || $user->authorise('core.create', 'com_content')
                || $user->authorise('core.edit', 'com_content')
                || $user->authorise('core.edit.own', 'com_content')
            ) {
                return;
            }

            throw new NotAllowed($this->app->getLanguage()->_('JERROR_ALERTNOAUTHOR'), 403);
        }

        parent::checkAccess();
    }

    private function isSelectModal(): bool
    {
        $task = $this->input->getCmd('task', '');

        return ($task === '' || $task === 'display')
            && $this->input->getCmd('view') === 'downloads'
            && $this->input->getCmd('layout') === 'modal';
    }
}
