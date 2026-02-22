<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;

class VehicleController extends BaseApiController
{
    /**
     * List vehicles for dropdowns (e.g. create booking)
     */
    public function index(): JsonResponse
    {
        $vehicles = Vehicle::orderBy('make')->orderBy('model')->get();
        return $this->collectionResponse($vehicles, 'Vehicles retrieved successfully');
    }
}
