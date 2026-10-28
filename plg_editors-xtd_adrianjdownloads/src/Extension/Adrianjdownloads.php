<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Plugin\EditorsXtd\Adrianjdownloads\Extension;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Editor button "Fișier (Adrian jDownloads)": opens a window where a file or a
 * whole category is chosen and inserted in the article as {adrianjd ...}.
 *
 * It uses the onDisplay() button API, which works on Joomla 4 and Joomla 5.
 */
final class Adrianjdownloads extends CMSPlugin
{
    protected $autoloadLanguage = true;

    public function onDisplay($name, $asset = 0, $author = 0)
    {
        $app = $this->getApplication();

        // The selection window lives in the administrator.
        if (!$app->isClient('administrator')) {
            return null;
        }

        $user = $app->getIdentity();

        if (
            !$user->authorise('core.manage', 'com_adrianjdownloads')
            && !$user->authorise('core.create', 'com_content')
            && !$user->authorise('core.edit', 'com_content')
            && !$user->authorise('core.edit.own', 'com_content')
        ) {
            return null;
        }

        $link = 'index.php?option=com_adrianjdownloads&view=downloads&layout=modal&tmpl=component&editor='
            . rawurlencode((string) $name) . '&' . Session::getFormToken() . '=1';

        $props = [
            // Joomla 5 dialog
            'action'  => 'modal',
            // Joomla 4 Bootstrap modal
            'modal'   => true,
            'link'    => $link,
            'text'    => Text::_('PLG_EDITORS-XTD_ADRIANJDOWNLOADS_BUTTON'),
            'name'    => $this->_type . '_' . $this->_name,
            'icon'    => 'download',
            'iconSVG' => '<svg viewBox="0 0 512 512" width="24" height="24"><path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32V274.7l-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L288 274.7V32zM64 352c-35.3 0-64 28.7-64 64v32c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V416c0-35.3-28.7-64-64-64H346.5l-45.3 45.3c-25 25-65.5 25-90.5 0L165.5 352H64zm368 56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/></svg>',
            'options' => [
                'height'     => '520px',
                'width'      => '900px',
                'bodyHeight' => '70',
                'modalWidth' => '80',
            ],
        ];

        return class_exists(CMSObject::class) ? new CMSObject($props) : new Registry($props);
    }
}
