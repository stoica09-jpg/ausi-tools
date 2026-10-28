<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\View\Downloads;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * List of downloads.
 */
class HtmlView extends BaseHtmlView
{
    protected $items = [];

    protected $pagination;

    protected $state;

    public $filterForm;

    public $activeFilters = [];

    public function display($tpl = null)
    {
        $model               = $this->getModel();
        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        }

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $canDo   = ContentHelper::getActions('com_adrianjdownloads');
        $document = Factory::getApplication()->getDocument();
        $toolbar  = method_exists($document, 'getToolbar') ? $document->getToolbar() : Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_ADRIANJDOWNLOADS_MANAGER_DOWNLOADS'), 'download');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('download.add');
        }

        if ($canDo->get('core.edit.state') || $canDo->get('core.delete')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            if ($canDo->get('core.edit.state')) {
                $childBar->publish('downloads.publish')->listCheck(true);
                $childBar->unpublish('downloads.unpublish')->listCheck(true);
                $childBar->archive('downloads.archive')->listCheck(true);
                $childBar->checkin('downloads.checkin')->listCheck(true);

                if ((string) $this->state->get('filter.published') !== '-2') {
                    $childBar->trash('downloads.trash')->listCheck(true);
                }
            }
        }

        if ((string) $this->state->get('filter.published') === '-2' && $canDo->get('core.delete')) {
            $toolbar->delete('downloads.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        $toolbar->linkButton('folder', 'COM_ADRIANJDOWNLOADS_SUBMENU_CATEGORIES')
            ->url('index.php?option=com_categories&view=categories&extension=com_adrianjdownloads')
            ->icon('icon-folder');

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_adrianjdownloads');
        }
    }
}
