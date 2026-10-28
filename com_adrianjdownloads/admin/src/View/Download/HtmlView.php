<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\View\Download;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * Edit form of a download.
 */
class HtmlView extends BaseHtmlView
{
    protected $form;

    protected $item;

    protected $state;

    public function display($tpl = null)
    {
        $model       = $this->getModel();
        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew   = empty($this->item->id);
        $canDo   = ContentHelper::getActions('com_adrianjdownloads');
        $document = Factory::getApplication()->getDocument();
        $toolbar  = method_exists($document, 'getToolbar') ? $document->getToolbar() : Toolbar::getInstance('toolbar');

        ToolbarHelper::title(
            Text::_($isNew ? 'COM_ADRIANJDOWNLOADS_DOWNLOAD_NEW' : 'COM_ADRIANJDOWNLOADS_DOWNLOAD_EDIT'),
            'download'
        );

        if ($canDo->get('core.edit') || $canDo->get('core.create')) {
            $toolbar->apply('download.apply');
            $toolbar->save('download.save');
        }

        if ($canDo->get('core.create')) {
            $toolbar->save2new('download.save2new');
        }

        $toolbar->cancel('download.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
