<?php

namespace App\Notification;

use App\Entity\Rappel_habitude;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

final class HabitReminderNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(
        private readonly Rappel_habitude $reminder,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
        parent::__construct(
            sprintf('Rappel habitude: %s', $reminder->getIdHabitude()?->getNom() ?? 'Habitude'),
            ['email']
        );

        $this->content($this->buildContent());
        $this->importance(self::IMPORTANCE_MEDIUM);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): ?EmailMessage
    {
        $habitName = $this->reminder->getIdHabitude()?->getNom() ?? 'votre habitude';

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($recipient->getEmail())
            ->subject($this->getSubject())
            ->text($this->getContent())
            ->html(sprintf(
                '<p>Bonjour,</p><p><strong>%s</strong></p><p>Habitude concernee: <strong>%s</strong></p><p>Horaire prevu: %s</p>',
                htmlspecialchars($this->reminder->getMessage(), ENT_QUOTES),
                htmlspecialchars($habitName, ENT_QUOTES),
                htmlspecialchars($this->reminder->getHeureRappel(), ENT_QUOTES)
            ))
        ;

        return new EmailMessage($email);
    }

    private function buildContent(): string
    {
        $habitName = $this->reminder->getIdHabitude()?->getNom() ?? 'Habitude';

        return sprintf(
            "%s\n\nHabitude: %s\nHeure prevue: %s\nJours: %s",
            $this->reminder->getMessage(),
            $habitName,
            $this->reminder->getHeureRappel(),
            $this->reminder->getJours()
        );
    }
}
