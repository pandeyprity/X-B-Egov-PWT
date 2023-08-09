<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Workflow\UlbWorkflowRolesRepository;

class UlbWorkflowRolesController extends Controller
{
    // Initializing Construct Function 
    protected $eloquent_repository;
    public function __construct(UlbWorkflowRolesRepository $eloquent_repository)
    {
        $this->Repository = $eloquent_repository;
    }
    /**
     * Display a listing of Roles assigned on Workflow
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->Repository->getAllRolesByUlbWorkflowID($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return $this->Repository->store($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
