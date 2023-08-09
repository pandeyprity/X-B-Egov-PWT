<?php

namespace App\MicroServices\IdGenerator;

use App\Models\Masters\IdGenerationParam;
use App\Models\Property\PropActiveSaf;
use App\Models\UlbWardMaster;
use Exception;
use Illuminate\Support\Facades\Config;

/**
 * ╔═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════╗
 * | ✅Author-Anshu Kumar                                                                                                              |
 * | Created On-13-07-2023                                                                                                           |
 * | Created for the Id generations for SAF and Property                                                                             |
 * | Status-Closed                                                                                                                   |
 * ╚═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════╝ 
 */

class PropIdGenerator
{
    private $_mUlbWardMasters;
    private $_mIdGenerationParams;
    private $_wardDtls;
    public function __construct()
    {
        $this->_mUlbWardMasters = new UlbWardMaster();
        $this->_mIdGenerationParams = new IdGenerationParam();
    }

    // ╔═══════════════════════════════════════════════════════════════════════════╗
    // ║                     ✅ SAF No Generation ✅                              ║ 
    // ╚═══════════════════════════════════════════════════════════════════════════╝ 

    /**
     * | Saf No Generation
     * | @param activeSaf
     */
    public function generateSafNo($activeSaf)
    {
        $assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE');
        $paramId = Config::get('PropertyConstaint.PARAM_ID');
        $gbParamId = Config::get('PropertyConstaint.GB_PARAM');
        $flippedAssessmentTypes = flipConstants($assessmentType);

        $assessmentType = $activeSaf->assessment_type;
        $assessmentId = $flippedAssessmentTypes->get($assessmentType);

        $this->_wardDtls = $this->_mUlbWardMasters->getWardById($activeSaf->ward_mstr_id);
        if (collect($this->_wardDtls)->isEmpty())
            throw new Exception("Ward No Found for this ward id");

        // parameters generation
        $assessmentId = str_pad($assessmentId, 2, "0", STR_PAD_LEFT);
        $wardId = str_pad($this->_wardDtls->ward_name, 3, "0", STR_PAD_LEFT);
        $counter = $this->getUpdateWardCounter($assessmentId);

        if ($activeSaf->is_gb_saf == true)                 // If Saf Is Government Building
            $prefix = $this->_mIdGenerationParams->where('id', $gbParamId)->first();

        if ($activeSaf->is_gb_saf == false)                // If Saf is Normal Saf
            $prefix = $this->_mIdGenerationParams->where('id', $paramId)->first();

        if (collect($prefix)->isEmpty())
            throw new Exception("Parameter Prefix String Value not Available");
        $stringVal = $prefix->string_val;
        $safNo = "$stringVal/$assessmentId/$wardId/$counter";

        return $safNo;
    }


    /**
     * | Get Ward Counter
     */
    public function getUpdateWardCounter($assessmentId): string
    {
        $counter = 0;
        switch ($assessmentId) {
            case 1:
                $counter = $this->_wardDtls->new_assessment_counter;
                $this->_wardDtls->new_assessment_counter += 1;
                break;
            case 2:
                $counter = $this->_wardDtls->re_assessment_counter;
                $this->_wardDtls->re_assessment_counter += 1;
                break;
            case 3:
                $counter = $this->_wardDtls->mutation_assessment_counter;
                $this->_wardDtls->mutation_assessment_counter += 1;
                break;
            case 4:
                $counter = $this->_wardDtls->bifurcation_assessment_counter;
                $this->_wardDtls->bifurcation_assessment_counter += 1;
                break;
            case 5:
                $counter = $this->_wardDtls->amalgamation_assessment_counter;
                $this->_wardDtls->amalgamation_assessment_counter += 1;
                break;
        }
        if ($counter == 0)
            throw new Exception("Invalid Counter Found");

        $this->_wardDtls->save();                           // Update Increamental Ward counter
        return str_pad($counter, 4, "0", STR_PAD_LEFT);
    }


    // ╔═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════╗
    // ║                                                ✅ Memo No Generation ✅                                                          ║ 
    // ╚═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════╝ 

    /**
     * | Generate Memo No
     */
    public function generateMemoNo($memoType, $wardId, $fyear): string
    {
        $paramId = ($memoType == 'SAM') ? Config::get('PropertyConstaint.SAM_PARAM_ID') : Config::get('PropertyConstaint.FAM_PARAM_ID');
        $prefix = $this->_mIdGenerationParams->where('id', $paramId)->first();
        if (collect($prefix)->isEmpty())
            throw new Exception("Memo Prefix Not Available");

        $wardDtls = $this->_mUlbWardMasters->getWardById($wardId);
        if (collect($wardDtls)->isEmpty())
            throw new Exception("Ward Not Available for this id");

        $ward = str_pad($wardDtls->ward_name, 3, "0", STR_PAD_LEFT);

        if ($memoType == 'SAM') {                                               // SAM Counter
            $counter = str_pad($wardDtls->sam_counter, 4, "0", STR_PAD_LEFT);
            $wardDtls->sam_counter += 1;                                         // Update Counter
        }

        if ($memoType == 'FAM') {                                                // FAM Counter
            $counter = str_pad($wardDtls->fam_counter, 4, "0", STR_PAD_LEFT);
            $wardDtls->fam_counter += 1;                                         // Update Counter
        }

        $wardDtls->save();
        $memoNo = $prefix->string_val . '/' . $ward . '/' . $counter . '/' . $fyear;
        return $memoNo;
    }
}
