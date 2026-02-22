<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Models\Route;
use Illuminate\Http\JsonResponse;

class RouteController extends BaseApiController
{
    /**
     * List active routes for dropdowns (e.g. create booking)
     */
    public function index(): JsonResponse
    {
        $routes = Route::active()->orderBy('origin_country')->orderBy('destination_country')->get();
        return $this->collectionResponse($routes, 'Routes retrieved successfully');
    }
}
