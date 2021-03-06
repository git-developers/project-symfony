<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bundle\UserBundle\DependencyInjection;

use Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Bundle\UserBundle\EventListener\UserDeleteListener;
use Bundle\UserBundle\EventListener\UserLastLoginSubscriber;
use Bundle\UserBundle\EventListener\UserReloaderListener;
use Bundle\UserBundle\Provider\AbstractUserProvider;
use Bundle\UserBundle\Provider\EmailProvider;
use Bundle\UserBundle\Provider\UsernameOrEmailProvider;
use Bundle\UserBundle\Provider\UsernameProvider;
use Bundle\UserBundle\Reloader\UserReloader;
use Component\User\Security\Checker\TokenUniquenessChecker;
use Component\User\Security\Generator\UniquePinGenerator;
use Component\User\Security\Generator\UniqueTokenGenerator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class SyliusUserExtension extends AbstractResourceExtension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load(sprintf('services/integrations/%s.xml', $config['driver']));

        $this->registerResources('sylius', $config['driver'], $this->resolveResources($config['resources'], $container), $container);

        $loader->load('services.xml');

        $this->createServices($config['resources'], $container);
    }

    /**
     * @param array $resources
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function resolveResources(array $resources, ContainerBuilder $container): array
    {
        $container->setParameter('sylius.user.users', $resources);

        $resolvedResources = [];
        foreach ($resources as $variableName => $variableConfig) {
            foreach ($variableConfig as $resourceName => $resourceConfig) {
                if (is_array($resourceConfig)) {
                    $resolvedResources[$variableName . '_' . $resourceName] = $resourceConfig;
                }
            }
        }

        return $resolvedResources;
    }

    /**
     * @param array $resources
     * @param ContainerBuilder $container
     */
    private function createServices(array $resources, ContainerBuilder $container): void
    {
        foreach ($resources as $userType => $config) {
            $userClass = $config['user']['classes']['model'];

            $this->createTokenGenerators($userType, $config['user'], $container);
            $this->createReloaders($userType, $container);
            $this->createLastLoginListeners($userType, $userClass, $container);
            $this->createProviders($userType, $userClass, $container);
            $this->createUserDeleteListeners($userType, $container);
        }
    }

    /**
     * @param string $userType
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createTokenGenerators(string $userType, array $config, ContainerBuilder $container): void
    {
        $this->createUniquenessCheckers($userType, $config, $container);

        $container->setDefinition(
            sprintf('sylius.%s_user.token_generator.password_reset', $userType),
            $this->createTokenGeneratorDefinition(
                UniqueTokenGenerator::class,
                [
                    new Reference('sylius.random_generator'),
                    new Reference(sprintf('sylius.%s_user.token_uniqueness_checker.password_reset', $userType)),
                    $config['resetting']['token']['length'],
                ]
            )
        );

        $container->setDefinition(
            sprintf('sylius.%s_user.pin_generator.password_reset', $userType),
            $this->createTokenGeneratorDefinition(
                UniquePinGenerator::class,
                [
                    new Reference('sylius.random_generator'),
                    new Reference(sprintf('sylius.%s_user.pin_uniqueness_checker.password_reset', $userType)),
                    $config['resetting']['pin']['length'],
                ]
            )
        );

        $container->setDefinition(
            sprintf('sylius.%s_user.token_generator.email_verification', $userType),
            $this->createTokenGeneratorDefinition(
                UniqueTokenGenerator::class,
                [
                    new Reference('sylius.random_generator'),
                    new Reference(sprintf('sylius.%s_user.token_uniqueness_checker.email_verification', $userType)),
                    $config['verification']['token']['length'],
                ]
            )
        );
    }

    /**
     * @param string $generatorClass
     * @param array $arguments
     *
     * @return Definition
     */
    private function createTokenGeneratorDefinition(string $generatorClass, array $arguments): Definition
    {
        $generatorDefinition = new Definition($generatorClass);
        $generatorDefinition->setArguments($arguments);

        return $generatorDefinition;
    }

    /**
     * @param string $userType
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createUniquenessCheckers(string $userType, array $config, ContainerBuilder $container): void
    {
        $repositoryServiceId = sprintf('sylius.repository.%s_user', $userType);

        $resetPasswordTokenUniquenessCheckerDefinition = new Definition(TokenUniquenessChecker::class);
        $resetPasswordTokenUniquenessCheckerDefinition->addArgument(new Reference($repositoryServiceId));
        $resetPasswordTokenUniquenessCheckerDefinition->addArgument($config['resetting']['token']['field_name']);
        $container->setDefinition(
            sprintf('sylius.%s_user.token_uniqueness_checker.password_reset', $userType),
            $resetPasswordTokenUniquenessCheckerDefinition
        );

        $resetPasswordPinUniquenessCheckerDefinition = new Definition(TokenUniquenessChecker::class);
        $resetPasswordPinUniquenessCheckerDefinition->addArgument(new Reference($repositoryServiceId));
        $resetPasswordPinUniquenessCheckerDefinition->addArgument($config['resetting']['pin']['field_name']);
        $container->setDefinition(
            sprintf('sylius.%s_user.pin_uniqueness_checker.password_reset', $userType),
            $resetPasswordPinUniquenessCheckerDefinition
        );

        $emailVerificationTokenUniquenessCheckerDefinition = new Definition(TokenUniquenessChecker::class);
        $emailVerificationTokenUniquenessCheckerDefinition->addArgument(new Reference($repositoryServiceId));
        $emailVerificationTokenUniquenessCheckerDefinition->addArgument($config['verification']['token']['field_name']);
        $container->setDefinition(
            sprintf('sylius.%s_user.token_uniqueness_checker.email_verification', $userType),
            $emailVerificationTokenUniquenessCheckerDefinition
        );
    }

    /**
     * @param string $userType
     * @param ContainerBuilder $container
     */
    private function createReloaders(string $userType, ContainerBuilder $container): void
    {
        $managerServiceId = sprintf('sylius.manager.%s_user', $userType);
        $reloaderServiceId = sprintf('sylius.%s_user.reloader', $userType);
        $reloaderListenerServiceId = sprintf('sylius.listener.%s_user.reloader', $userType);

        $userReloaderDefinition = new Definition(UserReloader::class);
        $userReloaderDefinition->addArgument(new Reference($managerServiceId));
        $container->setDefinition($reloaderServiceId, $userReloaderDefinition);

        $userReloaderListenerDefinition = new Definition(UserReloaderListener::class);
        $userReloaderListenerDefinition->addArgument(new Reference($reloaderServiceId));
        $userReloaderListenerDefinition->addTag('kernel.event_listener', ['event' => sprintf('sylius.%s_user.post_create', $userType), 'method' => 'reloadUser']);
        $userReloaderListenerDefinition->addTag('kernel.event_listener', ['event' => sprintf('sylius.%s_user.post_update', $userType), 'method' => 'reloadUser']);
        $container->setDefinition($reloaderListenerServiceId, $userReloaderListenerDefinition);
    }

    /**
     * @param string $userType
     * @param string $userClass
     * @param ContainerBuilder $container
     */
    private function createLastLoginListeners(string $userType, string $userClass, ContainerBuilder $container): void
    {
        $managerServiceId = sprintf('sylius.manager.%s_user', $userType);
        $lastLoginListenerServiceId = sprintf('sylius.listener.%s_user_last_login', $userType);

        $lastLoginListenerDefinition = new Definition(UserLastLoginSubscriber::class);
        $lastLoginListenerDefinition->setArguments([new Reference($managerServiceId), $userClass]);
        $lastLoginListenerDefinition->addTag('kernel.event_subscriber');
        $container->setDefinition($lastLoginListenerServiceId, $lastLoginListenerDefinition);
    }

    /**
     * @param string $userType
     * @param ContainerBuilder $container
     */
    public function createUserDeleteListeners(string $userType, ContainerBuilder $container): void
    {
        $userDeleteListenerServiceId = sprintf('sylius.listener.%s_user_delete', $userType);
        $userPreDeleteEventName = sprintf('sylius.%s_user.pre_delete', $userType);

        $userDeleteListenerDefinition = new Definition(UserDeleteListener::class);
        $userDeleteListenerDefinition->addArgument(new Reference('security.token_storage'));
        $userDeleteListenerDefinition->addArgument(new Reference('session'));
        $userDeleteListenerDefinition->addTag('kernel.event_listener', ['event' => $userPreDeleteEventName, 'method' => 'deleteUser']);
        $container->setDefinition($userDeleteListenerServiceId, $userDeleteListenerDefinition);
    }

    /**
     * @param string $userType
     * @param string $userModel
     * @param ContainerBuilder $container
     */
    private function createProviders(string $userType, string $userModel, ContainerBuilder $container): void
    {
        $repositoryServiceId = sprintf('sylius.repository.%s_user', $userType);
        $abstractProviderServiceId = sprintf('sylius.%s_user_provider', $userType);
        $providerEmailBasedServiceId = sprintf('sylius.%s_user_provider.email_based', $userType);
        $providerNameBasedServiceId = sprintf('sylius.%s_user_provider.name_based', $userType);
        $providerEmailOrNameBasedServiceId = sprintf('sylius.%s_user_provider.email_or_name_based', $userType);

        $abstractProviderDefinition = new Definition(AbstractUserProvider::class);
        $abstractProviderDefinition->setAbstract(true);
        $abstractProviderDefinition->setLazy(true);
        $abstractProviderDefinition->addArgument($userModel);
        $abstractProviderDefinition->addArgument(new Reference($repositoryServiceId));
        $abstractProviderDefinition->addArgument(new Reference('sylius.canonicalizer'));
        $container->setDefinition($abstractProviderServiceId, $abstractProviderDefinition);

        $emailBasedProviderDefinition = new DefinitionDecorator($abstractProviderServiceId);
        $emailBasedProviderDefinition->setClass(EmailProvider::class);
        $container->setDefinition($providerEmailBasedServiceId, $emailBasedProviderDefinition);

        $nameBasedProviderDefinition = new DefinitionDecorator($abstractProviderServiceId);
        $nameBasedProviderDefinition->setClass(UsernameProvider::class);
        $container->setDefinition($providerNameBasedServiceId, $nameBasedProviderDefinition);

        $emailOrNameBasedProviderDefinition = new DefinitionDecorator($abstractProviderServiceId);
        $emailOrNameBasedProviderDefinition->setClass(UsernameOrEmailProvider::class);
        $container->setDefinition($providerEmailOrNameBasedServiceId, $emailOrNameBasedProviderDefinition);
    }
}
