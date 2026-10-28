<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\Service;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\DatabaseHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * SEF router: /menu/category/subcategory/12-download-alias
 */
class Router extends RouterView
{
    private CategoryFactoryInterface $categoryFactory;

    private DatabaseInterface $db;

    private array $categoryCache = [];

    public function __construct(SiteApplication $app, AbstractMenu $menu, CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
    {
        $this->categoryFactory = $categoryFactory;
        $this->db              = $db;

        $categories = new RouterViewConfiguration('categories');
        $categories->setKey('id');
        $this->registerView($categories);

        $category = new RouterViewConfiguration('category');
        $category->setKey('id')->setParent($categories, 'catid')->setNestable();
        $this->registerView($category);

        $download = new RouterViewConfiguration('download');
        $download->setKey('id')->setParent($category, 'catid');
        $this->registerView($download);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    public function getCategorySegment($id, $query)
    {
        $category = $this->getCategories()->get($id);

        if ($category) {
            $path    = array_reverse($category->getPath(), true);
            $path[0] = '1:root';

            return $path;
        }

        return [];
    }

    public function getCategoriesSegment($id, $query)
    {
        return $this->getCategorySegment($id, $query);
    }

    public function getDownloadSegment($id, $query)
    {
        if (!strpos((string) $id, ':')) {
            $id      = (int) $id;
            $dbquery = DatabaseHelper::query($this->db)
                ->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__adrianjdownloads_files'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($dbquery);

            $id .= ':' . $this->db->loadResult();
        }

        return [(int) $id => $id];
    }

    public function getCategoryId($segment, $query)
    {
        if (isset($query['id'])) {
            $category = $this->getCategories(['access' => false])->get($query['id']);

            if ($category) {
                foreach ($category->getChildren() as $child) {
                    if ($child->id == (int) $segment) {
                        return $child->id;
                    }
                }
            }
        }

        return false;
    }

    public function getCategoriesId($segment, $query)
    {
        return $this->getCategoryId($segment, $query);
    }

    public function getDownloadId($segment, $query)
    {
        return (int) $segment;
    }

    private function getCategories(array $options = []): CategoryInterface
    {
        $key = serialize($options);

        if (!isset($this->categoryCache[$key])) {
            $this->categoryCache[$key] = $this->categoryFactory->createCategory($options);
        }

        return $this->categoryCache[$key];
    }
}
