<?php
/**
 * @package     mod_megameniu
 * @copyright   (C) 2026
 * @license     GNU General Public License version 2 or later
 *
 * ADAPTORUL: singurul loc din modul în care contează versiunea de Joomla.
 * Clasele cu namespace (Joomla\CMS\...) există din Joomla 3.8, deci sunt
 * folosite peste tot; diferențele reale sunt doar la încărcarea fișierelor
 * CSS/JS și la obținerea utilizatorului curent.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

if (!class_exists('MegaMeniuCompat'))
{
	class MegaMeniuCompat
	{
		/** @var bool */
		private static $assetsLoaded = false;

		/**
		 * Versiunea majoră Joomla (3, 4, 5, 6 ...).
		 */
		public static function major()
		{
			return (int) explode('.', JVERSION)[0];
		}

		public static function isJ4plus()
		{
			return self::major() >= 4;
		}

		public static function app()
		{
			return Factory::getApplication();
		}

		public static function menu()
		{
			return self::app()->getMenu();
		}

		/**
		 * Utilizatorul curent, fără metode depreciate pe Joomla 4+.
		 */
		public static function user()
		{
			$app = self::app();

			if (method_exists($app, 'getIdentity'))
			{
				$user = $app->getIdentity();

				if ($user)
				{
					return $user;
				}
			}

			// Joomla 3 (și rezervă dacă identitatea nu e încă încărcată).
			return Factory::getUser();
		}

		public static function document()
		{
			$app = self::app();

			if (method_exists($app, 'getDocument') && $app->getDocument())
			{
				return $app->getDocument();
			}

			return Factory::getDocument();
		}

		public static function route($url)
		{
			return Route::_($url);
		}

		public static function text($key)
		{
			return Text::_($key);
		}

		public static function sprintf($key, $arg)
		{
			return Text::sprintf($key, $arg);
		}

		/**
		 * Încarcă fișierul de limbă al modulului (necesar pentru textele din layout
		 * când modulul e randat în afara paginii de administrare).
		 */
		public static function loadLanguage()
		{
			$lang = self::app()->getLanguage();
			$lang->load('mod_megameniu', JPATH_SITE) || $lang->load('mod_megameniu', JPATH_SITE . '/modules/mod_megameniu');
		}

		/**
		 * CSS și JS, o singură dată pe pagină chiar dacă modulul apare de mai multe ori.
		 */
		public static function addAssets()
		{
			if (self::$assetsLoaded)
			{
				return;
			}

			self::$assetsLoaded = true;
			$doc = self::document();

			if (self::isJ4plus() && method_exists($doc, 'getWebAssetManager'))
			{
				$wa = $doc->getWebAssetManager();

				if (!$wa->assetExists('style', 'mod_megameniu'))
				{
					$wa->registerStyle('mod_megameniu', 'mod_megameniu/megameniu.css', ['version' => 'auto']);
				}

				if (!$wa->assetExists('script', 'mod_megameniu'))
				{
					$wa->registerScript('mod_megameniu', 'mod_megameniu/megameniu.js', ['version' => 'auto'], ['defer' => true]);
				}

				$wa->useStyle('mod_megameniu')->useScript('mod_megameniu');

				return;
			}

			// Joomla 3.
			HTMLHelper::_('stylesheet', 'mod_megameniu/megameniu.css', ['version' => 'auto', 'relative' => true]);
			HTMLHelper::_('script', 'mod_megameniu/megameniu.js', ['version' => 'auto', 'relative' => true], ['defer' => 'defer']);
		}

		public static function layoutPath($module, $layout)
		{
			return ModuleHelper::getLayoutPath($module, $layout);
		}
	}
}
