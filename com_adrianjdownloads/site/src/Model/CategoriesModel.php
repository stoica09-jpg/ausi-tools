<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

\defined('_JEXEC') or die;

/**
 * Tree of categories shown on the "Categories" page.
 */
class CategoriesModel extends BaseDatabaseModel
{
    protected function populateState()
    {
        $app = Factory::getApplication();

        $this->setState('filter.parentId', $app->getInput()->getInt('id', 0) ?: 'root');
        $this->setState('params', $app->getParams());
    }

    public function getParent()
    {
        return $this->getCategoryTree()->get($this->getState('filter.parentId'));
    }

    /**
     * @return  \Joomla\CMS\Categories\CategoryNode[]
     */
    public function getItems(): array
    {
        $parent = $this->getParent();

        return $parent ? $parent->getChildren() : [];
    }

    private function getCategoryTree()
    {
        return Factory::getApplication()
            ->bootComponent('com_adrianjdownloads')
            ->getCategory(['access' => true, 'published' => 1, 'countItems' => 1]);
    }
}
