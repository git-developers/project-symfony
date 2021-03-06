<?php

declare(strict_types=1);

namespace Bundle\ThemeBundle\Translation\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TranslatorFallbackLocalesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        try {
            $symfonyTranslator = $container->findDefinition('translator.default');
            $syliusTranslator = $container->findDefinition('sylius.theme.translation.translator');
        } catch (\InvalidArgumentException $exception) {
            return;
        }

        $methodCalls = array_filter($symfonyTranslator->getMethodCalls(), function (array $methodCall): bool {
            return 'setFallbackLocales' === $methodCall[0];
        });

        foreach ($methodCalls as $methodCall) {
            $syliusTranslator->addMethodCall($methodCall[0], $methodCall[1]);
        }
    }
}
