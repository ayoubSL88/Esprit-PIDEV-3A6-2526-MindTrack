<?php

namespace App\Service\Habitude;

use App\Message\HabitReminderMessage;
use App\Repository\RappelHabitudeRepository;
use Symfony\Component\Messenger\MessageBusInterface;

final class HabitReminderDispatcher
{
    public function __construct(
        private readonly HabitReminderDueResolver $habitReminderDueResolver,
        private readonly RappelHabitudeRepository $rappelHabitudeRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly string $timezone,
    ) {
    }

    public function dispatchDueReminders(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone($this->timezone));
        $scheduledForMinute = $now->format('Y-m-d H:i');
        $dueReminders = $this->habitReminderDueResolver->resolveDueAt($now);

        foreach ($dueReminders as $reminder) {
            $this->messageBus->dispatch(new HabitReminderMessage($reminder->getIdRappel(), $scheduledForMinute));
            $this->rappelHabitudeRepository->markSentAt(
                $reminder->getIdRappel(),
                new \DateTime($now->format('Y-m-d H:i:s'))
            );
        }

        return count($dueReminders);
    }
}
