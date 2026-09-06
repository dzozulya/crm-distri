<?php

use App\Http\Controllers\LeadDistributionController;

Route::post(
'/leads/distribute',
LeadDistributionController::class,
);
