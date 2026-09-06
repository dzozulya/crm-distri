<?php

namespace App\Domain;

final class ManagerLoad
{
    public function __construct(
        public readonly int $managerId,
        public int $load,
    ) {
    }

    public function increment(): void
    {
        $this->load++;
    }
}
