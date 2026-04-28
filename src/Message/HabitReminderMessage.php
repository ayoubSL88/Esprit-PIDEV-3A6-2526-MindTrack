<?php

namespace App\Message;

final class HabitReminderMessage
{
    public function __construct(
        private readonly int $reminderId,
        private readonly string $scheduledForMinute,
    ) {
    }

    public function getReminderId(): int
    {
        return $this->reminderId;
    }

    public function getScheduledForMinute(): string
    {
        return $this->scheduledForMinute;
    }
}
