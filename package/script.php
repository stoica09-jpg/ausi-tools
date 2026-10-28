<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Package script: switches on the two plugins after installation.
 */
class Pkg_AdrianjdownloadsInstallerScript
{
    public function preflight($type, $parent)
    {
        if ($type !== 'uninstall' && version_compare(JVERSION, '4.4.0', '<')) {
            Factory::getApplication()->enqueueMessage('Adrian jDownloads requires Joomla 4.4 or newer.', 'error');

            return false;
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        if ($type !== 'install' && $type !== 'update' && $type !== 'discover_install') {
            return true;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('adrianjdownloads'))
            ->whereIn($db->quoteName('folder'), ['content', 'editors-xtd'], \Joomla\Database\ParameterType::STRING);

        $db->setQuery($query)->execute();

        return true;
    }
}
