<?php
/**
 * @package     mod_megameniu
 * @copyright   (C) 2026
 * @license     GNU General Public License version 2 or later
 *
 * Punct de intrare clasic. Funcționează pe Joomla 3.10, 4.x, 5.x și 6.x
 * (Joomla 4+ rulează modulele fără services/provider.php prin dispecerul „legacy”).
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/src/Compat.php';
require_once __DIR__ . '/src/MenuTree.php';
require_once __DIR__ . '/src/Renderer.php';

$megameniuTree = MegaMeniuTree::build($params);

if (empty($megameniuTree['items']))
{
	return;
}

MegaMeniuCompat::loadLanguage();
MegaMeniuCompat::addAssets();

$moduleclass_sfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');

require MegaMeniuCompat::layoutPath('mod_megameniu', (string) $params->get('layout', 'default'));
