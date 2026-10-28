<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\StorageHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Adrian\Component\Adrianjdownloads\Administrator\View\Download\HtmlView $this */

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');

$allowed = implode(', ', StorageHelper::getAllowedExtensions());
?>
<form action="<?php echo Route::_('index.php?option=com_adrianjdownloads&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="download-form" class="form-validate" enctype="multipart/form-data"
      aria-label="<?php echo Text::_(empty($this->item->id) ? 'COM_ADRIANJDOWNLOADS_DOWNLOAD_NEW' : 'COM_ADRIANJDOWNLOADS_DOWNLOAD_EDIT', true); ?>">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_ADRIANJDOWNLOADS_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <fieldset class="options-form">
                    <legend><?php echo Text::_('COM_ADRIANJDOWNLOADS_LEGEND_FILE'); ?></legend>

                    <?php if (!empty($this->item->file_name)) : ?>
                        <div class="alert alert-success">
                            <span class="icon-file" aria-hidden="true"></span>
                            <?php echo Text::_('COM_ADRIANJDOWNLOADS_CURRENT_FILE'); ?>:
                            <strong><?php echo $this->escape($this->item->file_original); ?></strong>
                            (<?php echo StorageHelper::formatSize($this->item->file_size); ?>)
                            <?php if (StorageHelper::getFilePath($this->item->file_name) === null) : ?>
                                <span class="badge bg-danger"><?php echo Text::_('COM_ADRIANJDOWNLOADS_FILE_MISSING'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php echo $this->form->renderField('upload_file'); ?>
                    <p class="small text-muted">
                        <?php echo Text::sprintf('COM_ADRIANJDOWNLOADS_UPLOAD_HELP', $allowed); ?>
                    </p>
                    <?php if (!empty($this->item->file_name)) : ?>
                        <?php echo $this->form->renderField('remove_file'); ?>
                    <?php endif; ?>
                    <?php echo $this->form->renderField('external_url'); ?>
                    <?php echo $this->form->renderField('version'); ?>
                    <?php echo $this->form->renderField('author'); ?>
                </fieldset>

                <?php echo $this->form->getLabel('description'); ?>
                <?php echo $this->form->getInput('description'); ?>
            </div>
            <div class="col-lg-3">
                <?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
                <div class="form-vertical">
                    <?php echo $this->form->renderField('access_download'); ?>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
        <div class="row">
            <div class="col-md-6">
                <fieldset class="options-form">
                    <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
                    <?php echo $this->form->renderFieldset('publishing'); ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
