<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\Service;

use Joomla\CMS\Categories\Categories;

\defined('_JEXEC') or die;

/**
 * Category tree of the component.
 */
class Category extends Categories
{
    public function __construct($options = [])
    {
        $options['table']      = '#__adrianjdownloads_files';
        $options['extension']  = 'com_adrianjdownloads';
        $options['statefield'] = 'state';

        parent::__construct($options);
    }
}
