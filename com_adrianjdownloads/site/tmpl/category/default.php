<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\FileTypeHelper;
use Adrian\Component\Adrianjdownloads\Administrator\Helper\StorageHelper;
use Adrian\Component\Adrianjdownloads\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Adrian\Component\Adrianjdownloads\Site\View\Category\HtmlView $this */

$p        = $this->params;
$isGuest  = (bool) Factory::getApplication()->getIdentity()->guest;
$search   = (string) $this->state->get('filter.search');
$showSize = (bool) $p->get('show_size', 1);
$showDate = (bool) $p->get('show_date', 1);
$showCnt  = (bool) $p->get('show_count', 1);
$showVer  = (bool) $p->get('show_version', 1);
$showAut  = (bool) $p->get('show_author', 0);
$showIcon = (bool) $p->get('show_type_icon', 1);
$formUrl  = Route::_(RouteHelper::getCategoryRoute($this->category->id));
?>
<div class="adrianjd adrianjd-category">
    <?php if ($p->get('show_page_heading')) : ?>
        <h1><?php echo $this->escape($p->get('page_heading')); ?></h1>
    <?php endif; ?>

    <?php if ($p->get('show_category_title', 1) && !($p->get('show_page_heading') && $p->get('page_heading') === $this->category->title)) : ?>
        <h2 class="adrianjd-category-title"><?php echo $this->escape($this->category->title); ?></h2>
    <?php endif; ?>

    <?php if ($p->get('show_cat_description', 1) && $this->category->description) : ?>
        <div class="adrianjd-category-desc mb-3">
            <?php echo HTMLHelper::_('content.prepare', $this->category->description, '', 'com_adrianjdownloads.category'); ?>
        </div>
    <?php endif; ?>

    <?php if ($p->get('show_subcategories', 1) && $this->children) : ?>
        <h2 class="h5"><?php echo Text::_('COM_ADRIANJDOWNLOADS_SUBCATEGORIES'); ?></h2>
        <ul class="adrianjd-subcats">
            <?php foreach ($this->children as $child) : ?>
                <?php if (!$p->get('show_empty_categories', 1) && !$child->numitems && !$child->hasChildren()) {
                    continue;
                } ?>
                <li>
                    <span class="icon-folder" aria-hidden="true"></span>
                    <a href="<?php echo Route::_(RouteHelper::getCategoryRoute($child->id, $child->language)); ?>"><?php echo $this->escape($child->title); ?></a>
                    <span class="badge bg-secondary"><?php echo (int) $child->numitems; ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($p->get('show_search', 1)) : ?>
        <form action="<?php echo $formUrl; ?>" method="get" class="adrianjd-search" role="search">
            <label for="adrianjd-filter-search" class="visually-hidden"><?php echo Text::_('COM_ADRIANJDOWNLOADS_SEARCH_LABEL'); ?></label>
            <input type="search" name="filter-search" id="adrianjd-filter-search" class="form-control"
                   value="<?php echo $this->escape($search); ?>" placeholder="<?php echo Text::_('COM_ADRIANJDOWNLOADS_SEARCH_PLACEHOLDER'); ?>">
            <button type="submit" class="btn btn-primary"><span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
            <?php if ($search !== '') : ?>
                <a class="btn btn-secondary" href="<?php echo $formUrl; ?>"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
            <?php endif; ?>
        </form>
    <?php endif; ?>

    <?php if (empty($this->items)) : ?>
        <?php if ($search !== '' || !$this->children) : ?>
        <div class="alert alert-info">
            <?php echo Text::_($search !== '' ? 'COM_ADRIANJDOWNLOADS_NO_RESULTS' : 'COM_ADRIANJDOWNLOADS_NO_DOWNLOADS'); ?>
        </div>
        <?php endif; ?>
    <?php else : ?>
        <table class="table table-striped adrianjd-table">
            <caption class="visually-hidden"><?php echo $this->escape($this->category->title); ?></caption>
            <thead>
                <tr>
                    <th scope="col"><?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_TITLE'); ?></th>
                    <?php if ($showSize) : ?><th scope="col"><?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_SIZE'); ?></th><?php endif; ?>
                    <?php if ($showDate) : ?><th scope="col"><?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_DATE'); ?></th><?php endif; ?>
                    <?php if ($showCnt) : ?><th scope="col"><?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_DOWNLOADS'); ?></th><?php endif; ?>
                    <th scope="col" class="text-end"><span class="visually-hidden"><?php echo Text::_('COM_ADRIANJDOWNLOADS_DOWNLOAD'); ?></span></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->items as $item) :
                $canDl  = !$item->require_login || !$isGuest;
                $detail = Route::_(RouteHelper::getDownloadRoute($item->slug, $item->catid, $item->language));
                ?>
                <tr>
                    <td class="adrianjd-title-cell">
                        <?php if ($showIcon) : ?>
                            <?php echo FileTypeHelper::icon($item->file_original ?: $item->file_name, $item->external_url); ?>
                        <?php endif; ?>
                        <div class="adrianjd-title-body">
                        <a class="adrianjd-title" href="<?php echo $detail; ?>"><?php echo $this->escape($item->title); ?></a>
                        <?php if ($showVer && $item->version) : ?>
                            <span class="badge bg-light text-dark">v<?php echo $this->escape($item->version); ?></span>
                        <?php endif; ?>
                        <?php if ($showAut && $item->author) : ?>
                            <div class="small text-muted"><?php echo $this->escape($item->author); ?></div>
                        <?php endif; ?>
                        <?php if ($item->description) : ?>
                            <div class="adrianjd-desc"><?php echo HTMLHelper::_('string.truncate', strip_tags($item->description), 160); ?></div>
                        <?php endif; ?>
                        </div>
                    </td>
                    <?php if ($showSize) : ?>
                        <?php $size = StorageHelper::formatSize($item->file_size); ?>
                        <td<?php if ($size !== '') : ?> data-label="<?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_SIZE'); ?>"<?php endif; ?>><?php echo $size; ?></td>
                    <?php endif; ?>
                    <?php if ($showDate) : ?>
                        <td data-label="<?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_DATE'); ?>"><?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?></td>
                    <?php endif; ?>
                    <?php if ($showCnt) : ?>
                        <td data-label="<?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_DOWNLOADS'); ?>"><?php echo (int) $item->downloads; ?></td>
                    <?php endif; ?>
                    <td class="text-end">
                        <?php if ($canDl) : ?>
                            <a class="btn btn-sm btn-success" href="<?php echo Route::_(RouteHelper::getFileRoute($item->slug, $item->catid, $item->language)); ?>" rel="nofollow">
                                <span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_ADRIANJDOWNLOADS_DOWNLOAD'); ?>
                            </a>
                        <?php elseif ($p->get('show_noauth', 1)) : ?>
                            <span class="badge bg-warning text-dark"><span class="icon-lock" aria-hidden="true"></span> <?php echo Text::_('COM_ADRIANJDOWNLOADS_LOGIN_REQUIRED'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($this->pagination->pagesTotal > 1) : ?>
            <div class="com-adrianjd-pagination w-100">
                <p class="counter float-end pt-3 pe-2"><?php echo $this->pagination->getPagesCounter(); ?></p>
                <?php echo $this->pagination->getPagesLinks(); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
