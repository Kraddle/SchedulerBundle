<?php

declare(strict_types=1);

namespace Tests\SchedulerBundle\Task\Builder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use SchedulerBundle\Task\Builder\CommandBuilder;
use SchedulerBundle\Task\CommandTask;
use SchedulerBundle\Task\TaskInterface;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
final class CommandBuilderTest extends TestCase
{
    public function testBuilderSupport(): void
    {
        $builder = new CommandBuilder();

        self::assertFalse($builder->support('test'));
        self::assertTrue($builder->support('command'));
    }

    /**
     * @dataProvider provideTaskData
     */
    public function testTaskCanBeBuilt(array $options): void
    {
        $builder = new CommandBuilder();

        $task = $builder->build(PropertyAccess::createPropertyAccessor(), $options);

        self::assertInstanceOf(CommandTask::class, $task);
        self::assertSame($options['name'], $task->getName());
        self::assertSame($options['expression'], $task->getExpression());
        self::assertSame($options['command'], $task->getCommand());
        self::assertSame($options['arguments'], $task->getArguments());
        self::assertSame($options['options'], $task->getOptions());
        self::assertSame($options['description'], $task->getDescription());
        self::assertFalse($task->isQueued());
        self::assertNull($task->getTimezone());
        self::assertSame(TaskInterface::ENABLED, $task->getState());
    }

    public function provideTaskData(): \Generator
    {
        yield [
            [
                'name' => 'foo',
                'type' => 'command',
                'command' => 'cache:clear',
                'options' => [
                    '--env' => 'test',
                ],
                'arguments' => [],
                'expression' => '*/5 * * * *',
                'description' => 'A simple cache clear command',
            ],
        ];
        yield [
            [
                'name' => 'bar',
                'type' => 'command',
                'command' => 'cache:clear',
                'options' => [
                    '--env' => 'test',
                ],
                'arguments' => [
                    'test',
                ],
                'expression' => '*/5 * * * *',
                'description' => 'A simple cache clear command',
            ],
        ];
    }
}
