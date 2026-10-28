<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\View\Categories;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

/**
 * Categories page.
 */
class HtmlView extends BaseHtmlView
{
    protected $items = [];

    protected $parent;

    protected $params;

    public function display($tpl = null)
    {
        $model        = $this->getModel();
        $this->items  = $model->getItems();
        $this->parent = $model->getParent();
        $this->params = $model->getState('params');

        if (!$this->parent) {
            throw new \Exception(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'), 404);
        }

        $app    = Factory::getApplication();
        $active = $app->getMenu()->getActive();

        $this->params->def('page_heading', $active ? $this->params->get('page_title', $active->title) : Text::_('COM_ADRIANJDOWNLOADS_CATEGORIES'));
        $this->setDocumentTitle($this->params->get('page_title', $this->params->get('page_heading')));

        $app->getDocument()->getWebAssetManager()->getRegistry()->addExtensionRegistryFile('com_adrianjdownloads');
        $app->getDocument()->getWebAssetManager()->useStyle('com_adrianjdownloads.site');

        parent::display($tpl);
    }
}
