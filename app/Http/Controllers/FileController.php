<?php

namespace App\Http\Controllers;

use App\Http\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    /**
     * @var FileService
     */
    private $service;

    /**
     * @param FileService $service
     */
    function __construct(FileService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function upload (Request $request): JsonResponse
    {
        return response()->json($this->service->upload($request));
    }
}
