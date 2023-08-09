<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Workflow\EloquentWorkflowRepository;
use App\Repository\Workflow\Interface\iWorkflowRepository;

/**
 * Controller for Saving Workflows, WorkflowCandidates and all Workflows 
 * Child Repository-App\Repository\Workflow
 * Creation Date-06-07-2022 
 * Created By-Anshu Kumar 
 * ---------------------------------------------------------------------
 * Code Tested By-
 * Code Testing Date-
 */

class WorkflowController extends Controller
{
    /**
     * Initializing for Repository 
     */
    protected $eloquentWorkflow;


    public function __construct(EloquentWorkflowRepository $eloquentWorkflow)
    {
        $this->eloquentWorkflow = $eloquentWorkflow;
    }




    // Storing Workflow
    public function storeWorkflow(Request $request)
    {
        return $this->eloquentWorkflow->storeWorkflow($request);
    }

    // Get Workflow By Id
    public function viewWorkflow($id)
    {
        return $this->eloquentWorkflow->viewWorkflow($id);
    }

    // Get All Workflows
    public function getAllWorkflows()
    {
        return $this->eloquentWorkflow->getAllWorkflows();
    }

    // Update Workflow
    public function updateWorkflow(Request $request, $id)
    {
        return $this->eloquentWorkflow->updateWorkflow($request, $id);
    }

    // Delete Workflow
    public function deleteWorkflow($id)
    {
        return $this->eloquentWorkflow->deleteWorkflow($id);
    }

    // Store Workflow Candidates
    public function storeWorkflowCandidate(Request $request)
    {
        return $this->eloquentWorkflow->storeWorkflowCandidate($request);
    }

    // View Workflow Candidates
    public function viewWorkflowCandidates($id)
    {
        return $this->eloquentWorkflow->viewWorkflowCandidates($id);
    }

    // All Workflow Candidates
    public function allWorkflowCandidates()
    {
        return $this->eloquentWorkflow->allWorkflowCandidates();
    }

    // Edit Workflow Candidates
    public function editWorkflowCandidates(Request $request, $id)
    {
        return $this->eloquentWorkflow->editWorkflowCandidates($request, $id);
    }

    // Delete Workflow Candidates
    public function deleteWorkflowCandidates($id)
    {
        return $this->eloquentWorkflow->deleteWorkflowCandidates($id);
    }

    /**
     * Get Workflow candidates by UlbWorkflow ids
     */
    public function getWorkflowCandidatesByUlbWorkflowID($ulbworkflowid)
    {
        return $this->eloquentWorkflow->getWorkflowCandidatesByUlbWorkflowID($ulbworkflowid);
    }
}



//=========================================================================================================
//                                  Created By: Mrinal Kumar                                            
//=========================================================================================================

// class WC extends Controller
// {
//     protected $iworkflow;

//     public function __construct(iWorkflowRepository $iworkflow)
//     {
//         $this->iworkflow = $iworkflow;
//     }

//     /*
//     * Get Workflow Current User
//     */
//     public function workflowCurrentUser(Request $request)
//     {
//         return $this->iworkflow->workflowCurrentUser($request);
//     }
// }
