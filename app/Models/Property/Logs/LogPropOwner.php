<?php

namespace App\Models\Property\Logs;

use App\Models\Property\PropOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogPropOwner extends Model
{
    use HasFactory;
    protected $guarded = [];


    /**
     * | Replication of Prop Owners on Log Table
     */
    public function replicateOwnerByPropOwners($ownerId)
    {
        $owner = PropOwner::findOrFail($ownerId);
        $loggingOwners = $owner->replicate();
        $loggingOwners->setTable('log_prop_owners');
    }
}
