<?php
/**
 * @package     mod_megameniu
 * @copyright   (C) 2026
 * @license     GNU General Public License version 2 or later
 *
 * Layout implicit. Poate fi suprascris din temă:
 * templates/<tema>/html/mod_megameniu/default.php
 *
 * Variabile disponibile: $megameniuTree, $params, $module, $moduleclass_sfx
 */

defined('_JEXEC') or die;

echo MegaMeniuRenderer::render($megameniuTree, $params, $module);
