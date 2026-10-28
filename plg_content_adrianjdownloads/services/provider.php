<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Adrian\Plugin\Content\Adrianjdownloads\Extension\Adrianjdownloads;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $config = (array) PluginHelper::getPlugin('content', 'adrianjdownloads');

                // Joomla 4 plugins also receive the dispatcher; Joomla 5+ only the config.
                $plugin = version_compare(JVERSION, '5.0', '>=')
                    ? new Adrianjdownloads($config)
                    : new Adrianjdownloads($container->get(DispatcherInterface::class), $config);
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
