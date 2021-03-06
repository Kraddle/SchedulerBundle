<?php

declare(strict_types=1);

namespace SchedulerBundle\Task;

use Cron\CronExpression;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SchedulerBundle\Exception\InvalidArgumentException;
use SchedulerBundle\Exception\LogicException;
use function array_key_exists;
use function in_array;
use function sprintf;
use function strtotime;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
abstract class AbstractTask implements TaskInterface
{
    private $name;
    protected $options;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param array $options           The default $options allowed in every task
     * @param array $additionalOptions An array of key => types that define extra allowed $options (ex: ['timezone' => 'string'])
     */
    protected function defineOptions(array $options = [], array $additionalOptions = []): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'arrival_time' => null,
            'background' => false,
            'description' => null,
            'expression' => '* * * * *',
            'execution_absolute_deadline' => null,
            'execution_computation_time' => null,
            'execution_delay' => null,
            'execution_memory_usage' => null,
            'execution_period' => null,
            'execution_relative_deadline' => null,
            'execution_start_date' => null,
            'execution_end_date' => null,
            'execution_start_time' => null,
            'execution_end_time' => null,
            'last_execution' => null,
            'max_duration' => null,
            'nice' => null,
            'output' => false,
            'priority' => 0,
            'queued' => false,
            'scheduled_at' => null,
            'single_run' => false,
            'state' => TaskInterface::ENABLED,
            'execution_state' => null,
            'tags' => [],
            'timezone' => null,
            'tracked' => true,
            'type' => null,
        ]);

        $resolver->setAllowedTypes('arrival_time', [DateTimeImmutable::class, 'null']);
        $resolver->setAllowedTypes('background', ['bool']);
        $resolver->setAllowedTypes('description', ['string', 'null']);
        $resolver->setAllowedTypes('expression', ['string']);
        $resolver->setAllowedTypes('execution_absolute_deadline', [DateInterval::class, 'null']);
        $resolver->setAllowedTypes('execution_computation_time', ['float', 'null']);
        $resolver->setAllowedTypes('execution_delay', ['int', 'null']);
        $resolver->setAllowedTypes('execution_memory_usage', ['int', 'null']);
        $resolver->setAllowedTypes('execution_relative_deadline', [DateInterval::class, 'null']);
        $resolver->setAllowedTypes('execution_start_date', ['string', 'null']);
        $resolver->setAllowedTypes('execution_end_date', ['string', 'null']);
        $resolver->setAllowedTypes('execution_start_time', [DateTimeImmutable::class, 'null']);
        $resolver->setAllowedTypes('execution_end_time', [DateTimeImmutable::class, 'null']);
        $resolver->setAllowedTypes('last_execution', [DateTimeImmutable::class, 'null']);
        $resolver->setAllowedTypes('max_duration', ['float', 'null']);
        $resolver->setAllowedTypes('nice', ['int', 'null']);
        $resolver->setAllowedTypes('output', ['bool']);
        $resolver->setAllowedTypes('priority', ['int']);
        $resolver->setAllowedTypes('queued', ['bool']);
        $resolver->setAllowedTypes('scheduled_at', [DateTimeImmutable::class, 'null']);
        $resolver->setAllowedTypes('single_run', ['bool']);
        $resolver->setAllowedTypes('state', ['string']);
        $resolver->setAllowedTypes('execution_state', ['null', 'string']);
        $resolver->setAllowedTypes('tags', ['string[]']);
        $resolver->setAllowedTypes('tracked', ['bool']);
        $resolver->setAllowedTypes('timezone', [DateTimeZone::class, 'null']);

        $resolver->setAllowedValues('expression', function (string $expression): bool {
            return $this->validateExpression($expression);
        });
        $resolver->setAllowedValues('execution_start_date', function (string $executionStartDate = null): bool {
            return $this->validateDate($executionStartDate);
        });
        $resolver->setAllowedValues('execution_end_date', function (string $executionEndDate = null): bool {
            return $this->validateDate($executionEndDate);
        });
        $resolver->setAllowedValues('nice', function (int $nice = null): bool {
            return $this->validateNice($nice);
        });
        $resolver->setAllowedValues('priority', function (int $priority): bool {
            return $this->validatePriority($priority);
        });
        $resolver->setAllowedValues('state', function (string $state): bool {
            return $this->validateState($state);
        });
        $resolver->setAllowedValues('execution_state', function (string $executionState = null): bool {
            return $this->validateExecutionState($executionState);
        });

        $resolver->setInfo('arrival_time', '[INTERNAL] The time when the task is retrieved in order to execute it');
        $resolver->setInfo('execution_absolute_deadline', '[INTERNAL] An addition of the "execution_start_time" and "execution_relative_deadline" options');
        $resolver->setInfo('execution_computation_time', '[Internal] Used to store the execution duration of a task');
        $resolver->setInfo('execution_delay', 'The delay in microseconds applied before the task execution');
        $resolver->setInfo('execution_memory_usage', '[INTERNAL] The amount of memory used described as an integer');
        $resolver->setInfo('execution_period', '[Internal] Used to store the period during a task has been executed thanks to deadline sort');
        $resolver->setInfo('execution_relative_deadline', 'The estimated ending date of the task execution, must be a \DateInterval');
        $resolver->setInfo('execution_start_time', 'The start date since the task can be executed');
        $resolver->setInfo('execution_end_time', 'The limit date since the task must not be executed');
        $resolver->setInfo('execution_start_time', '[Internal] The start time of the task execution, mostly used by the internal sort process');
        $resolver->setInfo('execution_end_time', '[Internal] The date where the execution is finished, mostly used by the internal sort process');
        $resolver->setInfo('last_execution', 'Define the last execution date of the task');
        $resolver->setInfo('max_duration', 'Define the maximum amount of time allowed to this task to be executed, mostly used for internal sort process');
        $resolver->setInfo('nice', 'Define a priority for this task inside a runner, a high value means a lower priority in the runner');
        $resolver->setInfo('output', 'Define if the output of the task must be returned by the worker');
        $resolver->setInfo('scheduled_at', 'Define the date where the task has been scheduled');
        $resolver->setInfo('single_run', 'Define if the task must run only once, if so, the task is unscheduled from the scheduler once executed');
        $resolver->setInfo('state', 'Define the state of the task, mainly used by the worker and transports to execute enabled tasks');
        $resolver->setInfo('execution_state', '[INTERNAL] Define the state of the task during the execution phase, mainly used by the worker');
        $resolver->setInfo('queued', 'Define if the task need to be dispatched to a "symfony/messenger" queue');
        $resolver->setInfo('tags', 'A set of tag that can be used to sort tasks');
        $resolver->setInfo('tracked', 'Define if the task will be tracked during execution, this option enable the "duration" sort');
        $resolver->setInfo('timezone', 'Define the timezone used by the task, this value is set by the Scheduler and can be overridden');

        if (empty($additionalOptions)) {
            $this->options = $resolver->resolve($options);
        }

        foreach ($additionalOptions as $additionalOption => $allowedTypes) {
            $resolver->setDefined($additionalOption);
            $resolver->setAllowedTypes($additionalOption, $allowedTypes);
        }

        $this->options = $resolver->resolve($options);
    }

    public function setName(string $name): TaskInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setArrivalTime(DateTimeImmutable $arrivalTime = null): TaskInterface
    {
        $this->options['arrival_time'] = $arrivalTime;

        return $this;
    }

    public function getArrivalTime(): ?DateTimeImmutable
    {
        return $this->options['arrival_time'];
    }

    public function setBackground(bool $background): TaskInterface
    {
        if (!$this instanceof ShellTask && $background) {
            throw new InvalidArgumentException(sprintf('The background option is available only for task of type %s', ShellTask::class));
        }

        $this->options['background'] = $background;

        return $this;
    }

    public function mustRunInBackground(): bool
    {
        return $this->options['background'];
    }

    public function setDescription(string $description = null): TaskInterface
    {
        $this->options['description'] = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->options['description'];
    }

    public function setExpression(string $expression): TaskInterface
    {
        if (!$this->validateExpression($expression)) {
            throw new InvalidArgumentException('This expression is not valid');
        }

        $this->options['expression'] = $expression;

        return $this;
    }

    public function getExpression(): string
    {
        return $this->options['expression'];
    }

    public function setExecutionAbsoluteDeadline(DateInterval $executionAbsoluteDeadline = null): TaskInterface
    {
        $this->options['execution_absolute_deadline'] = $executionAbsoluteDeadline;

        return $this;
    }

    public function getExecutionAbsoluteDeadline(): ?DateInterval
    {
        return $this->options['execution_absolute_deadline'];
    }

    public function getExecutionComputationTime(): ?float
    {
        return $this->options['execution_computation_time'];
    }

    /**
     * {@internal Used by the TaskExecutionTracker and the ExecutionModeOrchestrator to evaluate the time spent by the worker to execute this task and sort it if required}.
     */
    public function setExecutionComputationTime(float $executionComputationTime = null): TaskInterface
    {
        $this->options['execution_computation_time'] = $executionComputationTime;

        return $this;
    }

    public function getExecutionDelay(): ?int
    {
        return $this->options['execution_delay'];
    }

    public function setExecutionDelay(int $executionDelay = null): TaskInterface
    {
        $this->options['execution_delay'] = $executionDelay;

        return $this;
    }

    public function getExecutionMemoryUsage(): ?int
    {
        return $this->options['execution_memory_usage'];
    }

    public function setExecutionMemoryUsage(int $executionMemoryUsage = null): TaskInterface
    {
        $this->options['execution_memory_usage'] = $executionMemoryUsage;

        return $this;
    }

    public function getExecutionPeriod(): ?float
    {
        return $this->options['execution_period'];
    }

    public function setExecutionPeriod(float $executionPeriod = null): TaskInterface
    {
        $this->options['execution_period'] = $executionPeriod;

        return $this;
    }

    public function getExecutionRelativeDeadline(): ?DateInterval
    {
        return $this->options['execution_relative_deadline'];
    }

    public function setExecutionRelativeDeadline(DateInterval $executionRelativeDeadline = null): TaskInterface
    {
        $this->options['execution_relative_deadline'] = $executionRelativeDeadline;

        return $this;
    }

    public function setExecutionStartDate(string $executionStartDate = null): TaskInterface
    {
        if (!$this->validateDate($executionStartDate)) {
            throw new InvalidArgumentException('The date could not be created');
        }

        $this->options['execution_start_date'] = null !== $executionStartDate ? new DateTimeImmutable($executionStartDate) : null;

        return $this;
    }

    public function getExecutionStartDate(): ?DateTimeImmutable
    {
        return $this->options['execution_start_date'];
    }

    public function setExecutionEndDate(string $executionEndDate = null): TaskInterface
    {
        if (!$this->validateDate($executionEndDate)) {
            throw new InvalidArgumentException('The date could not be created');
        }

        $this->options['execution_end_date'] = null !== $executionEndDate ? new DateTimeImmutable($executionEndDate) : null;

        return $this;
    }

    public function getExecutionEndDate(): ?DateTimeImmutable
    {
        return $this->options['execution_end_date'];
    }

    public function setExecutionStartTime(DateTimeImmutable $executionStartTime = null): TaskInterface
    {
        $this->options['execution_start_time'] = $executionStartTime;

        return $this;
    }

    public function getExecutionStartTime(): ?DateTimeImmutable
    {
        return $this->options['execution_start_time'];
    }

    public function setExecutionEndTime(DateTimeImmutable $executionStartTime = null): TaskInterface
    {
        $this->options['execution_end_time'] = $executionStartTime;

        return $this;
    }

    public function getExecutionEndTime(): ?DateTimeImmutable
    {
        return $this->options['execution_end_time'];
    }

    public function setLastExecution(DateTimeImmutable $lastExecution = null): TaskInterface
    {
        $this->options['last_execution'] = $lastExecution;

        return $this;
    }

    public function getLastExecution(): ?DateTimeImmutable
    {
        return $this->options['last_execution'];
    }

    public function setMaxDuration(float $maxDuration = null): TaskInterface
    {
        $this->options['max_duration'] = $maxDuration;

        return $this;
    }

    public function getMaxDuration(): ?float
    {
        return $this->options['max_duration'];
    }

    public function getNice(): ?int
    {
        return $this->options['nice'];
    }

    public function setNice(int $nice = null): TaskInterface
    {
        if (!$this->validateNice($nice)) {
            throw new InvalidArgumentException('The nice value is not valid');
        }

        $this->options['nice'] = $nice;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    public function getState(): string
    {
        return $this->options['state'];
    }

    public function setState(string $state): TaskInterface
    {
        if (!$this->validateState($state)) {
            throw new InvalidArgumentException('This state is not allowed');
        }

        $this->options['state'] = $state;

        return $this;
    }

    public function getExecutionState(): ?string
    {
        return $this->options['execution_state'];
    }

    public function setExecutionState(string $executionState = null): TaskInterface
    {
        if (!$this->validateExecutionState($executionState)) {
            throw new InvalidArgumentException('This execution state is not allowed');
        }

        $this->options['execution_state'] = $executionState;

        return $this;
    }

    public function isOutput(): bool
    {
        return $this->options['output'];
    }

    public function setOutput(bool $output): TaskInterface
    {
        $this->options['output'] = $output;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->options['priority'];
    }

    public function setPriority(int $priority): TaskInterface
    {
        if (!$this->validatePriority($priority)) {
            throw new InvalidArgumentException('The priority is invalid');
        }

        $this->options['priority'] = $priority;

        return $this;
    }

    public function isQueued(): bool
    {
        return $this->options['queued'];
    }

    public function setQueued(bool $queued): TaskInterface
    {
        $this->options['queued'] = $queued;

        return $this;
    }

    public function setScheduledAt(DateTimeImmutable $scheduledAt = null): TaskInterface
    {
        $this->options['scheduled_at'] = $scheduledAt;

        return $this;
    }

    public function getScheduledAt(): ?DateTimeImmutable
    {
        return $this->options['scheduled_at'];
    }

    public function isSingleRun(): bool
    {
        return $this->options['single_run'];
    }

    public function setSingleRun(bool $singleRun): TaskInterface
    {
        $this->options['single_run'] = $singleRun;

        return $this;
    }

    public function getTags(): array
    {
        return $this->options['tags'];
    }

    public function setTags(array $tags): TaskInterface
    {
        $this->options['tags'] = $tags;

        return $this;
    }

    public function addTag(string $tag): TaskInterface
    {
        $this->options['tags'][] = $tag;

        return $this;
    }

    public function getTimezone(): ?DateTimeZone
    {
        return $this->options['timezone'];
    }

    public function setTimezone(DateTimeZone $timezone = null): TaskInterface
    {
        $this->options['timezone'] = $timezone;

        return $this;
    }

    public function isTracked(): bool
    {
        return $this->options['tracked'];
    }

    public function setTracked(bool $tracked): TaskInterface
    {
        $this->options['tracked'] = $tracked;

        return $this;
    }

    private function validateExpression($expression): bool
    {
        return CronExpression::isValidExpression($expression);
    }

    private function validateNice(int $nice = null): bool
    {
        if (null === $nice) {
            return true;
        }

        return $nice <= 19 && $nice >= -20;
    }

    private function validatePriority(int $priority): bool
    {
        return $priority <= 1000 && $priority >= -1000;
    }

    private function validateState(string $state): bool
    {
        return in_array($state, TaskInterface::ALLOWED_STATES);
    }

    private function validateExecutionState(string $executionState = null): bool
    {
        return null === $executionState ? true : in_array($executionState, TaskInterface::EXECUTION_STATES);
    }

    private function validateDate(?string $date = null): bool
    {
        if (null === $date) {
            return true;
        }

        if (false === strtotime($date)) {
            return false;
        }

        if (new DateTimeImmutable('now', $this->getTimezone()) > new DateTimeImmutable($date)) {
            throw new LogicException('The date cannot be previous to the current date');
        }

        return true;
    }
}
