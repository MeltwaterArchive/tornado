<?php

namespace Tornado\Application\Flash;

use MD\Foundation\Debug\Debugger;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Tornado\Controller\Brand\DataAwareInterface;

/**
 * A compiler pass to associate the Flash provider at runtime
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Application\Flash
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class CompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $tag = 'flash_aware';

        $flash = new Reference('flash');

        $taggedServices = $container->findTaggedServiceIds($tag);
        foreach (array_keys($taggedServices) as $id) {
            $definition = $container->findDefinition($id);
            $definition->addMethodCall('setFlashProvider', [$flash]);
        }
    }
}
