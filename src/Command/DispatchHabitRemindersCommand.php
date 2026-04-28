<?php

namespace App\Command;

use App\Service\Habitude\HabitReminderDispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

#[AsCommand(name: 'app:habit-reminders:dispatch', description: 'Dispatch due habit reminders to Messenger.')]
#[AsScheduledTask('* * * * *', description: 'Dispatch due habit reminders every minute.')]
final class DispatchHabitRemindersCommand extends Command
{
    public function __construct(
        private readonly HabitReminderDispatcher $habitReminderDispatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->habitReminderDispatcher->dispatchDueReminders();

        $io->success(sprintf('%d rappel(s) habitude dispatches.', $count));

        return Command::SUCCESS;
    }
}
