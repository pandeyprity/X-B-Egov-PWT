<?php

namespace App\Repository\Property\Interfaces;


interface iPropertyDetailsRepo
{
    public function getFilterProperty($request);
    public function getFilterSafs($request);
    public function getUserDetails($request);
}