<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface ManagerRepositoryInterface
{
    public function getActiveManagerLoads() : Collection;

}
