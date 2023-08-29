<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvTypologyMstr extends Model
{
    use HasFactory;
    /**
     * | Get Typology List for Master DataF
     */
    public function listTypology()
    {
        $typology = AdvTypologyMstr::where('status', '1')
            ->select(
                'id',
                'type',
                'type_inner as subtype',
                'descriptions'
            )
            ->orderBy('type_inner')
            ->get();

        $typologyList = $typology->groupBy('type');
        foreach ($typologyList as $key => $data) {
            $type = [
                'Type' => "Type " . $key,
                'data' => $typologyList[$key]
            ];
            $fData[] = $type;
        }
        return $fData;
    }

    /**
     * | Get Hoarding Category
     */
    public function getHordingCategory()
    {
        $typology = AdvTypologyMstr::where('status', '1')
            ->select(
                'id',
                'type_inner as subtype',
                'descriptions'
            )
            ->where('ulb_id',2)
            ->orderBy('type_inner')
            ->get();

        return $typology;
    }

    /**
     * | Get Typology List for application form
     */
    public function listTypology1($ulbId)
    {
        $typology = AdvTypologyMstr::where('status', '1')
            ->select(
                'id',
                'type',
                'type_inner as subtype',
                'descriptions'
            )
            ->where('ulb_id',$ulbId)
            ->orderBy('type_inner')
            ->get();

        return  $typology;
    }
}
