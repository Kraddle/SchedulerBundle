<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\SchedulerBundle\Test\Constraint;

use PHPUnit\Framework\TestCase;
use SchedulerBundle\Event\TaskEventList;
use SchedulerBundle\Event\TaskFailedEvent;
use SchedulerBundle\Task\FailedTask;
use SchedulerBundle\Task\TaskInterface;
use SchedulerBundle\Test\Constraint\TaskFailed;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
final class TaskFailedTest extends TestCase
{
    public function testConstraintCannotMatch(): void
    {
        $list = new TaskEventList();

        $constraint = new TaskFailed(1);

        static::assertFalse($constraint->evaluate($list, '', true));
    }

    public function testConstraintCanMatch(): void
    {
        $task = $this->createMock(TaskInterface::class);

        $failedTask = new FailedTask($task, 'error');

        $list = new TaskEventList();
        $list->addEvent(new TaskFailedEvent($failedTask));

        $constraint = new TaskFailed(1);

        static::assertTrue($constraint->evaluate($list, '', true));
    }
}