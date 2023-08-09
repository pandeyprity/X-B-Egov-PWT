<?php

namespace App\Repository\Cluster\Interfaces;

/**
 * | Property Cluster
 * | Created By - Sam kerketta
 * | Created On- 23-11-2022 
 * | Interface Calling Cluster Repo_
 */

interface iCluster
{
    public function getClusterById($request);                   // Fetch Cluster Detail Accordinfg to Cluster Id
    # cluster maping
    public function detailsByHolding($request);                 // Fetch Property Detail Accordinfg to Holding  
    public function holdingByCluster($request);                 // Fetch Property Detail Accordinfg to Cluster
    public function saveHoldingInCluster($request);             // Save the Holdings to respective Cluster
}
