<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\Model;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\DatabaseHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Downloads of one category (with search and pagination).
 */
class CategoryModel extends ListModel
{
    private $category;

    protected function populateState($ordering = null, $direction = null)
    {
        $app    = Factory::getApplication();
        $input  = $app->getInput();
        $params = $app->getParams();

        $this->setState('category.id', $input->getInt('id'));
        $this->setState('params', $params);

        $limit = (int) $app->getUserStateFromRequest('com_adrianjdownloads.category.list.' . $input->getInt('id') . '.limit', 'limit', (int) $params->get('list_limit', 20), 'uint');
        $this->setState('list.limit', $limit);
        $this->setState('list.start', $input->getUint('limitstart', 0));

        $this->setState('filter.search', trim($input->getString('filter-search', '')));
        $this->setState('filter.language', Multilanguage::isEnabled());
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('category.id');
        $id .= ':' . $this->getState('filter.search');

        return parent::getStoreId($id);
    }

    public function getCategory()
    {
        if ($this->category === null) {
            $categories = Factory::getApplication()
                ->bootComponent('com_adrianjdownloads')
                ->getCategory(['access' => true, 'published' => 1, 'countItems' => 1]);

            $this->category = $categories->get($this->getState('category.id'));

            if (!$this->category || $this->category->id === 'root') {
                throw new \Exception(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'), 404);
            }
        }

        return $this->category;
    }

    public function getChildren(): array
    {
        return $this->getCategory()->getChildren();
    }

    public function getItems()
    {
        $items = parent::getItems();

        foreach ($items ?: [] as $item) {
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
        }

        return $items;
    }

    protected function getListQuery()
    {
        $db     = $this->getDatabase();
        $user   = Factory::getApplication()->getIdentity();
        $levels = $user->getAuthorisedViewLevels();
        $catid  = (int) $this->getState('category.id');
        $now    = Factory::getDate()->toSql();
        $params = $this->getState('params');

        $query = DatabaseHelper::query($db)
            ->select('a.*')
            ->select($db->quoteName('c.title', 'category_title'))
            ->select($db->quoteName('c.alias', 'category_alias'));

        $query->from($db->quoteName('#__adrianjdownloads_files', 'a'))
            ->join('INNER', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->where($db->quoteName('a.catid') . ' = :catid')
            ->where($db->quoteName('a.state') . ' = 1')
            ->where($db->quoteName('c.published') . ' = 1')
            ->whereIn($db->quoteName('a.access'), $levels)
            ->whereIn($db->quoteName('c.access'), $levels)
            ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :now1)')
            ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' >= :now2)')
            ->bind(':catid', $catid, ParameterType::INTEGER)
            ->bind([':now1', ':now2'], $now);

        if ($this->getState('filter.language')) {
            $query->whereIn($db->quoteName('a.language'), [Factory::getApplication()->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('a.title') . ' LIKE :s1 OR ' . $db->quoteName('a.description') . ' LIKE :s2 OR '
                . $db->quoteName('a.author') . ' LIKE :s3 OR ' . $db->quoteName('a.file_original') . ' LIKE :s4)'
            )
                ->bind([':s1', ':s2', ':s3', ':s4'], $like);
        }

        switch ($params ? $params->get('list_ordering', 'ordering') : 'ordering') {
            case 'title':
                $query->order($db->quoteName('a.title') . ' ASC');
                break;
            case 'newest':
                $query->order($db->quoteName('a.created') . ' DESC');
                break;
            case 'oldest':
                $query->order($db->quoteName('a.created') . ' ASC');
                break;
            case 'downloads':
                $query->order($db->quoteName('a.downloads') . ' DESC');
                break;
            default:
                $query->order($db->quoteName('a.ordering') . ' ASC');
        }

        return $query;
    }
}
