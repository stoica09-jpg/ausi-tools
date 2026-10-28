<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\Model;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\DatabaseHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * One download (details page and file sending).
 */
class DownloadModel extends ItemModel
{
    protected $_context = 'com_adrianjdownloads.download';

    protected function populateState()
    {
        $app = Factory::getApplication();

        $this->setState('download.id', $app->getInput()->getInt('id'));
        $this->setState('params', $app->getParams());
    }

    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState('download.id'));

        if (isset($this->_item[$pk])) {
            return $this->_item[$pk];
        }

        $db     = $this->getDatabase();
        $levels = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();
        $now    = Factory::getDate()->toSql();

        $query = DatabaseHelper::query($db)
            ->select('a.*')
            ->select(
                [
                    $db->quoteName('c.title', 'category_title'),
                    $db->quoteName('c.alias', 'category_alias'),
                    $db->quoteName('c.access', 'category_access'),
                    $db->quoteName('u.name', 'created_by_name'),
                ]
            )
            ->from($db->quoteName('#__adrianjdownloads_files', 'a'))
            ->join('INNER', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'))
            ->where($db->quoteName('a.id') . ' = :id')
            ->where($db->quoteName('a.state') . ' = 1')
            ->where($db->quoteName('c.published') . ' = 1')
            ->whereIn($db->quoteName('a.access'), $levels)
            ->whereIn($db->quoteName('c.access'), $levels)
            ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :now1)')
            ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' >= :now2)')
            ->bind(':id', $pk, ParameterType::INTEGER)
            ->bind([':now1', ':now2'], $now);

        $item = $db->setQuery($query)->loadObject();

        if (!$item) {
            throw new \Exception(Text::_('COM_ADRIANJDOWNLOADS_ERROR_DOWNLOAD_NOT_FOUND'), 404);
        }

        $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

        $this->_item[$pk] = $item;

        return $item;
    }

    public function hit($pk = 0): void
    {
        $this->increment((int) ($pk ?: $this->getState('download.id')), 'hits');
    }

    public function countDownload(int $pk): void
    {
        $this->increment($pk, 'downloads');
    }

    private function increment(int $pk, string $column): void
    {
        $db    = $this->getDatabase();
        $query = DatabaseHelper::query($db)
            ->update($db->quoteName('#__adrianjdownloads_files'))
            ->set($db->quoteName($column) . ' = ' . $db->quoteName($column) . ' + 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $pk, ParameterType::INTEGER);

        $db->setQuery($query)->execute();
    }
}
