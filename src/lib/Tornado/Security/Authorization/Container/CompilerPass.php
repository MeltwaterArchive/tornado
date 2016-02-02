<?php

namespace Tornado\Security\Authorization\Container;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * CompilerPass
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Security\Authorization\Container
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class CompilerPass implements CompilerPassInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('security.authorization.access_decision_manager')) {
            return;
        }

        $definition = $container->findDefinition(
            'security.authorization.access_decision_manager'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'voter'
        );

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addVoter',
                [new Reference($id)]
            );
        }
    }
}
