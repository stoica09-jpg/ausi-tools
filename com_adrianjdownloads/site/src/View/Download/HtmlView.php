<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\View\Download;

use Adrian\Component\Adrianjdownloads\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Details page of a download.
 */
class HtmlView extends BaseHtmlView
{
    protected $item;

    protected $params;

    protected $canDownload = false;

    public function display($tpl = null)
    {
        $model        = $this->getModel();
        $this->item   = $model->getItem();
        $this->params = $model->getState('params');

        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        $this->canDownload = !$this->item->require_login || !$user->guest;

        $model->hit($this->item->id);

        $this->item->description = HTMLHelper::_('content.prepare', (string) $this->item->description, '', 'com_adrianjdownloads.download');

        $active   = $app->getMenu()->getActive();
        $isMenuDl = $active
            && ($active->query['view'] ?? '') === 'download'
            && (int) ($active->query['id'] ?? 0) === (int) $this->item->id;

        if ($isMenuDl) {
            $this->params->def('page_heading', $this->params->get('page_title', $active->title));
        } else {
            $this->params->set('page_heading', $this->item->title);

            $menuCat = ($active && ($active->query['view'] ?? '') === 'category') ? (int) ($active->query['id'] ?? 0) : 0;

            if ($menuCat !== (int) $this->item->catid) {
                $category = $app->bootComponent('com_adrianjdownloads')->getCategory()->get($this->item->catid);
                $path     = [];

                while ($category && $category->id !== 'root' && (int) $category->id !== $menuCat) {
                    $path[]   = [$category->title, RouteHelper::getCategoryRoute($category->id)];
                    $category = $category->getParent();
                }

                foreach (array_reverse($path) as [$title, $link]) {
                    $app->getPathway()->addItem($title, Route::_($link));
                }
            }

            $app->getPathway()->addItem($this->item->title);
        }

        $this->setDocumentTitle($isMenuDl ? $this->params->get('page_title', $active->title) : $this->item->title);

        $app->getDocument()->getWebAssetManager()->getRegistry()->addExtensionRegistryFile('com_adrianjdownloads');
        $app->getDocument()->getWebAssetManager()->useStyle('com_adrianjdownloads.site');

        parent::display($tpl);
    }
}
