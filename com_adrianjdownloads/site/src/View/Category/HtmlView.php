<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\View\Category;

use Adrian\Component\Adrianjdownloads\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Category page: sub-categories and the list of downloads.
 */
class HtmlView extends BaseHtmlView
{
    protected $category;

    protected $children = [];

    protected $items = [];

    protected $pagination;

    protected $params;

    protected $state;

    public function display($tpl = null)
    {
        $model            = $this->getModel();
        $this->category   = $model->getCategory();
        $this->children   = $model->getChildren();
        $this->items      = $model->getItems();
        $this->pagination = $model->getPagination();
        $this->state      = $model->getState();
        $this->params     = $this->state->get('params');

        $app    = Factory::getApplication();
        $active = $app->getMenu()->getActive();

        // Is the menu item pointing exactly to this category?
        $isMenuCategory = $active
            && ($active->query['option'] ?? '') === 'com_adrianjdownloads'
            && ($active->query['view'] ?? '') === 'category'
            && (int) ($active->query['id'] ?? 0) === (int) $this->category->id;

        if ($isMenuCategory) {
            $this->params->def('page_heading', $this->params->get('page_title', $active->title));
            $title = $this->params->get('page_title', $active->title);
        } else {
            $this->params->set('page_heading', $this->category->title);
            $title = $this->category->title;

            // Breadcrumbs for the categories below the menu item.
            $pathway  = $app->getPathway();
            $path     = [];
            $category = $this->category;
            $menuCat  = (int) ($active->query['id'] ?? 0);

            while ($category && $category->id !== 'root' && (int) $category->id !== $menuCat) {
                $path[]   = ['title' => $category->title, 'link' => RouteHelper::getCategoryRoute($category->id)];
                $category = $category->getParent();
            }

            foreach (array_reverse($path) as $item) {
                $pathway->addItem($item['title'], Route::_($item['link']));
            }
        }

        $this->setDocumentTitle($title);

        if ($this->category->metadesc) {
            $app->getDocument()->setDescription($this->category->metadesc);
        }

        $app->getDocument()->getWebAssetManager()->getRegistry()->addExtensionRegistryFile('com_adrianjdownloads');
        $app->getDocument()->getWebAssetManager()->useStyle('com_adrianjdownloads.site');

        parent::display($tpl);
    }
}
