<?php

namespace App\MicroServices;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

/**
 * | Created On-13-12-2021 
 * | Created By-Anshu Kumar
 * | Created For the Document Upload MicroService
 */
class DocUpload
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
}
