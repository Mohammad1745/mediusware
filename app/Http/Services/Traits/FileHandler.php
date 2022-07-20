<?php


namespace App\Http\Services\Traits;


use Illuminate\Support\Facades\Storage;

trait FileHandler
{

    /**
     * @param $file
     * @param $destinationPath
     * @param $oldFile
     * @return false|string
     */
    function uploadFile($file, $destinationPath, $oldFile = null)
    {
        if ($oldFile != null) {
            $this->deleteFile($destinationPath, $oldFile);
        }

        $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
        $uploaded = Storage::put($destinationPath . $fileName, file_get_contents($file->getRealPath()));
        if ($uploaded == true) {
            $name = $fileName;
            return $name;
        }
        return false;
    }

    /**
     * @param $destinationPath
     * @param $file
     * @return void
     */
    function deleteFile($destinationPath, $file)
    {
        if ($file != null) {
            try {
                Storage::delete($destinationPath . $file);
            } catch (\Exception $e) {

            }
        }
    }
}
