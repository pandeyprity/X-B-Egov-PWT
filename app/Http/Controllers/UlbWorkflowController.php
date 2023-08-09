<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\UlbWorkflow\EloquentUlbWorkflow;

/**
 * Save, Edit, Fetch and deleting UlbWorkflow
 * Created By-Anshu Kumar
 * Created On-14-07-2022 
 */
class UlbWorkflowController extends Controller
{
    /**
     * Initializing EloquentUlbWorkflow Repository
     */
    protected $eloquentUlbWorkflow;
    public function __construct(EloquentUlbWorkflow $eloquentUlbWorkflow)
    {
        $this->EloquentUlbWorkflow = $eloquentUlbWorkflow;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return $this->EloquentUlbWorkflow->create();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return $this->EloquentUlbWorkflow->store($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->EloquentUlbWorkflow->show($id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
        return $this->EloquentUlbWorkflow->update($request, $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->EloquentUlbWorkflow->destroy($id);
    }

    /**
     * Display the Specific record of Ulb Workflows by their Ulbs
     * 
     */
    public function getUlbWorkflowByUlbID($ulb_id)
    {
        return $this->EloquentUlbWorkflow->getUlbWorkflowByUlbID($ulb_id);
    }
}
