<?php
/**
 * @package     mod_megameniu
 * @copyright   (C) 2026
 * @license     GNU General Public License version 2 or later
 *
 * Citește elementele unui meniu Joomla și construiește arborele de afișat.
 * Ordinea este cea din administrare; nimic nu se reordonează.
 */

defined('_JEXEC') or die;

if (!class_exists('MegaMeniuTree'))
{
	class MegaMeniuTree
	{
		/**
		 * @param   \Joomla\Registry\Registry  $params  Parametrii modulului
		 *
		 * @return  array  ['items' => noduri de nivel 1 (stdClass), 'activeId' => int]
		 */
		public static function build($params)
		{
			$menutype = (string) $params->get('menutype', '');

			if ($menutype === '')
			{
				return ['items' => [], 'activeId' => 0];
			}

			$menu    = MegaMeniuCompat::menu();
			$active  = $menu->getActive();
			$default = $menu->getDefault();
			$current = $active ? $active : $default;

			$activeId = $current ? (int) $current->id : 0;
			$path     = ($current && isset($current->tree) && is_array($current->tree)) ? array_map('intval', $current->tree) : [];

			$items = $menu->getItems('menutype', $menutype);

			if (!$items)
			{
				return ['items' => [], 'activeId' => $activeId];
			}

			return [
				'items'    => self::buildFromItems($items, $params, $activeId, $path),
				'activeId' => $activeId,
			];
		}

		/**
		 * Separat de build() ca să poată fi testat fără Joomla.
		 *
		 * @param   array   $items     Elemente de meniu (MenuItem sau obiecte compatibile)
		 * @param   object  $params    Registry cu parametrii modulului
		 * @param   int     $activeId  ID-ul elementului curent
		 * @param   int[]   $path      ID-urile de la rădăcină la elementul curent
		 */
		public static function buildFromItems(array $items, $params, $activeId, array $path)
		{
			$start       = max(1, (int) $params->get('startLevel', 1));
			$end         = (int) $params->get('endLevel', 0);
			$showAll     = (int) $params->get('showAllChildren', 1) === 1;
			$levels      = self::viewLevels();
			$hidden      = [];
			$nodes       = [];
			$order       = [];

			foreach ($items as $item)
			{
				$id       = (int) $item->id;
				$parentId = (int) $item->parent_id;
				$level    = (int) $item->level;

				// Copiii unui element ascuns sunt și ei ascunși.
				if (isset($hidden[$parentId]))
				{
					$hidden[$id] = true;
					continue;
				}

				$itemParams = self::itemParams($item);

				$published = isset($item->published) ? (int) $item->published : 1;
				$access    = isset($item->access) ? (int) $item->access : 1;

				if ($published !== 1
					|| ($levels !== null && !in_array($access, $levels, true))
					|| (int) $itemParams->get('menu_show', 1) === 0)
				{
					$hidden[$id] = true;
					continue;
				}

				if ($level < $start || ($end > 0 && $level > $end))
				{
					continue;
				}

				// Nivel de start > 1: doar ramura elementului curent.
				if ($start > 1 && (!isset($path[$start - 2]) || !self::isDescendantOf($item, (int) $path[$start - 2], $items)))
				{
					continue;
				}

				$node = self::makeNode($item, $itemParams, $activeId, $path);
				$nodes[$id] = $node;
				$order[]    = $id;
			}

			// Legarea în arbore, păstrând ordinea primită.
			$roots = [];

			foreach ($order as $id)
			{
				$node = $nodes[$id];

				if ($node->level === $start || !isset($nodes[$node->parentId]))
				{
					if ($node->level === $start)
					{
						$roots[] = $node;
					}

					continue;
				}

				$parent = $nodes[$node->parentId];

				// „Afișează subelementele” = Nu: doar ramura activă se deschide.
				if (!$showAll && $node->level > $start && !in_array($parent->id, $path, true))
				{
					continue;
				}

				$parent->children[] = $node;
			}

			return $roots;
		}

		private static function isDescendantOf($item, $ancestorId, array $items)
		{
			if (isset($item->tree) && is_array($item->tree))
			{
				return in_array($ancestorId, array_map('intval', $item->tree), true) && (int) $item->id !== $ancestorId;
			}

			// Rezervă: urcare prin parent_id.
			$byId = [];

			foreach ($items as $i)
			{
				$byId[(int) $i->id] = $i;
			}

			$pid = (int) $item->parent_id;

			while ($pid > 1 && isset($byId[$pid]))
			{
				if ($pid === $ancestorId)
				{
					return true;
				}

				$pid = (int) $byId[$pid]->parent_id;
			}

			return false;
		}

		private static function viewLevels()
		{
			if (!class_exists('MegaMeniuCompat') || !defined('JVERSION'))
			{
				return null;
			}

			try
			{
				$user = MegaMeniuCompat::user();

				return $user ? array_map('intval', $user->getAuthorisedViewLevels()) : null;
			}
			catch (\Throwable $e)
			{
				return null;
			}
		}

		private static function itemParams($item)
		{
			if (method_exists($item, 'getParams'))
			{
				return $item->getParams();
			}

			return $item->params;
		}

		private static function makeNode($item, $p, $activeId, array $path)
		{
			$node            = new \stdClass;
			$node->id        = (int) $item->id;
			$node->parentId  = (int) $item->parent_id;
			$node->level     = (int) $item->level;
			$node->title     = (string) $item->title;
			$node->type      = (string) $item->type;
			$node->children  = [];
			$node->isCurrent = $node->id === (int) $activeId;
			$node->isActive  = in_array($node->id, $path, true);
			$node->isHeading = in_array($node->type, ['separator', 'heading'], true);

			$node->anchorTitle = (string) $p->get('menu-anchor_title', '');
			$node->anchorCss   = (string) $p->get('menu-anchor_css', '');
			$node->anchorRel   = (string) $p->get('menu-anchor_rel', '');
			$node->image       = (string) $p->get('menu_image', '');
			$node->showText    = (int) $p->get('menu_text', 1) === 1;

			$browserNav   = isset($item->browserNav) ? (int) $item->browserNav : 0;
			$node->newWin = in_array($browserNav, [1, 2], true);

			$node->href = $node->isHeading ? '' : self::href($item, $p);

			// Un alias către elementul curent se marchează și el ca activ.
			if ($node->type === 'alias' && (int) $p->get('aliasoptions', 0) === (int) $activeId)
			{
				$node->isCurrent = true;
				$node->isActive  = true;
			}

			return $node;
		}

		/**
		 * Linkul elementului, după aceleași reguli ca mod_menu din Joomla.
		 */
		private static function href($item, $p)
		{
			$link = (string) $item->link;

			switch ($item->type)
			{
				case 'url':
					if (strpos($link, 'index.php?') === 0 && strpos($link, 'Itemid=') === false)
					{
						$link .= '&Itemid=' . (int) $item->id;
					}
					break;

				case 'alias':
					$link = 'index.php?Itemid=' . (int) $p->get('aliasoptions', 0);
					break;

				default:
					$link = 'index.php?Itemid=' . (int) $item->id;
					break;
			}

			if ($link === '')
			{
				return '';
			}

			// Linkurile externe absolute (http, https, mailto, tel, //) rămân neschimbate.
			if (preg_match('#^(?:[a-z][a-z0-9+.\-]*:|//)#i', $link) && stripos($link, 'index.php?') !== 0)
			{
				return $link;
			}

			if (strpos($link, 'index.php?') === 0 && class_exists('MegaMeniuCompat') && defined('JVERSION'))
			{
				return MegaMeniuCompat::route($link);
			}

			return $link;
		}
	}
}
