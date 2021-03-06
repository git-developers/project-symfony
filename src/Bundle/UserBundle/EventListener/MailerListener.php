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

namespace Bundle\UserBundle\EventListener;

use Bundle\UserBundle\Mailer\Emails;
use Component\Mailer\Sender\SenderInterface;
use Component\User\Model\UserInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class MailerListener
{
    /**
     * @var SenderInterface
     */
    protected $emailSender;

    /**
     * @param SenderInterface $emailSender
     */
    public function __construct(SenderInterface $emailSender)
    {
        $this->emailSender = $emailSender;
    }

    /**
     * @param GenericEvent $event
     */
    public function sendResetPasswordTokenEmail(GenericEvent $event): void
    {
        $this->sendEmail($event->getSubject(), Emails::RESET_PASSWORD_TOKEN);
    }

    /**
     * @param GenericEvent $event
     */
    public function sendResetPasswordPinEmail(GenericEvent $event): void
    {
        $this->sendEmail($event->getSubject(), Emails::RESET_PASSWORD_PIN);
    }

    /**
     * @param GenericEvent $event
     */
    public function sendVerificationTokenEmail(GenericEvent $event): void
    {
        $this->sendEmail($event->getSubject(), Emails::EMAIL_VERIFICATION_TOKEN);
    }

    /**
     * @param UserInterface $user
     * @param string $emailCode
     */
    protected function sendEmail(UserInterface $user, string $emailCode): void
    {
        $this->emailSender->send($emailCode, [$user->getEmail()], ['user' => $user]);
    }
}
