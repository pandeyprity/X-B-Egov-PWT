<?php

namespace App\MicroServices;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/**
 * | Created On-30-12-2022 
 * | Created By-Anshu Kumar
 * | Document Upload Service
 */

class DocumentUpload
{
    /**
     * | Image Document Upload
     * | @param refImageName format Image Name like SAF-geotagging-id (Pass Your Ref Image Name Here)
     * | @param requested image (pass your request image here)
     * | @param relativePath Image Relative Path (pass your relative path of the image to be save here)
     * | @return imageName imagename to save (Final Image Name with time and extension)
     */
    public function upload($refImageName, $image, $relativePath)
    {
        $extention = $image->getClientOriginalExtension();
        $imageName = time() . '-' . $refImageName . '.' . $extention;
        $image->move($relativePath, $imageName);
        return $imageName;
    }


    /**
     * | Doc Upload using DMS Service
     */
    public function uploadV2($req)
    {
        $file = $req->document;
        $filePath = $file->getPathname();
        $hashedFile = hash_file('sha256', $filePath);
        $filename = ($req->document)->getClientOriginalName();
        $dmsUrl = Config::get('constant.DMS_URL');
        $api = "$dmsUrl/document/upload";
        $transfer = [
            "file" => $req->document,
            "tags" => "good,ghdt",
            // "reference" => 425
        ];
        $returnData = Http::withHeaders([
            "x-digest"      => "$hashedFile",
            "token"         => "8Ufn6Jio6Obv9V7VXeP7gbzHSyRJcKluQOGorAD58qA1IQKYE0",
            "folderPathId"  => 1
        ])->attach([
            [
                'file',
                file_get_contents($req->file('document')->getRealPath()),
                $filename
            ]
        ])->post("$api", $transfer);

        if ($returnData->successful()) {
            $statusCode = $returnData->status();
            $responseBody = $returnData->body();
            return $returnData;
        } else {
            $statusCode = $returnData->status();
            $responseBody = $returnData->body();
            return $responseBody;
        }
        return false;
    }
}
