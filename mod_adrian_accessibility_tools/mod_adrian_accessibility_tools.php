<?php

/**
 * @package     Adrian Accessibility Tools
 * @subpackage  mod_adrian_accessibility_tools
 *
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

/** @var \Joomla\Registry\Registry $params */
/** @var \stdClass $module */

$app = Factory::getApplication();

// Register this module's joomla.asset.json and load its CSS + JS through the WebAssetManager.
$wa = $app->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('mod_adrian_accessibility_tools');
$wa->useStyle('mod_adrian_accessibility_tools')->useScript('mod_adrian_accessibility_tools');

// Re-apply saved preferences before the page paints, so there is no flash of the default look.
// The main script later drops any class whose tool is disabled in this module.
if (!$wa->assetExists('script', 'mod_adrian_accessibility_tools.restore')) {
	$wa->addInlineScript(
		"(function(){try{var p=JSON.parse(localStorage.getItem('adrian_a11y_prefs')||'{}');"
		. "(p.classes||[]).forEach(function(c){if(/^adrian-[a-z-]+$/.test(c)){document.documentElement.classList.add(c);}});"
		. "}catch(e){}})();",
		['name' => 'mod_adrian_accessibility_tools.restore']
	);
}

// Toggle tools: param name => CSS class added to <html>.
$toolClasses = [
	'invert'             => 'adrian-invert',
	'monochrome'         => 'adrian-monochrome',
	'dark_contrast'      => 'adrian-dark-contrast',
	'light_contrast'     => 'adrian-light-contrast',
	'highlight_links'    => 'adrian-highlight-links',
	'highlight_headings' => 'adrian-highlight-headings',
	'readable_font'      => 'adrian-readable-font',
];

$tools = [];

foreach ($toolClasses as $key => $class) {
	if ((int) $params->get($key, 1) === 1) {
		$tools[$key] = $class;
	}
}

$showTextSize = (int) $params->get('text_size', 1) === 1;
$showReset    = (int) $params->get('reset', 1) === 1;

$position = $params->get('position', 'right') === 'left' ? 'left' : 'right';

$themeColor = trim((string) $params->get('theme_color', '#0b57d0'));

if (!preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $themeColor)) {
	$themeColor = '#0b57d0';
}

// Pick black or white text for the theme colour (WCAG relative luminance).
$hex = ltrim($themeColor, '#');

if (strlen($hex) === 3) {
	$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
}

$channel = static function (string $pair): float {
	$c = hexdec($pair) / 255;

	return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
};

$luminance     = 0.2126 * $channel(substr($hex, 0, 2)) + 0.7152 * $channel(substr($hex, 2, 2)) + 0.0722 * $channel(substr($hex, 4, 2));
$themeContrast = $luminance > 0.179 ? '#000000' : '#ffffff';

// Icons and outlines sit on the white panel: fall back to a dark accent when the theme colour is too light (< 3:1).
$themeAccent = (1.05 / ($luminance + 0.05)) >= 3 ? $themeColor : '#1b1f24';

require ModuleHelper::getLayoutPath('mod_adrian_accessibility_tools', $params->get('layout', 'default'));
