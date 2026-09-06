<?php

namespace App\Http\Controllers;

use App\Http\Requests\DistributeLeadsRequest;
use App\Services\LeadDistributionService;
use Illuminate\Http\JsonResponse;

final class LeadDistributionController extends Controller
{
    public function __invoke(
        DistributeLeadsRequest $request,
        LeadDistributionService $service,
    ): JsonResponse {
        $distributed = $service->distribute();

        return response()->json([
            'distributed' => $distributed,
        ]);
    }
}
