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

namespace Bundle\OneToManyBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Bundle\OneToManyBundle\DependencyInjection\SyliusOneToManyExtension;

final class SyliusOneToManyExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @test
     */
    public function it_sets_configured_grids_as_parameter(): void
    {
        $this->load([
            'grids' => [
                'sylius_admin_tax_category' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => [
                            'class' => 'Component\Taxation\Model\TaxCategory',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasParameter('sylius.grids_definitions', [
            'sylius_admin_tax_category' => [
                'driver' => [
                    'name' => 'doctrine/orm',
                    'options' => [
                        'class' => 'Component\Taxation\Model\TaxCategory',
                    ],
                ],
                'sorting' => [],
                'limits' => [10, 25, 50],
                'fields' => [],
                'filters' => [],
                'actions' => [],
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_aliases_default_services(): void
    {
        $this->load([]);

        $this->assertContainerBuilderHasAlias('sylius.grid.renderer', 'sylius.grid.renderer.twig');
        $this->assertContainerBuilderHasAlias('sylius.grid.data_extractor', 'sylius.grid.data_extractor.property_access');
    }

    /**
     * @test
     */
    public function it_always_defines_template_parameters(): void
    {
        $this->load([]);

        $this->assertContainerBuilderHasParameter('sylius.grid.templates.filter', []);
        $this->assertContainerBuilderHasParameter('sylius.grid.templates.action', []);
    }

    /**
     * @test
     */
    public function it_sets_filter_templates_as_parameters(): void
    {
        $this->load([
            'templates' => [
                'filter' => [
                    'string' => 'AppBundle:OneToMany/Filter:string.html.twig',
                    'date' => 'AppBundle:OneToMany/Filter:date.html.twig',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasParameter('sylius.grid.templates.filter', [
            'string' => 'AppBundle:OneToMany/Filter:string.html.twig',
            'date' => 'AppBundle:OneToMany/Filter:date.html.twig',
        ]);
    }

    /**
     * @test
     */
    public function it_sets_action_templates_as_parameters(): void
    {
        $this->load([
            'templates' => [
                'action' => [
                    'create' => 'AppBundle:OneToMany/Filter:create.html.twig',
                    'update' => 'AppBundle:OneToMany/Filter:update.html.twig',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasParameter('sylius.grid.templates.action', [
            'create' => 'AppBundle:OneToMany/Filter:create.html.twig',
            'update' => 'AppBundle:OneToMany/Filter:update.html.twig',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensions(): array
    {
        return [
            new SyliusOneToManyExtension(),
        ];
    }
}
