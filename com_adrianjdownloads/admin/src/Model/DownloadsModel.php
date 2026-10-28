<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\Model;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\DatabaseHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

/**
 * List of downloads in the administrator.
 */
class DownloadsModel extends ListModel
{
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'state', 'a.state',
                'access', 'a.access', 'access_level',
                'catid', 'a.catid', 'category_title',
                'language', 'a.language',
                'ordering', 'a.ordering',
                'created', 'a.created',
                'downloads', 'a.downloads',
                'file_size', 'a.file_size',
                'published', 'category_id', 'level',
            ];
        }

        parent::__construct($config, $factory);

        // The "insert file" window keeps its own filters.
        if (\Joomla\CMS\Factory::getApplication()->getInput()->getCmd('layout') === 'modal') {
            $this->context .= '.modal';
        }
    }

    protected function populateState($ordering = 'a.id', $direction = 'desc')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . serialize($this->getState('filter.category_id'));
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.language');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = DatabaseHelper::query($db);

        $query->select(
            $db->quoteName(
                [
                    'a.id', 'a.title', 'a.alias', 'a.catid', 'a.state', 'a.access', 'a.require_login',
                    'a.language', 'a.ordering', 'a.created', 'a.created_by', 'a.downloads', 'a.hits',
                    'a.file_name', 'a.file_original', 'a.file_size', 'a.external_url', 'a.version',
                    'a.checked_out', 'a.checked_out_time', 'a.publish_up', 'a.publish_down',
                ]
            )
        )
            ->select(
                [
                    $db->quoteName('c.title', 'category_title'),
                    $db->quoteName('ag.title', 'access_level'),
                    $db->quoteName('uc.name', 'editor'),
                    $db->quoteName('l.title', 'language_title'),
                    $db->quoteName('l.image', 'language_image'),
                ]
            )
            ->from($db->quoteName('#__adrianjdownloads_files', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'))
            ->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'))
            ->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

        $this->applyFilters($query);

        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = strtoupper($this->state->get('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        if ($orderCol === 'a.ordering' || $orderCol === 'category_title') {
            $query->order($db->escape('c.lft') . ' ' . $orderDirn)
                ->order($db->escape('a.ordering') . ' ' . $orderDirn);
        } else {
            $query->order($db->escape($orderCol) . ' ' . $orderDirn);
        }

        return $query;
    }

    private function applyFilters(QueryInterface $query): void
    {
        $db = $this->getDatabase();

        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->whereIn($db->quoteName('a.state'), [0, 1]);
        }

        $access = $this->getState('filter.access');

        if (is_numeric($access)) {
            $access = (int) $access;
            $query->where($db->quoteName('a.access') . ' = :access')
                ->bind(':access', $access, ParameterType::INTEGER);
        }

        $categoryIds = array_filter(array_map('intval', (array) $this->getState('filter.category_id')));

        if ($categoryIds) {
            // Include the sub-categories of the selected categories.
            $sub = DatabaseHelper::query($db)
                ->select($db->quoteName('sub.id'))
                ->from($db->quoteName('#__categories', 'sub'))
                ->join(
                    'INNER',
                    $db->quoteName('#__categories', 'this'),
                    $db->quoteName('sub.lft') . ' >= ' . $db->quoteName('this.lft')
                    . ' AND ' . $db->quoteName('sub.rgt') . ' <= ' . $db->quoteName('this.rgt')
                )
                ->where($db->quoteName('this.id') . ' IN (' . implode(',', $categoryIds) . ')');

            $query->where($db->quoteName('a.catid') . ' IN (' . (string) $sub . ')');
        }

        $language = (string) $this->getState('filter.language');

        if ($language !== '') {
            $query->where($db->quoteName('a.language') . ' = :language')
                ->bind(':language', $language);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                    ->bind(':id', $id, ParameterType::INTEGER);
            } else {
                $like = '%' . str_replace(' ', '%', $search) . '%';
                $query->where(
                    '(' . $db->quoteName('a.title') . ' LIKE :s1 OR ' . $db->quoteName('a.alias') . ' LIKE :s2 OR '
                    . $db->quoteName('a.file_original') . ' LIKE :s3)'
                )
                    ->bind([':s1', ':s2', ':s3'], $like);
            }
        }
    }
}
