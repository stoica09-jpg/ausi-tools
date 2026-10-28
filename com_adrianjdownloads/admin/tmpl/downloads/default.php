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
use Joomla\CMS\Session\Session;

/** @var \Adrian\Component\Adrianjdownloads\Administrator\View\Downloads\HtmlView $this */

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')->useScript('multiselect');

$user      = Factory::getApplication()->getIdentity();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder === 'a.ordering';

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_adrianjdownloads&task=downloads.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>
<form action="<?php echo Route::_('index.php?option=com_adrianjdownloads&view=downloads'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="downloadList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_ADRIANJDOWNLOADS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-md-table-cell">
                                    <?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_FILE'); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_ADRIANJDOWNLOADS_HEADING_DOWNLOADS', 'a.downloads', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JDATE', 'a.created', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
                        <?php foreach ($this->items as $i => $item) :
                            $canEdit    = $user->authorise('core.edit', 'com_adrianjdownloads');
                            $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || !$item->checked_out;
                            $canChange  = $user->authorise('core.edit.state', 'com_adrianjdownloads') && $canCheckin;
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo (int) $item->catid; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php
                                    $iconClass = '';
                                    if (!$canChange) {
                                        $iconClass = ' inactive';
                                    } elseif (!$saveOrder) {
                                        $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                                    }
                                    ?>
                                    <span class="sortable-handler<?php echo $iconClass; ?>"><span class="icon-ellipsis-v" aria-hidden="true"></span></span>
                                    <?php if ($canChange && $saveOrder) : ?>
                                        <input type="text" name="order[]" size="5" value="<?php echo (int) $item->ordering; ?>" class="width-20 text-area-order hidden">
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'downloads.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
                                </td>
                                <th scope="row">
                                    <?php if ($item->checked_out) : ?>
                                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'downloads.', $canCheckin); ?>
                                    <?php endif; ?>
                                    <?php if ($canEdit) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_adrianjdownloads&task=download.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->title); ?></a>
                                    <?php else : ?>
                                        <?php echo $this->escape($item->title); ?>
                                    <?php endif; ?>
                                    <div class="small">
                                        <?php echo Text::_('JCATEGORY') . ': ' . $this->escape($item->category_title); ?>
                                        <?php if ($item->version) : ?>
                                            &middot; <?php echo Text::_('COM_ADRIANJDOWNLOADS_FIELD_VERSION_LABEL') . ': ' . $this->escape($item->version); ?>
                                        <?php endif; ?>
                                    </div>
                                </th>
                                <td class="small d-none d-md-table-cell">
                                    <?php if ($item->file_name) : ?>
                                        <span class="icon-file" aria-hidden="true"></span>
                                        <?php echo $this->escape($item->file_original); ?>
                                        <span class="text-muted">(<?php echo StorageHelper::formatSize($item->file_size); ?>)</span>
                                    <?php elseif ($item->external_url) : ?>
                                        <span class="icon-link" aria-hidden="true"></span>
                                        <?php echo $this->escape(HTMLHelper::_('string.truncate', $item->external_url, 50)); ?>
                                    <?php else : ?>
                                        <span class="badge bg-warning text-dark"><?php echo Text::_('COM_ADRIANJDOWNLOADS_NO_FILE'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo $this->escape($item->access_level); ?>
                                    <?php if ($item->require_login) : ?>
                                        <br><span class="badge bg-warning text-dark"><span class="icon-lock" aria-hidden="true"></span> <?php echo Text::_('COM_ADRIANJDOWNLOADS_BADGE_REQUIRE_LOGIN'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-info"><?php echo (int) $item->downloads; ?></span>
                                </td>
                                <td class="small d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo (int) $item->id; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
