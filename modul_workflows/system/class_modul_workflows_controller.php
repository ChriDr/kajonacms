<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$					    *
********************************************************************************************************/

/**
 * The controller triggers the execution of scheduled workflows and manages the transition of
 * workflows' states.
 *
 * @package modul_workflows
 * @author sidler@mulchprod.de
 */
class class_modul_workflows_controller extends class_model implements interface_model  {


    /**
     * Constructor to create a valid object
     *
     * @param string $strSystemid (use "" on new objects)
     */
    public function __construct($strSystemid = "") {
        $arrModul = array();
        $arrModul["name"] 				= "modul_workflows";
		$arrModul["moduleId"] 			= _workflows_modul_id_;
		$arrModul["modul"]				= "workflows";
		$arrModul["table"]				= "";

		//base class
		parent::__construct($arrModul, $strSystemid);

    }

    public function initObject() { }

    protected function updateStateToDb() {  }


    /**
     * Searches for new workflows and forces them to schedule and initialize
     */
    public function scheduleWorkflows() {
        $arrWorkflows = class_modul_workflows_workflow::getWorkflowsByType(class_modul_workflows_workflow::$INT_STATE_NEW, false);

        class_logger::getInstance()->addLogRow("scheduling workflows, count: ".count($arrWorkflows), class_logger::$levelInfo);

        foreach($arrWorkflows as /** @var class_modul_workflows_workflow */$objOneWorkflow) {

            //lock the workflow
            $objLockmanager = new class_lockmanager($objOneWorkflow->getSystemid());
            if($objLockmanager->isLocked()) {
                class_logger::getInstance()->addLogRow("workflow ".$objOneWorkflow->getSystemid()." is locked, can't be scheduled", class_logger::$levelWarning);
                continue;
            }

            $objLockmanager->lockRecord();

            /**
             * @var interface_workflows_handler
             */
            $objHandler = $objOneWorkflow->getObjWorkflowHandler();

            //trigger the workflow
            class_logger::getInstance()->addLogRow("initializing workflow ".$objOneWorkflow->getSystemid(), class_logger::$levelInfo);
            $objHandler->initialize();
            class_logger::getInstance()->addLogRow("scheduling workflow ".$objOneWorkflow->getSystemid(), class_logger::$levelInfo);
            $objHandler->schedule();

            class_logger::getInstance()->addLogRow(" scheduling finished, new state: scheduled", class_logger::$levelInfo);
            $objOneWorkflow->setIntState(class_modul_workflows_workflow::$INT_STATE_SCHEDULED);
            $objOneWorkflow->updateObjectToDb();

            //unlock
            $objLockmanager->unlockRecord();


        }
    }



    /**
     * Triggers the workflows scheduled for running.
     */
    public function runWorkflows() {
        $arrWorkflows = class_modul_workflows_workflow::getWorkflowsByType(class_modul_workflows_workflow::$INT_STATE_SCHEDULED);

        class_logger::getInstance()->addLogRow("running workflows, count: ".count($arrWorkflows), class_logger::$levelInfo);

        foreach($arrWorkflows as /** @var class_modul_workflows_workflow */$objOneWorkflow) {

            //lock the workflow
            $objLockmanager = new class_lockmanager($objOneWorkflow->getSystemid());
            if($objLockmanager->isLocked()) {
                class_logger::getInstance()->addLogRow("workflow ".$objOneWorkflow->getSystemid()." is locked, can't be scheduled", class_logger::$levelWarning);
                continue;
            }

            $objLockmanager->lockRecord();

            /**
             * @var interface_workflows_handler
             */
            $objHandler = $objOneWorkflow->getObjWorkflowHandler();

            //trigger the workflow
            class_logger::getInstance()->addLogRow("executing workflow ".$objOneWorkflow->getSystemid(), class_logger::$levelInfo);
            if($objHandler->execute()) {
                //handler executed successfully. shift to state 'executed'
                $objOneWorkflow->setIntState(class_modul_workflows_workflow::$INT_STATE_EXECUTED);
                class_logger::getInstance()->addLogRow(" execution finished, new state: executed", class_logger::$levelInfo);
            }
            else {
                //handler failed to execute. reschedule.
                $objHandler->schedule();
                $objOneWorkflow->setIntState(class_modul_workflows_workflow::$INT_STATE_SCHEDULED);
                class_logger::getInstance()->addLogRow(" execution finished, new state: scheduled", class_logger::$levelInfo);
            }

            $objOneWorkflow->setIntRuns($objOneWorkflow->getIntRuns()+1);
            $objOneWorkflow->updateObjectToDb();

            $objLockmanager->unlockRecord();

        }
    }

    /**
     * Runs a single workflow.
     * @param class_modul_workflows_workflow $objOneWorkflow
     */
    public function runSingleWorkflow($objOneWorkflow) {

        $objHandler = $objOneWorkflow->getObjWorkflowHandler();

        if($objOneWorkflow->getIntState() != class_modul_workflows_workflow::$INT_STATE_SCHEDULED)
            return;

        //trigger the workflow
        class_logger::getInstance()->addLogRow("executing workflow ".$objOneWorkflow->getSystemid(), class_logger::$levelInfo);
        if($objHandler->execute()) {
            //handler executed successfully. shift to state 'executed'
            $objOneWorkflow->setIntState(class_modul_workflows_workflow::$INT_STATE_EXECUTED);
            class_logger::getInstance()->addLogRow(" execution finished, new state: executed", class_logger::$levelInfo);
        }
        else {
            //handler failed to execute. reschedule.
            $objHandler->schedule();
            $objOneWorkflow->setIntState(class_modul_workflows_workflow::$INT_STATE_SCHEDULED);
            class_logger::getInstance()->addLogRow(" execution finished, new state: scheduled", class_logger::$levelInfo);
        }

        $objOneWorkflow->setIntRuns($objOneWorkflow->getIntRuns()+1);
        $objOneWorkflow->updateObjectToDb();

    }
    
}
?>