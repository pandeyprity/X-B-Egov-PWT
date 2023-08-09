<?php

namespace App\Observers\Property;

use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\MicroServices\IdGenerator\PropIdGenerator;
use App\Models\Property\PropActiveSaf;
use Illuminate\Support\Facades\Config;

class PropActiveSafObserver
{
    /**
     * Handle the PropActiveSaf "created" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function created(PropActiveSaf $propActiveSaf)
    {
        $propIdGenerator = new PropIdGenerator;
        if (is_null($propActiveSaf->saf_no)) {
            $safNo = $propIdGenerator->generateSafNo($propActiveSaf);
            $propActiveSaf->saf_no = $safNo;
            $propActiveSaf->save();
        }
    }

    /**
     * Handle the PropActiveSaf "updated" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function updated(PropActiveSaf $propActiveSaf)
    {
        //
    }

    /**
     * Handle the PropActiveSaf "deleted" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function deleted(PropActiveSaf $propActiveSaf)
    {
        //
    }

    /**
     * Handle the PropActiveSaf "restored" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function restored(PropActiveSaf $propActiveSaf)
    {
        //
    }

    /**
     * Handle the PropActiveSaf "force deleted" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function forceDeleted(PropActiveSaf $propActiveSaf)
    {
        //
    }
}
