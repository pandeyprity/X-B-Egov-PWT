<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefRequiredDocument extends Model
{
    use HasFactory;

    public function getDocsByDocCode($moduldId, $docCode)
    {
        return RefRequiredDocument::select('requirements')
            ->where('module_id', $moduldId)
            ->where('code', $docCode)
            ->first();
    }

    public function listDocument($moduleId)
    {
        return RefRequiredDocument::select('requirements', 'module_id', 'code')
            ->where('module_id', $moduleId)
            ->get();
    }

    /**
     * | Get  All Document Collictively For Array Of DocCode
     */
    public function getCollectiveDocByCode($moduldId, $docCodes)
    {
        return RefRequiredDocument::select(
            'requirements',
            'code'
        )
            ->where('module_id', $moduldId)
            ->whereIn('code', $docCodes)
            ->get();
    }
}
