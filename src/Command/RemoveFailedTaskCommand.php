<?php

declare(strict_types=1);

namespace SchedulerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SchedulerBundle\SchedulerInterface;
use SchedulerBundle\Task\TaskInterface;
use SchedulerBundle\Worker\WorkerInterface;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
final class RemoveFailedTaskCommand extends Command
{
    private $scheduler;
    private $worker;

    protected static $defaultName = 'scheduler:remove:failed';

    public function __construct(SchedulerInterface $scheduler, WorkerInterface $worker)
    {
        $this->scheduler = $scheduler;
        $this->worker = $worker;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Remove given task from the scheduler')
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'The name of the task to remove'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force the operation without confirmation'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command remove a failed task.

    <info>php %command.full_name%</info>

Use the task-name argument to specify the task to remove:
    <info>php %command.full_name% <task-name></info>

Use the --force option to force the task deletion without asking for confirmation:
    <info>php %command.full_name% <task-name> --force</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');

        $toRemoveTask = $this->worker->getFailedTasks()->get($name);
        if (!$toRemoveTask instanceof TaskInterface) {
            $style->error(\sprintf('The task "%s" does not fails', $name));

            return self::FAILURE;
        }

        if ($input->getOption('force') || $style->confirm('Do you want to permanently remove this task?', true)) {
            try {
                $this->scheduler->unschedule($toRemoveTask->getName());
            } catch (\Throwable $throwable) {
                $style->error([
                    'An error occurred when trying to unschedule the task:',
                    $throwable->getMessage(),
                ]);

                return self::FAILURE;
            }

            $style->success(\sprintf('The task "%s" has been unscheduled', $toRemoveTask->getName()));

            return self::SUCCESS;
        } else {
            $style->note(\sprintf('The task "%s" has not been unscheduled', $toRemoveTask->getName()));

            return self::FAILURE;
        }
    }
}
