<?php

/**
 * @package     Adrian Cookie
 * @subpackage  mod_adrian_cookie
 *
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;

/** @var \Joomla\Registry\Registry $params */
/** @var \stdClass $module */

$app = Factory::getApplication();

// Register this module's joomla.asset.json and load its CSS + JS through the WebAssetManager.
$wa = $app->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('mod_adrian_cookie');
$wa->useStyle('mod_adrian_cookie')->useScript('mod_adrian_cookie');

// Only accept real hex colours, so nothing else can be injected into the style attribute.
$sanitizeColor = static function ($value, string $default): string {
	$value = trim((string) $value);

	return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value) ? $value : $default;
};

$bgColor   = $sanitizeColor($params->get('bg_color'), '#1f2937');
$textColor = $sanitizeColor($params->get('text_color'), '#ffffff');

$position = (string) $params->get('position', 'bottom');

if (!in_array($position, ['top', 'bottom', 'float-left', 'float-right'], true)) {
	$position = 'bottom';
}

// Editor content is already filtered with "safehtml" on save.
$message = trim((string) $params->get('message', ''));

if ($message === '') {
	$message = '<p>' . Text::_('MOD_ADRIAN_COOKIE_DEFAULT_MESSAGE') . '</p>';
}

$acceptText    = trim((string) $params->get('accept_text', '')) ?: Text::_('MOD_ADRIAN_COOKIE_DEFAULT_ACCEPT');
$essentialText = trim((string) $params->get('essential_text', '')) ?: Text::_('MOD_ADRIAN_COOKIE_DEFAULT_ESSENTIAL');
$policyUrl     = trim((string) $params->get('policy_url', ''));
$policyText    = trim((string) $params->get('policy_text', '')) ?: Text::_('MOD_ADRIAN_COOKIE_DEFAULT_POLICY');

require ModuleHelper::getLayoutPath('mod_adrian_cookie', $params->get('layout', 'default'));
