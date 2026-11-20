<?php
/**
 * @package     mod_megameniu
 * @copyright   (C) 2026
 * @license     GNU General Public License version 2 or later
 *
 * Generează HTML-ul meniului. Tot textul trece prin htmlspecialchars.
 */

defined('_JEXEC') or die;

if (!class_exists('MegaMeniuRenderer'))
{
	class MegaMeniuRenderer
	{
		/** @var string */
		private $uid;

		/** @var bool */
		private $mega;

		/** @var int */
		private $maxColumns;

		/** @var callable */
		private $t;

		/**
		 * @param   array     $tree    Rezultatul MegaMeniuTree::build()
		 * @param   object    $params  Registry cu parametrii modulului
		 * @param   object    $module  Obiectul modulului (id, title)
		 * @param   callable  $t       Funcție de traducere: fn(string $key, ?string $arg) => string
		 */
		public static function render(array $tree, $params, $module, $t = null)
		{
			if ($t === null)
			{
				$t = static function ($key, $arg = null) {
					return $arg === null ? MegaMeniuCompat::text($key) : MegaMeniuCompat::sprintf($key, $arg);
				};
			}

			$self             = new self;
			$self->uid        = 'mm-' . (int) (isset($module->id) ? $module->id : mt_rand(1000, 99999));
			$self->mega       = (string) $params->get('columnsMode', 'auto') === 'auto';
			$self->maxColumns = min(6, max(1, (int) $params->get('maxColumns', 4)));
			$self->t          = $t;

			return $self->renderNav($tree['items'], $params, $module);
		}

		private function e($s)
		{
			return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
		}

		private function tr($key, $arg = null)
		{
			$fn = $this->t;

			return (string) $fn($key, $arg);
		}

		private function renderNav(array $items, $params, $module)
		{
			$classes = ['mm', $this->mega ? 'mm-mode-mega' : 'mm-mode-list', 'mm-align-' . $this->pick($params->get('alignment', 'left'), ['left', 'center', 'right', 'justify'], 'left')];

			if ((int) $params->get('uppercase', 0) === 1)
			{
				$classes[] = 'mm-upper';
			}

			if ((int) $params->get('sticky', 0) === 1)
			{
				$classes[] = 'mm-sticky';
			}

			if ((int) $params->get('shadow', 1) === 1)
			{
				$classes[] = 'mm-shadow';
			}

			$sfx = trim((string) $params->get('moduleclass_sfx', ''));

			if ($sfx !== '')
			{
				$classes[] = $sfx;
			}

			$label = trim((string) $params->get('navLabel', ''));

			if ($label === '')
			{
				$label = $this->tr('MOD_MEGAMENIU_NAV_LABEL');
			}

			$mobileLabel = trim((string) $params->get('mobileLabel', ''));

			if ($mobileLabel === '')
			{
				$mobileLabel = $this->tr('MOD_MEGAMENIU_MOBILE_LABEL');
			}

			$openOn     = $this->pick($params->get('openOn', 'hover'), ['hover', 'click'], 'hover');
			$mobileMode = $this->pick($params->get('mobileMode', 'accordion'), ['accordion', 'offcanvas'], 'accordion');
			$breakpoint = min(2000, max(320, (int) $params->get('mobileBreakpoint', 992)));

			$html  = '<nav class="' . $this->e(implode(' ', $classes)) . '" id="' . $this->uid . '"';
			$html .= ' aria-label="' . $this->e($label) . '"';
			$html .= ' data-open-on="' . $openOn . '" data-breakpoint="' . $breakpoint . '" data-mobile-mode="' . $mobileMode . '"';
			$html .= ' data-close-label="' . $this->e($this->tr('MOD_MEGAMENIU_CLOSE')) . '"';
			$html .= ' style="' . $this->e($this->styleVars($params)) . '">';

			$html .= '<button class="mm-toggle" type="button" aria-expanded="false" aria-controls="' . $this->uid . '-list">'
				. '<span class="mm-toggle-icon" aria-hidden="true"><span></span></span>'
				. '<span class="mm-toggle-text">' . $this->e($mobileLabel) . '</span></button>';

			$html .= '<ul class="mm-list" id="' . $this->uid . '-list">';

			foreach ($items as $node)
			{
				$html .= $this->renderTop($node);
			}

			$html .= '</ul></nav>';

			return $html;
		}

		private function renderTop($node)
		{
			$hasSub  = !empty($node->children);
			$classes = ['mm-item', 'mm-l1', 'item-' . $node->id];

			if ($hasSub)
			{
				$classes[] = 'mm-has-sub';
			}

			if ($node->isCurrent)
			{
				$classes[] = 'current';
			}

			if ($node->isActive)
			{
				$classes[] = 'active mm-active';
			}

			$html = '<li class="' . $this->e(implode(' ', $classes)) . '">';

			$dropId = $this->uid . '-sub-' . $node->id;

			if ($hasSub && ($node->isHeading || $node->href === ''))
			{
				// Element fără link: butonul este chiar titlul.
				$html .= '<button class="mm-sub-toggle mm-top-btn" type="button" aria-expanded="false" aria-controls="' . $dropId . '">'
					. $this->labelHtml($node) . '<span class="mm-caret" aria-hidden="true"></span></button>';
			}
			else
			{
				$html .= $this->linkHtml($node, 'mm-link mm-top-link');

				if ($hasSub)
				{
					$html .= '<button class="mm-sub-toggle" type="button" aria-expanded="false" aria-controls="' . $dropId . '">'
						. '<span class="mm-caret" aria-hidden="true"></span>'
						. '<span class="mm-sr">' . $this->e($this->tr('MOD_MEGAMENIU_OPEN_SUBMENU', $node->title)) . '</span></button>';
				}
			}

			if ($hasSub)
			{
				$html .= $this->renderDrop($node, $dropId);
			}

			return $html . '</li>';
		}

		private function renderDrop($node, $dropId)
		{
			$hasGrandChildren = false;

			foreach ($node->children as $child)
			{
				if (!empty($child->children))
				{
					$hasGrandChildren = true;
					break;
				}
			}

			if ($this->mega && $hasGrandChildren)
			{
				$cols  = min($this->maxColumns, count($node->children));
				$html  = '<div class="mm-drop mm-mega" id="' . $dropId . '" style="--mm-cols:' . $cols . '">';

				foreach ($node->children as $child)
				{
					$html .= '<div class="mm-col item-' . $child->id . ($child->isActive ? ' mm-active' : '') . '">';
					$html .= '<div class="mm-col-title">' . $this->linkHtml($child, $child->isHeading || $child->href === '' ? 'mm-heading' : 'mm-link') . '</div>';

					if (!empty($child->children))
					{
						$html .= '<ul class="mm-sublist">' . $this->renderList($child->children) . '</ul>';
					}

					$html .= '</div>';
				}

				return $html . '</div>';
			}

			return '<div class="mm-drop mm-simple" id="' . $dropId . '"><ul class="mm-sublist">' . $this->renderList($node->children) . '</ul></div>';
		}

		private function renderList(array $nodes)
		{
			$html = '';

			foreach ($nodes as $node)
			{
				$hasSub  = !empty($node->children);
				$classes = ['mm-item', 'mm-subitem', 'item-' . $node->id];

				if ($hasSub)
				{
					$classes[] = 'mm-has-fly';
				}

				if ($node->isCurrent)
				{
					$classes[] = 'current';
				}

				if ($node->isActive)
				{
					$classes[] = 'active';
				}

				$html .= '<li class="' . $this->e(implode(' ', $classes)) . '">';
				$html .= $this->linkHtml($node, $node->isHeading || $node->href === '' ? 'mm-heading' : 'mm-link');

				if ($hasSub)
				{
					$html .= '<ul class="mm-sublist mm-fly">' . $this->renderList($node->children) . '</ul>';
				}

				$html .= '</li>';
			}

			return $html;
		}

		private function labelHtml($node)
		{
			$out = '';

			if ($node->image !== '')
			{
				$src = $node->image;

				// Joomla 4+ adaugă „#joomlaImage://…” la imaginile din câmpul media.
				$hash = strpos($src, '#');

				if ($hash !== false)
				{
					$src = substr($src, 0, $hash);
				}

				$out .= '<img class="mm-img" src="' . $this->e($src) . '" alt="' . ($node->showText ? '' : $this->e($node->title)) . '">';
			}

			if ($node->showText || $node->image === '')
			{
				$out .= '<span class="mm-text">' . $this->e($node->title) . '</span>';
			}

			return $out;
		}

		private function linkHtml($node, $class)
		{
			$extraClass = trim($node->anchorCss);
			$cls        = $class . ($extraClass !== '' ? ' ' . $extraClass : '');

			if ($node->isHeading || $node->href === '' || $class === 'mm-heading')
			{
				return '<span class="' . $this->e($cls) . '">' . $this->labelHtml($node) . '</span>';
			}

			$attrs = ' href="' . $this->e($node->href) . '"';

			if ($node->anchorTitle !== '')
			{
				$attrs .= ' title="' . $this->e($node->anchorTitle) . '"';
			}

			$rel = $node->anchorRel !== '' ? [$node->anchorRel] : [];

			if ($node->newWin)
			{
				$attrs .= ' target="_blank"';
				$rel[]  = 'noopener';
				$rel[]  = 'noreferrer';
			}

			if ($rel)
			{
				$attrs .= ' rel="' . $this->e(implode(' ', array_unique($rel))) . '"';
			}

			if ($node->isCurrent)
			{
				$attrs .= ' aria-current="page"';
			}

			$label = $this->labelHtml($node);

			if ($node->newWin)
			{
				$label .= '<span class="mm-sr"> ' . $this->e($this->tr('MOD_MEGAMENIU_NEW_WINDOW')) . '</span>';
			}

			return '<a class="' . $this->e($cls) . '"' . $attrs . '>' . $label . '</a>';
		}

		private function pick($value, array $allowed, $default)
		{
			$value = (string) $value;

			return in_array($value, $allowed, true) ? $value : $default;
		}

		/**
		 * Parametrii de aspect devin variabile CSS, cu validare strictă.
		 */
		private function styleVars($params)
		{
			$colors = [
				'barBg'          => '--mm-bar-bg',
				'barColor'       => '--mm-bar-color',
				'barHoverBg'     => '--mm-bar-hover-bg',
				'barHoverColor'  => '--mm-bar-hover-color',
				'activeBg'       => '--mm-active-bg',
				'activeColor'    => '--mm-active-color',
				'dropBg'         => '--mm-drop-bg',
				'dropColor'      => '--mm-drop-color',
				'dropHoverBg'    => '--mm-drop-hover-bg',
				'dropHoverColor' => '--mm-drop-hover-color',
				'dropBorder'     => '--mm-drop-border',
			];

			$vars = [];

			foreach ($colors as $param => $var)
			{
				$value = trim((string) $params->get($param, ''));

				if ($value !== '' && self::isColor($value))
				{
					$vars[] = $var . ':' . $value;
				}
			}

			$fontSize = trim((string) $params->get('fontSize', ''));

			if (preg_match('/^\d+(\.\d+)?(px|rem|em|%)$/', $fontSize))
			{
				$vars[] = '--mm-font-size:' . $fontSize;
			}

			$fontFamily = trim((string) $params->get('fontFamily', ''));

			if ($fontFamily !== '' && preg_match('/^[\p{L}\p{N} ,\'"\-_]+$/u', $fontFamily))
			{
				$vars[] = '--mm-font-family:' . $fontFamily;
			}

			$dropWidth = trim((string) $params->get('dropWidth', 'auto'));

			if (preg_match('/^\d{2,4}(px|rem|em)$/', $dropWidth))
			{
				$vars[] = '--mm-drop-width:' . $dropWidth;
			}

			$radius = $params->get('radius', '');

			if ($radius !== '' && $radius !== null)
			{
				$vars[] = '--mm-radius:' . min(24, max(0, (int) $radius)) . 'px';
			}

			return implode(';', $vars);
		}

		public static function isColor($value)
		{
			return (bool) preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\(\s*[\d.%\s,\/]+\)|hsla?\(\s*[\d.%\s,\/deg]+\)|[a-zA-Z]{3,20})$/', $value);
		}
	}
}
