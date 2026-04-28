<?php

namespace App\MessageHandler;

use App\Entity\Habitude;
use App\Message\HabitReminderMessage;
use App\Notification\HabitReminderNotification;
use App\Repository\RappelHabitudeRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsMessageHandler]
final class HabitReminderMessageHandler
{
    public function __construct(
        private readonly RappelHabitudeRepository $rappelHabitudeRepository,
        private readonly NotifierInterface $notifier,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    public function __invoke(HabitReminderMessage $message): void
    {
        $reminder = $this->rappelHabitudeRepository->find($message->getReminderId());
        if (!$reminder?->getActif()) {
            return;
        }

        $habit = $reminder->getIdHabitude();
        if (!$habit instanceof Habitude) {
            return;
        }

        $owner = $habit->getIdU();
        $email = trim((string) $owner?->getEmailU());
        if ($email === '') {
            return;
        }

        $notification = new HabitReminderNotification($reminder, $this->fromEmail, $this->fromName);
        $this->notifier->send($notification, new Recipient($email));
    }
}
