<?php

declare(strict_types=1);

namespace SchedulerBundle\Transport;

use Closure;
use SchedulerBundle\Exception\TransportException;
use SchedulerBundle\Task\TaskInterface;
use SchedulerBundle\Task\TaskListInterface;
use SplObjectStorage;
use Throwable;
use function array_merge;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
final class FailoverTransport extends AbstractTransport
{
    private $failedTransports;
    private $transports;

    /**
     * @param iterable|TransportInterface[] $transports
     */
    public function __construct(iterable $transports, array $options = [])
    {
        $this->defineOptions(array_merge($options, [
            'mode' => 'normal',
        ]), [
            'mode' => ['string'],
        ]);

        $this->transports = $transports;
        $this->failedTransports = new SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): TaskInterface
    {
        return $this->execute(function (TransportInterface $transport) use ($name): TaskInterface {
            return $transport->get($name);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function list(): TaskListInterface
    {
        return $this->execute(function (TransportInterface $transport): TaskListInterface {
            return $transport->list();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function create(TaskInterface $task): void
    {
        $this->execute(function (TransportInterface $transport) use ($task): void {
            $transport->create($task);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $name, TaskInterface $updatedTask): void
    {
        $this->execute(function (TransportInterface $transport) use ($name, $updatedTask): void {
            $transport->update($name, $updatedTask);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name): void
    {
        $this->execute(function (TransportInterface $transport) use ($name): void {
            $transport->delete($name);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function pause(string $name): void
    {
        $this->execute(function (TransportInterface $transport) use ($name): void {
            $transport->pause($name);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function resume(string $name): void
    {
        $this->execute(function (TransportInterface $transport) use ($name): void {
            $transport->resume($name);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->execute(function (TransportInterface $transport): void {
            $transport->clear();
        });
    }

    private function execute(Closure $func)
    {
        if (empty($this->transports)) {
            throw new TransportException('No transport found');
        }

        foreach ($this->transports as $transport) {
            if ($this->failedTransports->contains($transport)) {
                continue;
            }

            try {
                return $func($transport);
            } catch (Throwable $throwable) {
                $this->failedTransports->attach($transport);

                continue;
            }
        }

        throw new TransportException('All the transports failed to execute the requested action');
    }
}
