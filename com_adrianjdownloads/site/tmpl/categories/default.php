<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Adrian\Component\Adrianjdownloads\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Adrian\Component\Adrianjdownloads\Site\View\Categories\HtmlView $this */

$showEmpty = (bool) $this->params->get('show_empty_categories', 1);
$showDesc  = (bool) $this->params->get('show_cat_description', 1);

$renderTree = static function (array $nodes) use (&$renderTree, $showEmpty, $showDesc): string {
    $html = '';

    foreach ($nodes as $node) {
        $children = $node->getChildren();

        if (!$showEmpty && !$node->numitems && !$children) {
            continue;
        }

        $html .= '<li class="adrianjd-cat">';
        $html .= '<span class="icon-folder" aria-hidden="true"></span> ';
        $html .= '<a href="' . Route::_(RouteHelper::getCategoryRoute($node->id, $node->language)) . '">' . htmlspecialchars($node->title, ENT_QUOTES, 'UTF-8') . '</a>';
        $html .= ' <span class="badge bg-secondary" title="' . Text::_('COM_ADRIANJDOWNLOADS_NUM_ITEMS') . '">' . (int) $node->numitems . '</span>';

        if ($showDesc && $node->description) {
            $html .= '<div class="adrianjd-cat-desc">' . HTMLHelper::_('string.truncate', strip_tags($node->description), 200) . '</div>';
        }

        if ($children) {
            $html .= '<ul class="adrianjd-cats">' . $renderTree($children) . '</ul>';
        }

        $html .= '</li>';
    }

    return $html;
};
?>
<div class="adrianjd adrianjd-categories">
    <?php if ($this->params->get('show_page_heading')) : ?>
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
    <?php endif; ?>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_ADRIANJDOWNLOADS_NO_CATEGORIES'); ?></div>
    <?php else : ?>
        <ul class="adrianjd-cats">
            <?php echo $renderTree($this->items); ?>
        </ul>
    <?php endif; ?>
</div>
