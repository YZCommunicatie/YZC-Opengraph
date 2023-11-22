<?php
/*
 *  package: YZC Open Graph
 *  copyright: Copyright (c) 2023. Jeroen Moolenschot | YZCommunicatie
 *  license: GNU General Public License version 2 or later
 *  link: https://www.yzcommunicatie.nl
 */

defined('_JEXEC') or die;

use YZCommunicatie\Plugin\System\Opengraph\Extension\Opengraph;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class implements ServiceProviderInterface {

    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $config = (array)PluginHelper::getPlugin('system', 'opengraph');
                $dispatcher = $container->get(DispatcherInterface::class);

                $plugin = new Opengraph($dispatcher, $config);

                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
