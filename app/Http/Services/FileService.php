<?php

namespace App\Http\Services;

use App\Http\Services\Traits\FileHandler;
use App\Http\Services\Traits\Response;
use Illuminate\Support\Facades\Storage;

class FileService
{
    use Response, FileHandler;

    /**
     * @param object $request
     * @return array
     */
    public function upload (object $request): array
    {
        try {
            $fileName = $this->uploadFile($request->file('image'), 'public/image');

            return $this->response(['filename'=>$fileName])->success();
        } catch (\Exception $exception) {
            return $this->response()->error($exception->getMessage());
        }
    }
}
