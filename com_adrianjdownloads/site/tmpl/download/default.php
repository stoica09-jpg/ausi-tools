<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\StorageHelper;
use Adrian\Component\Adrianjdownloads\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Adrian\Component\Adrianjdownloads\Site\View\Download\HtmlView $this */

$item = $this->item;
$p    = $this->params;
?>
<div class="adrianjd adrianjd-download">
    <?php if ($p->get('show_page_heading')) : ?>
        <h1><?php echo $this->escape($p->get('page_heading')); ?></h1>
    <?php endif; ?>

    <h2 class="adrianjd-download-title"><?php echo $this->escape($item->title); ?></h2>

    <ul class="adrianjd-meta">
        <li>
            <span class="icon-folder" aria-hidden="true"></span>
            <?php echo Text::_('JCATEGORY'); ?>:
            <a href="<?php echo Route::_(RouteHelper::getCategoryRoute($item->catid)); ?>"><?php echo $this->escape($item->category_title); ?></a>
        </li>
        <?php if ($item->file_original) : ?>
            <li><span class="icon-file" aria-hidden="true"></span> <?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_FILE'); ?>: <?php echo $this->escape($item->file_original); ?></li>
        <?php endif; ?>
        <?php if ($p->get('show_size', 1) && $item->file_size) : ?>
            <li><?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_SIZE'); ?>: <?php echo StorageHelper::formatSize($item->file_size); ?></li>
        <?php endif; ?>
        <?php if ($p->get('show_version', 1) && $item->version) : ?>
            <li><?php echo Text::_('COM_ADRIANJDOWNLOADS_FIELD_VERSION_LABEL'); ?>: <?php echo $this->escape($item->version); ?></li>
        <?php endif; ?>
        <?php if ($p->get('show_author', 0) && $item->author) : ?>
            <li><?php echo Text::_('COM_ADRIANJDOWNLOADS_FIELD_AUTHOR_LABEL'); ?>: <?php echo $this->escape($item->author); ?></li>
        <?php endif; ?>
        <?php if ($p->get('show_date', 1)) : ?>
            <li><?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_DATE'); ?>: <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC3')); ?></li>
        <?php endif; ?>
        <?php if ($p->get('show_count', 1)) : ?>
            <li><?php echo Text::_('COM_ADRIANJDOWNLOADS_HEADING_DOWNLOADS'); ?>: <?php echo (int) $item->downloads; ?></li>
        <?php endif; ?>
    </ul>

    <?php if ($item->description) : ?>
        <div class="adrianjd-download-desc mb-3"><?php echo $item->description; ?></div>
    <?php endif; ?>

    <?php if ($this->canDownload) : ?>
        <a class="btn btn-lg btn-success" href="<?php echo Route::_(RouteHelper::getFileRoute($item->slug, $item->catid, $item->language)); ?>" rel="nofollow">
            <span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_ADRIANJDOWNLOADS_DOWNLOAD'); ?>
        </a>
    <?php else : ?>
        <div class="alert alert-warning">
            <span class="icon-lock" aria-hidden="true"></span> <?php echo Text::_('COM_ADRIANJDOWNLOADS_LOGIN_TO_DOWNLOAD'); ?>
        </div>
    <?php endif; ?>
</div>
