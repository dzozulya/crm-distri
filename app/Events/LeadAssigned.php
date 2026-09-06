<?php

namespace App\Events;


use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LeadAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $leadId,
        public readonly int $managerId,
    )
    {
    }
}
