<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SchedulerBundle\Task;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 *
 * @experimental in 5.3
 */
final class CallbackTask extends AbstractTask
{
    public function __construct(string $name, $callback, array $arguments = [])
    {
        $this->defineOptions([
            'callback' => $callback,
            'arguments' => $arguments,
        ], [
            'callback' => ['callable', 'string'],
            'arguments' => ['array', 'string[]'],
        ]);

        parent::__construct($name);
    }

    public function getCallback()
    {
        return $this->options['callback'];
    }

    public function setCallback($callback): TaskInterface
    {
        $this->options['callback'] = $callback;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getArguments(): array
    {
        return $this->options['arguments'];
    }

    /**
     * @param array<string,mixed> $arguments
     */
    public function setArguments(array $arguments): TaskInterface
    {
        $this->options['arguments'] = $arguments;

        return $this;
    }
}
