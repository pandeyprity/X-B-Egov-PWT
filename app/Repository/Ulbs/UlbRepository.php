<?php

namespace App\Repository\Ulbs;

use Illuminate\Http\Request;

/**
 * Created On-02-07-2022 
 * Created By-Anshu Kumar
 * ---------------------------------------------------------------------------------------------------------
 * Interface for the functions to used in EloquentUlbRepository
 * @return ChildRepository App\Repository\Ulbs\EloquentUlbRepository
 */
interface UlbRepository
{
    public function store(Request $request);
    public function edit(Request $request, $id);
    public function view($id);
}
