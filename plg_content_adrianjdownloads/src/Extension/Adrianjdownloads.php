<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Plugin\Content\Adrianjdownloads\Extension;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\DatabaseHelper;
use Adrian\Component\Adrianjdownloads\Administrator\Helper\FileTypeHelper;
use Adrian\Component\Adrianjdownloads\Administrator\Helper\StorageHelper;
use Adrian\Component\Adrianjdownloads\Site\Helper\RouteHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/**
 * Replaces {adrianjd file=12} and {adrianjd category=5} in articles with the
 * file (or the list of files of a category), using the access rules of the
 * component. Optional: layout=link (simple link) or layout=box.
 */
final class Adrianjdownloads extends CMSPlugin implements SubscriberInterface
{
    private const PATTERN = '/\{adrianjd\s+([^}]*)\}/i';

    private bool $assetsLoaded = false;

    public static function getSubscribedEvents(): array
    {
        return ['onContentPrepare' => 'onContentPrepare'];
    }

    public function onContentPrepare(Event $event): void
    {
        // Joomla 4: [context, item, params, page]; Joomla 5: the same order, named.
        $args    = array_values($event->getArguments());
        $context = (string) ($args[0] ?? '');
        $item    = $args[1] ?? null;

        if (!\is_object($item) || !isset($item->text) || stripos((string) $item->text, '{adrianjd') === false) {
            return;
        }

        // Search indexing: remove the tags.
        if ($context === 'com_finder.indexer' || !$this->getApplication()->isClient('site')) {
            $item->text = preg_replace(self::PATTERN, '', $item->text);

            return;
        }

        $item->text = preg_replace_callback(self::PATTERN, fn ($m) => $this->render($this->parseAttributes($m[1])), $item->text);
    }

    private function parseAttributes(string $text): array
    {
        $attributes = [];

        preg_match_all('/(\w+)\s*==?\s*("[^"]*"|\'[^\']*\'|[^\s"\']+)/', html_entity_decode(strip_tags($text)), $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $attributes[strtolower($match[1])] = trim($match[2], '"\'');
        }

        return $attributes;
    }

    private function render(array $attributes): string
    {
        $layout = ($attributes['layout'] ?? $this->params->get('layout', 'box')) === 'link' ? 'link' : 'box';

        if (!empty($attributes['file'])) {
            $files = $this->loadFiles('a.id', (int) $attributes['file']);
        } elseif (!empty($attributes['category'])) {
            $files = $this->loadFiles('a.catid', (int) $attributes['category']);
        } else {
            return '';
        }

        // Files the visitor may not see are silently left out.
        if (!$files) {
            return '';
        }

        $this->loadAssets();

        $html = [];

        foreach ($files as $file) {
            $html[] = $layout === 'link' ? $this->renderLink($file) : $this->renderBox($file);
        }

        if ($layout === 'link') {
            return \count($html) === 1
                ? $html[0]
                : '<ul class="adrianjd adrianjd-embed-list"><li>' . implode('</li><li>', $html) . '</li></ul>';
        }

        return '<div class="adrianjd adrianjd-embed-group">' . implode('', $html) . '</div>';
    }

    private function renderLink(object $file): string
    {
        return '<span class="adrianjd adrianjd-embed-link">'
            . FileTypeHelper::icon($file->file_original ?: $file->file_name, $file->external_url, 'adrianjd-ficon-inline')
            . ' <a href="' . $this->fileUrl($file) . '" rel="nofollow">' . htmlspecialchars($file->title, ENT_QUOTES, 'UTF-8') . '</a>'
            . $this->renderMeta($file, true)
            . '</span>';
    }

