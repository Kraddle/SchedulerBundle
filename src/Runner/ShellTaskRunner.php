<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SchedulerBundle\Runner;

use Symfony\Component\Process\Process;
use SchedulerBundle\Task\Output;
use SchedulerBundle\Task\ShellTask;
use SchedulerBundle\Task\TaskInterface;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 *
 * @experimental in 5.3
 */
final class ShellTaskRunner implements RunnerInterface
{
    /**
     * {@inheritdoc}
     */
    public function run(TaskInterface $task): Output
    {
        $process = new Process(
            $task->getCommand(),
            $task->getCwd(),
            $task->getEnvironmentVariables(),
            null,
            $task->getTimeout()
        );

        $task->setExecutionState(TaskInterface::RUNNING);

        if ($task->mustRunInBackground()) {
            $process->run(null, $task->getEnvironmentVariables());

            $task->setExecutionState(TaskInterface::INCOMPLETE);

            return new Output($task, 'Task is running in background, output is not available');
        }

        $exitCode = $process->run(null, $task->getEnvironmentVariables());

        $output = $task->isOutput() ? \trim($process->getOutput()) : null;
        if (0 !== $exitCode) {
            $task->setExecutionState(TaskInterface::ERRORED);

            return new Output($task, $process->getErrorOutput(), Output::ERROR);
        }

        $task->setExecutionState(TaskInterface::SUCCEED);

        return new Output($task, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function support(TaskInterface $task): bool
    {
        return $task instanceof ShellTask;
    }
}
