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

namespace spec\Bundle\UserBundle\Reloader;

use Doctrine\Common\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Bundle\UserBundle\Reloader\UserReloaderInterface;
use Component\User\Model\UserInterface;

final class UserReloaderSpec extends ObjectBehavior
{
    function let(ObjectManager $objectManager): void
    {
        $this->beConstructedWith($objectManager);
    }

    function it_implements_user_reloader_interface(): void
    {
        $this->shouldImplement(UserReloaderInterface::class);
    }

    function it_reloads_user(ObjectManager $objectManager, UserInterface $user): void
    {
        $objectManager->refresh($user)->shouldBeCalled();

        $this->reloadUser($user);
    }
}
