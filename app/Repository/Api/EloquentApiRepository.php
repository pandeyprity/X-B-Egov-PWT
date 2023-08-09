<?php

namespace App\Repository\Api;

use App\Repository\Api\ApiRepository;
use App\Http\Requests\Api\ApiStoreRequest;
use App\Http\Requests\Api\ApiSearchRequest;
use App\Models\ApiMaster;
use Exception;
use App\Traits\Api\StoreApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Repository for Storing, Modifing, Fetching The Api master 
 * ---------------------------------------------------------------------------------------------------------
 * Created On-29-06-2022 
 * Created By-Anshu Kumar
 * ---------------------------------------------------------------------------------------------------------
 * Code Tested By-
 * Feedback-
 */

class EloquentApiRepository implements ApiRepository
{
    use StoreApi;
    /**
     * Storing API 
     * @param App\Http\Requests\Api\ApiStoreRequest
     * @param App\Http\Requests\Api\ApiStoreRequest $request
     * @return App\Traits\Api\StoreApi Trait
     */
    public function store(ApiStoreRequest $request)
    {
        try {
            $api_master = new ApiMaster;
            return $this->saving($api_master, $request);            //Save using StoreApi Trait
        } catch (Exception $e) {
            return response()->json([$e, 400]);
        }
    }

    /**
     * Modifying APIs
     * @param App\Http\Requests\Api\ApiStoreRequest
     * @param \App\Http\Requests\Api\ApiStoreRequest $request
     * @return App\Traits\Api\StoreApi Trait
     */

    public function update(ApiStoreRequest $request)
    {
        try {
            $api_master = ApiMaster::find($request->id);
            if ($api_master) {
                return $this->saving($api_master, $request);    //Save using StoreApi Trait(Code Duplication Removed)
            } else {
                return response()->json('Id Not Found', 404);
            }
        } catch (Exception $e) {
            return response()->json([$e, 400]);
        }
    }

    /**
     * Get Api By api-id
     * @param api-id $id
     * @return resposne
     */

    public function getApiByID($id)
    {
        try {
            $api = DB::select("select * from api_masters where id=$id");
            if ($api) {
                return $this->getApiDetails($api);                         // Fetching Data Using Trait
            } else {
                return response()->json('Api not found for this id', 404);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Get All Apis
     * @return response
     */
    public function getAllApis()
    {
        try {
            $api = DB::select("select * from api_masters order by id desc");
            if ($api) {
                return $this->getApiDetails($api);                         // Fetching Data Using Trait
            } else {
                return response()->json('Api not found for this id', 404);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Searching APIs by Api End Point
     * @param App\Http\Requests\Api\ApiSearchRequest
     * @param App\Http\Requests\Api\ApiSearchRequest $request
     * @return response
     */

    public function search(ApiSearchRequest $request)
    {
        try {
            $api = ApiMaster::where('end_point', $request->endPoint)
                ->orWhere('end_point', 'like', '%' . $request->endPoint . '%')
                ->get();

            if ($api->count() > 0) {
                return $this->getApiDetails($api);                                  // Fetching Data Using Trait
            } else {
                return response()->json(['Message' => 'No End Point Available'], 404);
            }
        } catch (Exception $e) {
            return response()->json([$e, 400]);
        }
    }

    /**
     * Searching Api By Tag
     * @param Request 
     * @param Request $request
     * @return json Response
     * --------------------------------------------------------------------------------
     */
    public function searchApiByTag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tags' => array('required')
        ]);
        if ($validator->fails()) {
            return [
                'message' => $validator->errors()->first()
            ];
        }

        $query = "SELECT * FROM api_masters WHERE tags LIKE '%$request->tags%'";
        $api = DB::select($query);
        return $this->getApiDetails($api);                                  // Fetching Data Using Trait
    }
}
