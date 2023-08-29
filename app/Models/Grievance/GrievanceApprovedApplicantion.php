<?php

namespace App\Models\Grievance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrievanceApprovedApplicantion extends Model
{
    use HasFactory;

    /**
     * | Get approved application detials
        | Remove
     */
    public function getApproveApplication($applicationId)
    {
        return GrievanceApprovedApplicantion::where('id', $applicationId)
            ->where('status', 1);
    }
}
