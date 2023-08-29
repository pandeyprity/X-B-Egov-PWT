<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * | Created On-02-01-2023 
 * | Created By-Anshu Kumar
 */

class AdvActiveAgencydirector extends Model
{
    use HasFactory;
    protected $guarded = [];

    /** 
     * | Store
     * | @param request $req
     * | @param agencyId Agency Id
     * */
    public function store($req, $agencyId)
    {
        if (is_array($req)) {
            $req = new Request($req);
        }

        $metaReqs = [
            'agency_id' => $agencyId,
            'director_name' => $req->name,
            'director_mobile' => $req->mobile,
            'director_email' => $req->email
        ];

        AdvActiveAgencydirector::create($metaReqs);
    }
}
