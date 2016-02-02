<?php

namespace Tornado\Controller;

use MD\Foundation\Debug\Debugger;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Tornado\Controller\ProjectDataAwareInterface;

/**
 * Injects required services to all services tagged with `project_data_aware`
 * using setter injection.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Controller
 * @author      Michał Pałys-Dudek
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class ProjectDataAwareCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $tag = 'project_data_aware';

        $brandRepository = new Reference('organization.brand.repository');
        $projectRepository = new Reference('project.repository');
        $workbookRepository = new Reference('project.workbook.repository');
        $worksheetRepository = new Reference('project.worksheet.repository');
        $authorizationManager = new Reference('security.authorization.access_decision_manager');

        $taggedServices = $container->findTaggedServiceIds($tag);
        foreach (array_keys($taggedServices) as $id) {
            $definition = $container->findDefinition($id);

            if (!Debugger::isImplementing($definition->getClass(), ProjectDataAwareInterface::class)) {
                throw new \LogicException(sprintf(
                    'Services tagged with "%s" tag must implement %s interface.',
                    $tag,
                    ProjectDataAwareInterface::class
                ));
            }

            $definition->addMethodCall('setBrandRepository', [$brandRepository]);
            $definition->addMethodCall('setProjectRepository', [$projectRepository]);
            $definition->addMethodCall('setWorkbookRepository', [$workbookRepository]);
            $definition->addMethodCall('setWorksheetRepository', [$worksheetRepository]);
            $definition->addMethodCall('setAuthorizationManager', [$authorizationManager]);
        }
    }
}
