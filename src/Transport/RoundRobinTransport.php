<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SchedulerBundle\Transport;

use SchedulerBundle\Exception\TransportException;
use SchedulerBundle\Task\TaskInterface;
use SchedulerBundle\Task\TaskListInterface;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 *
 * @experimental in 5.3
 */
final class RoundRobinTransport extends AbstractTransport
{
    private $transports;
    private $sleepingTransports;

    /**
     * @param iterable|TransportInterface[] $transports
     */
    public function __construct(iterable $transports, array $options = [])
    {
        $this->defineOptions(array_merge($options, [
            'quantum' => 2,
        ]), [
            'quantum' => ['int'],
        ]);

        $this->transports = $transports;
        $this->sleepingTransports = new \SplObjectStorage();
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

    private function execute(\Closure $func)
    {
        if (empty($this->transports)) {
            throw new TransportException('No transport found');
        }

        while ($this->sleepingTransports->count() !== \count($this->transports)) {
            foreach ($this->transports as $transport) {
                if ($this->sleepingTransports->contains($transport)) {
                    continue;
                }

                try {
                    $res = $func($transport);

                    $this->sleepingTransports->attach($transport);

                    return $res;
                } catch (\Throwable $throwable) {
                    $this->sleepingTransports->attach($transport);

                    continue;
                }
            }
        }

        throw new TransportException('All the transports failed to execute the requested action');
    }
}