    private function renderBox(object $file): string
    {
        $url    = $this->fileUrl($file);
        $locked = $file->require_login && Factory::getApplication()->getIdentity()->guest;
        $button = $locked
            ? '<a class="btn btn-sm btn-warning" href="' . $url . '" rel="nofollow"><span class="icon-lock" aria-hidden="true"></span> '
                . Text::_('COM_ADRIANJDOWNLOADS_LOGIN_REQUIRED') . '</a>'
            : '<a class="btn btn-sm btn-success" href="' . $url . '" rel="nofollow"><span class="icon-download" aria-hidden="true"></span> '
                . Text::_('COM_ADRIANJDOWNLOADS_DOWNLOAD') . '</a>';

        return '<div class="adrianjd-embed-box">'
            . FileTypeHelper::icon($file->file_original ?: $file->file_name, $file->external_url)
            . '<div class="adrianjd-embed-body">'
            . '<a class="adrianjd-title" href="' . $url . '" rel="nofollow">' . htmlspecialchars($file->title, ENT_QUOTES, 'UTF-8') . '</a>'
            . $this->renderMeta($file, false)
            . '</div>'
            . '<div class="adrianjd-embed-action">' . $button . '</div>'
            . '</div>';
    }

    private function renderMeta(object $file, bool $inline): string
    {
        $parts = [];

        if ($this->params->get('show_size', 1) && $file->file_size) {
            $parts[] = StorageHelper::formatSize($file->file_size);
        }

        if ($this->params->get('show_count', 0)) {
            $parts[] = Text::_('COM_ADRIANJDOWNLOADS_HEADING_DOWNLOADS') . ': ' . (int) $file->downloads;
        }

        if ($inline && $file->require_login && Factory::getApplication()->getIdentity()->guest) {
            $parts[] = Text::_('COM_ADRIANJDOWNLOADS_LOGIN_REQUIRED');
        }

        if (!$parts) {
            return '';
        }

        return $inline
            ? ' <span class="adrianjd-embed-meta">(' . htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8') . ')</span>'
            : '<div class="adrianjd-embed-meta">' . htmlspecialchars(implode(' · ', $parts), ENT_QUOTES, 'UTF-8') . '</div>';
    }

    private function fileUrl(object $file): string
    {
        $slug = $file->alias ? $file->id . ':' . $file->alias : $file->id;

        return Route::_(RouteHelper::getFileRoute($slug, $file->catid, $file->language));
    }

    /**
     * Published files the visitor may see, by id or by category.
     */
    private function loadFiles(string $column, int $value): array
    {
        if ($value <= 0) {
            return [];
        }

        $app    = $this->getApplication();
        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $levels = $app->getIdentity()->getAuthorisedViewLevels();
        $now    = Factory::getDate()->toSql();

        $query = DatabaseHelper::query($db)
            ->select($db->quoteName(['a.id', 'a.catid', 'a.title', 'a.alias', 'a.file_name', 'a.file_original', 'a.file_size',
                'a.external_url', 'a.downloads', 'a.require_login', 'a.language']))
            ->from($db->quoteName('#__adrianjdownloads_files', 'a'))
            ->join('INNER', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->where($db->quoteName($column) . ' = :value')
            ->where($db->quoteName('a.state') . ' = 1')
            ->where($db->quoteName('c.published') . ' = 1')
            ->whereIn($db->quoteName('a.access'), $levels)
            ->whereIn($db->quoteName('c.access'), $levels)
            ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :now1)')
            ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' >= :now2)')
            ->bind(':value', $value, ParameterType::INTEGER)
            ->bind([':now1', ':now2'], $now)
            ->order($db->quoteName('a.ordering') . ' ASC');

        if (Multilanguage::isEnabled()) {
            $query->whereIn($db->quoteName('a.language'), [$app->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    private function loadAssets(): void
    {
        if ($this->assetsLoaded) {
            return;
        }

        $this->assetsLoaded = true;

        $app = $this->getApplication();
        $app->getLanguage()->load('com_adrianjdownloads', JPATH_SITE . '/components/com_adrianjdownloads')
            || $app->getLanguage()->load('com_adrianjdownloads', JPATH_SITE);

        $wa = $app->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_adrianjdownloads');
        $wa->useStyle('com_adrianjdownloads.site');
    }
}
