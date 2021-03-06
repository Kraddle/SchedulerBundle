<?php

declare(strict_types=1);

namespace SchedulerBundle\Task;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
final class NullTask extends AbstractTask
{
    public function __construct(string $name)
    {
        $this->defineOptions();

        parent::__construct($name);
    }
}
