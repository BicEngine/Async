<?php

declare(strict_types=1);

namespace Bic\Async;

interface TaskProviderInterface
{
    /**
     * @return TaskInterface
     */
    public function getTask(): TaskInterface;
}
