<?php

namespace App\Http\Controllers;

use App\Repository\Api\EloquentApiRepository;
use App\Http\Requests\Api\ApiStoreRequest;
use App\Http\Requests\Api\ApiSearchRequest;
use Illuminate\Http\Request;

class ApiMasterController extends Controller
{
    /**
     * Controller for api store,api search and api editing
     * ------------------------------------------------------------------------------------------
     * CreatedOn-29-06-2022 
     * CreatedBy-Anshu Kumar
     * ------------------------------------------------------------------------------------------
     * Code Testing
     * Tested By-
     * Feedback-
     * ------------------------------------------------------------------------------------------
     */

    // Initializing Constructor for EloquentApiRepository
    protected $eloquentApi;

    public function __construct(EloquentApiRepository $eloquentApi)
    {
        $this->EloquentApi = $eloquentApi;
    }

    // Storing
    public function store(ApiStoreRequest $request)
    {
        return $this->EloquentApi->store($request);
    }

    // Update
    public function update(ApiStoreRequest $request)
    {
        return $this->EloquentApi->update($request);
    }

    // Get Api By ID
    public function getApiByID($id)
    {
        return $this->EloquentApi->getApiByID($id);
    }

    // Get All Apis
    public function getAllApis()
    {
        return $this->EloquentApi->getAllApis();
    }

    // Search By EndPoint
    public function search(ApiSearchRequest $request)
    {
        return $this->EloquentApi->search($request);
    }

    // Search Api by Tag
    public function searchApiByTag(Request $request)
    {
        return $this->EloquentApi->searchApiByTag($request);
    }
}
