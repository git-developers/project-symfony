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

namespace Bundle\ThemeBundle\Translation\Provider\Resource;

use Bundle\ThemeBundle\Translation\Resource\TranslationResourceInterface;

interface TranslatorResourceProviderInterface
{
    /**
     * @return array|TranslationResourceInterface[]
     */
    public function getResources(): array;

    /**
     * @return array|string[]
     */
    public function getResourcesLocales(): array;
}
