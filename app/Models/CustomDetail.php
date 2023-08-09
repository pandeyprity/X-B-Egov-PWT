<?php

namespace App\Models;

use App\Repository\Property\Concrete\PropertyBifurcation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;

class CustomDetail extends Model
{
    use HasFactory;
    private $_bifuraction;

    public function __construct()
    {
        $this->_bifuraction = new PropertyBifurcation();
    }

    public function getCustomDetails($request)
    {
        try {
            $customDetails = CustomDetail::select(
                'id',
                'ref_id',
                'ref_type',
                'relative_path',
                'doc_name as docUrl',
                'remarks',
                'type',
                'created_at as date',
                'ref_type as customFor'
            )
                ->orderBy("id", 'desc')
                ->where('ref_id', $request->applicationId)
                ->where('ref_type', trim(strtoupper($request->customFor)))
                ->get();
            $customDetails = $customDetails->map(function ($val) {
                // $path = $this->_bifuraction->readDocumentPath($val->relative_path . '/' . $val->docUrl);
                $path = config('app.url') . '/' . $val->relative_path . '/' . $val->docUrl;
                $val->docUrl = $path;
                return $val;
            });
            return responseMsg(true, "Successfully Retrieved", $customDetails);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    //post custom details
    public function postCustomDetails($request)
    {

        $customFor = trim(strtoupper($request->customFor));
        $path = Config::get('PropertyConstaint.CUSTOM_RELATIVE_PATH');

        $customDetails = new CustomDetail;
        $filename = NULL;

        if ($file = $request->file('document')) {
            $filename = time() .  '.' . $file->getClientOriginalExtension();
            // $path = storage_path('app/public/custom');
            $file->move($path, $filename);
        }

        $customDetails = new CustomDetail;
        if ($customFor == 'PROPERTY-CONCESSION') {
            $customDetails->ref_type = 'PROPERTY-CONCESSION';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'SAF') {
            $customDetails->ref_type = 'SAF';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'PROPERTY-OBJECTION') {
            $customDetails->ref_type = 'PROPERTY-OBJECTION';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'PROPERTY-HARVESTING') {
            $customDetails->ref_type = 'PROPERTY-HARVESTING';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'WATER') {
            $customDetails->ref_type = 'WATER';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'TRADE') {
            $customDetails->ref_type = 'TRADE';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'PROPERTY DEACTIVATION') {
            $customDetails->ref_type = 'PROPERTY DEACTIVATION';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'GBSAF') {
            $customDetails->ref_type = 'GBSAF';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'SELF') {
            $customDetails->ref_type = 'SELF';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'MOVABLE') {
            $customDetails->ref_type = 'MOVABLE';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'PRIVATE') {
            $customDetails->ref_type = 'PRIVATE';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'AGENCY') {
            $customDetails->ref_type = 'AGENCY';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'HOARDING') {
            $customDetails->ref_type = 'HOARDING';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'BANQUET') {
            $customDetails->ref_type = 'BANQUET';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'LODGE') {
            $customDetails->ref_type = 'LODGE';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'HOSTEL') {
            $customDetails->ref_type = 'HOSTEL';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }

        if ($customFor == 'DHARAMSHALA') {
            $customDetails->ref_type = 'DHARAMSHALA';
            $this->saveCustomDetail($request, $filename, $customDetails, $path);
            $customDetails->save();
        }
    }

    // save custom details
    public function saveCustomDetail($request, $filename, $customDetails, $path)
    {
        if ($request->remarks && $request->document) {

            $customDetails->ref_id = $request->applicationId;
            $customDetails->doc_name = $filename;
            $customDetails->remarks = $request->remarks;
            $customDetails->relative_path = $path;
            $customDetails->type = "both";
        } elseif ($request->document) {

            $customDetails->ref_id = $request->applicationId;
            $customDetails->doc_name = $filename;
            $customDetails->relative_path = $path;
            $customDetails->type = "file";
        } elseif ($request->remarks) {

            $customDetails->ref_id = $request->applicationId;
            $customDetails->remarks = $request->remarks;
            $customDetails->type = "text";
        }
    }
}
