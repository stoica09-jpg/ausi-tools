<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\FileTypeHelper;
use Adrian\Component\Adrianjdownloads\Administrator\Helper\StorageHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Adrian\Component\Adrianjdownloads\Administrator\View\Downloads\HtmlView $this */

$app    = Factory::getApplication();
$editor = $app->getInput()->getCmd('editor', '');
$doc    = $app->getDocument();
$wa     = $doc->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_adrianjdownloads');
$wa->useScript('com_adrianjdownloads.admin-modal');
$wa->addInlineStyle('.adrianjd-ficon-sm{width:20px;height:24px;vertical-align:middle;margin-right:.25rem}');
$doc->addScriptOptions('adrianjd-modal', ['editor' => $editor]);

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<div class="container-popup adrianjd-modal">
    <form action="<?php echo Route::_('index.php?option=com_adrianjdownloads&view=downloads&layout=modal&tmpl=component&editor=' . $editor); ?>" method="post" name="adminForm" id="adminForm">

        <div class="alert alert-info small mb-3">
            <?php echo Text::_('COM_ADRIANJDOWNLOADS_MODAL_HELP'); ?>
        </div>

        <fieldset class="mb-3">
            <legend class="visually-hidden"><?php echo Text::_('COM_ADRIANJDOWNLOADS_MODAL_LAYOUT'); ?></legend>
            <span class="me-2 fw-bold"><?php echo Text::_('COM_ADRIANJDOWNLOADS_MODAL_LAYOUT'); ?>:</span>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="adrianjd_layout" id="adrianjd_layout_box" value="box" checked>
                <label class="form-check-label" for="adrianjd_layout_box"><?php echo Text::_('COM_ADRIANJDOWNLOADS_LAYOUT_BOX'); ?></label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="adrianjd_layout" id="adrianjd_layout_link" value="link">
                <label class="form-check-label" for="adrianjd_layout_link"><?php echo Text::_('COM_ADRIANJDOWNLOADS_LAYOUT_LINK'); ?></label>
            </div>
        </fieldset>

        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span>
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else : ?>
            <table class="table table-sm" id="downloadList">
                <caption class="visually-hidden"><?php echo Text::_('COM_ADRIANJDOWNLOADS_TABLE_CAPTION'); ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
                        <th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'JCATEGORY', 'category_title', $listDirn, $listOrder); ?></th>
                        <th scope="col" class="w-10 d-none d-md-table-cell"><?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_SIZE'); ?></th>
                        <th scope="col" class="w-5 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($this->items as $item) : ?>
                    <tr>
                        <th scope="row">
                            <?php echo FileTypeHelper::icon($item->file_original ?: $item->file_name, $item->external_url, 'adrianjd-ficon-sm'); ?>
                            <button type="button" class="btn btn-link p-0 align-baseline text-start adrianjd-insert"
                                    data-type="file" data-id="<?php echo (int) $item->id; ?>"
                                    title="<?php echo $this->escape(Text::_('COM_ADRIANJDOWNLOADS_MODAL_INSERT_FILE')); ?>">
                                <?php echo $this->escape($item->title); ?>
                            </button>
                            <?php if ((int) $item->state !== 1) : ?>
                                <span class="badge bg-secondary"><?php echo Text::_('JUNPUBLISHED'); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item->require_login)) : ?>
                                <span class="badge bg-warning text-dark"><?php echo Text::_('COM_ADRIANJDOWNLOADS_BADGE_REQUIRE_LOGIN'); ?></span>
                            <?php endif; ?>
                        </th>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary adrianjd-insert"
                                    data-type="category" data-id="<?php echo (int) $item->catid; ?>"
                                    title="<?php echo $this->escape(Text::_('COM_ADRIANJDOWNLOADS_MODAL_INSERT_CATEGORY')); ?>">
                                <span class="icon-folder" aria-hidden="true"></span>
                                <?php echo $this->escape($item->category_title); ?>
                            </button>
                        </td>
                        <td class="small d-none d-md-table-cell"><?php echo StorageHelper::formatSize($item->file_size); ?></td>
                        <td class="d-none d-md-table-cell"><?php echo (int) $item->id; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php echo $this->pagination->getListFooter(); ?>
        <?php endif; ?>

        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
