<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

\defined('_JEXEC') or die;

/**
 * Table for #__adrianjdownloads_files.
 */
class DownloadTable extends Table
{
    protected $_supportNullValue = true;

    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_adrianjdownloads.download';

        parent::__construct('#__adrianjdownloads_files', 'id', $db, $dispatcher);

        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        $this->title = trim((string) $this->title);

        if ($this->title === '') {
            $this->setError(Text::_('COM_ADRIANJDOWNLOADS_ERROR_TITLE_REQUIRED'));

            return false;
        }

        if ((int) $this->catid <= 0) {
            $this->setError(Text::_('COM_ADRIANJDOWNLOADS_ERROR_CATEGORY_REQUIRED'));

            return false;
        }

        if (trim((string) $this->alias) === '') {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language ?: '*');

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        if (empty($this->publish_up)) {
            $this->publish_up = null;
        }

        if (empty($this->publish_down)) {
            $this->publish_down = null;
        }

        if ($this->publish_up && $this->publish_down && $this->publish_down < $this->publish_up) {
            [$this->publish_up, $this->publish_down] = [$this->publish_down, $this->publish_up];
        }

        if (empty($this->params)) {
            $this->params = '{}';
        }

        if ($this->description === null) {
            $this->description = '';
        }

        return true;
    }

    public function store($updateNulls = true)
    {
        $date = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();

        if ($this->id) {
            $this->modified    = $date;
            $this->modified_by = $user ? (int) $user->id : 0;
        } else {
            if (empty($this->created)) {
                $this->created = $date;
            }

            if (empty($this->created_by)) {
                $this->created_by = $user ? (int) $user->id : 0;
            }

            $this->modified    = $this->created;
            $this->modified_by = $this->created_by;
        }

        // Unique alias inside the category.
        $table = new self($this->_db);

        if ($table->load(['alias' => $this->alias, 'catid' => (int) $this->catid]) && ($table->id != $this->id || !$this->id)) {
            $this->setError(Text::_('COM_ADRIANJDOWNLOADS_ERROR_UNIQUE_ALIAS'));

            return false;
        }

        return parent::store($updateNulls);
    }
}
