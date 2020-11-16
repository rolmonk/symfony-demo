<?php

namespace App\DI\Compiler;

use App\Shipping\Method\ShippingMethodsCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ShippingMethodPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ShippingMethodsCollection::class)) {
            return;
        }

        $definition = $container->findDefinition(ShippingMethodsCollection::class);

        $taggedServices = $container->findTaggedServiceIds('app.shipping_method');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addMethod', [new Reference($id)]);
        }
    }
}