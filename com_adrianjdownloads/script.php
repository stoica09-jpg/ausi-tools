<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * Installation script: checks the Joomla/PHP version and prepares the
 * protected folder where the uploaded files are stored.
 */
class Com_AdrianjdownloadsInstallerScript
{
    private string $minimumJoomla = '4.4.0';

    private string $minimumPhp = '8.1.0';

    public function preflight($type, $parent)
    {
        if ($type === 'uninstall') {
            return true;
        }

        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage('Adrian jDownloads requires PHP ' . $this->minimumPhp . ' or newer.', 'error');

            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage('Adrian jDownloads requires Joomla ' . $this->minimumJoomla . ' or newer.', 'error');

            return false;
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        if ($type !== 'install' && $type !== 'update' && $type !== 'discover_install') {
            return true;
        }

        $folder = JPATH_ROOT . '/adrianjdownloads_files';

        if (!is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }

        if (is_dir($folder)) {
            if (!is_file($folder . '/.htaccess')) {
                file_put_contents(
                    $folder . '/.htaccess',
                    "# Direct access is denied: files are served through the component.\n"
                    . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
                    . "<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n"
                );
            }

            if (!is_file($folder . '/index.html')) {
                file_put_contents($folder . '/index.html', '<!DOCTYPE html><title></title>');
            }
        }

        return true;
    }
}
