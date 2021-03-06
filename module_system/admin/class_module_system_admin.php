<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                *
********************************************************************************************************/


/**
 * Class to handle infos about the system and to set systemwide properties
 *
 * @package module_system
 * @author sidler@mulchprod.de
 *
 * @module system
 * @moduleId _system_modul_id_
 */
class class_module_system_admin extends class_admin_simple implements interface_admin {

    /**
     * @return array
     */
    public function getOutputModuleNavi() {
        $arrReturn = array();
        $arrReturn[] = array("view", class_link::getLinkAdmin($this->getArrModule("modul"), "list", "", $this->getLang("action_list"), "", "", true, "adminnavi"));
        $arrReturn[] = array("edit", class_link::getLinkAdmin($this->getArrModule("modul"), "systemInfo", "", $this->getLang("action_system_info"), "", "", true, "adminnavi"));
        $arrReturn[] = array("right1", class_link::getLinkAdmin($this->getArrModule("modul"), "systemSettings", "", $this->getLang("action_system_settings"), "", "", true, "adminnavi"));
        $arrReturn[] = array("right2", class_link::getLinkAdmin($this->getArrModule("modul"), "systemTasks", "", $this->getLang("action_system_tasks"), "", "", true, "adminnavi"));
        $arrReturn[] = array("right3", class_link::getLinkAdmin($this->getArrModule("modul"), "systemlog", "", $this->getLang("action_systemlog"), "", "", true, "adminnavi"));
        if(_system_changehistory_enabled_ != "false")
            $arrReturn[] = array("right3", class_link::getLinkAdmin($this->getArrModule("modul"), "genericChangelog", "&bitBlockFolderview=true", $this->getLang("action_changelog"), "", "", true, "adminnavi"));
        $arrReturn[] = array("right5", class_link::getLinkAdmin($this->getArrModule("modul"), "aspects", "", $this->getLang("action_aspects"), "", "", true, "adminnavi"));
        $arrReturn[] = array("right1", class_link::getLinkAdmin($this->getArrModule("modul"), "systemSessions", "", $this->getLang("action_system_sessions"), "", "", true, "adminnavi"));
        $arrReturn[] = array("right1", class_link::getLinkAdmin($this->getArrModule("modul"), "lockedRecords", "", $this->getLang("action_locked_records"), "", "", true, "adminnavi"));
        $arrReturn[] = array("", "");
        $arrReturn[] = array("", class_link::getLinkAdmin($this->getArrModule("modul"), "about", "", $this->getLang("action_about"), "", "", true, "adminnavi"));
        $arrReturn[] = array("", "");
        $arrReturn[] = array("right", class_link::getLinkAdmin("right", "change", "&changemodule=".$this->getArrModule("modul"), $this->getLang("commons_module_permissions"), "", "", true, "adminnavi"));
        $arrReturn[] = array("right", class_link::getLinkAdmin("right", "change", "&systemid=0", $this->getLang("modul_rechte_root"), "", "", true, "adminnavi"));
        return $arrReturn;
    }

    /**
     * Sets the status of a module.
     * Therefore you have to be member of the admin-group.
     *
     * @permissions edit
     */
    protected function actionModuleStatus() {
        //status: for setting the status of modules, you have to be member of the admin-group
        $objUser = new class_module_user_user($this->objSession->getUserID());
        $arrGroups = $objUser->getObjSourceUser()->getGroupIdsForUser();
        $objModule = new class_module_system_module($this->getSystemid());
        if($objModule->rightEdit() && in_array(_admins_group_id_, $arrGroups)) {
            $objModule->setIntRecordStatus($objModule->getIntRecordStatus() == 0 ? 1 : 0);
            $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul")));
        }
    }

    /**
     * Renders the form to create a new entry
     *
     * @return string
     */
    protected function actionNew() {

    }

    /**
     * Renders the form to edit an existing entry
     *
     * @return string
     */
    protected function actionEdit() {

        $objInstance = class_objectfactory::getInstance()->getObject($this->getSystemid());
        if($objInstance instanceof class_module_system_aspect)
            $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "editAspect", "&systemid=".$objInstance->getSystemid()));

        if($objInstance instanceof class_module_system_module)
            $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "list"));
    }


    /**
     * Creates a list of all installed modules
     *
     * @return string
     * @permissions view
     * @autoTestable
     */
    protected function actionList() {

        $objIterator = new class_array_section_iterator(class_module_system_module::getObjectCount());
        $objIterator->setPageNumber($this->getParam("pv"));
        $objIterator->setArraySection(class_module_system_module::getAllModules($objIterator->calculateStartPos(), $objIterator->calculateEndPos()));

        return $this->renderList($objIterator, true, "moduleList");

    }

    /**
     * @param class_model $objListEntry
     *
     * @return array
     */
    protected function renderAdditionalActions(class_model $objListEntry) {
        if($objListEntry instanceof class_module_system_module) {
            $arrReturn = array();
            $arrReturn[] = $this->objToolkit->listButton(class_link::getLinkAdminDialog("system", "moduleAspect", "&systemid=".$objListEntry->getSystemid(), "", $this->getLang("modul_aspectedit"), "icon_aspect", $this->getLang("modul_aspectedit")));

            if($objListEntry->rightEdit() && in_array(_admins_group_id_, $this->objSession->getGroupIdsAsArray())) {
                if($objListEntry->getStrName() == "system")
                    $arrReturn[] = $this->objToolkit->listButton(class_link::getLinkAdmin("system", "moduleList", "", "", $this->getLang("modul_status_system"), "icon_enabled"));
                else if($objListEntry->getIntRecordStatus() == 0)
                    $arrReturn[] = $this->objToolkit->listButton(class_link::getLinkAdmin("system", "moduleStatus", "&systemid=".$objListEntry->getSystemid(), "", $this->getLang("modul_status_disabled"), "icon_disabled"));
                else
                    $arrReturn[] = $this->objToolkit->listButton(class_link::getLinkAdmin("system", "moduleStatus", "&systemid=".$objListEntry->getSystemid(), "", $this->getLang("modul_status_enabled"), "icon_enabled"));
            }

            return $arrReturn;
        }
        return parent::renderAdditionalActions($objListEntry);
    }

    /**
     * @param class_model $objListEntry
     *
     * @return string
     */
    protected function renderStatusAction(class_model $objListEntry) {
        if($objListEntry instanceof class_module_system_module)
            return "";

        return parent::renderStatusAction($objListEntry);
    }


    /**
     * @param class_model $objListEntry
     * @param bool $bitDialog
     *
     * @return string
     */
    protected function renderEditAction(class_model $objListEntry, $bitDialog = false) {
        if($objListEntry instanceof class_module_system_module)
            return "";

        return parent::renderEditAction($objListEntry);
    }


    /**
     * @param interface_model $objListEntry
     *
     * @return string
     */
    protected function renderDeleteAction(interface_model $objListEntry) {
        if($objListEntry instanceof class_module_system_module)
            return "";

        if($objListEntry instanceof class_module_system_aspect && $objListEntry->rightDelete())
            return $this->objToolkit->listDeleteButton($objListEntry->getStrName(), $this->getLang("aspect_delete_question"), class_link::getLinkAdminHref($this->getArrModule("modul"), "deleteAspect", "&systemid=".$objListEntry->getSystemid()));

        return parent::renderDeleteAction($objListEntry);
    }

    /**
     * @param string $strListIdentifier
     * @param bool $bitDialog
     *
     * @return array|string
     */
    protected function getNewEntryAction($strListIdentifier, $bitDialog = false) {
        if($strListIdentifier == "moduleList")
            return "";

        if($strListIdentifier == "aspectList" && $this->getObjModule()->rightEdit())
            return $this->objToolkit->listButton(getLinkAdmin($this->getArrModule("modul"), "newAspect", "", $this->getLang("aspect_create"), $this->getLang("aspect_create"), "icon_new"));

        return parent::getNewEntryAction($strListIdentifier);
    }

    /**
     * @param class_model $objListEntry
     *
     * @return string
     */
    protected function renderCopyAction(class_model $objListEntry) {
        return "";
    }


    /**
     * Creates the form to manipulate the aspects of a single module
     *
     * @return string
     * @permissions right5
     */
    protected function actionModuleAspect() {
        $this->setArrModuleEntry("template", "/folderview.tpl");
        $strReturn = "";
        $objModule = class_module_system_module::getModuleBySystemid($this->getSystemid());
        $strReturn .= $this->objToolkit->formHeadline($objModule->getStrName());
        $arrAspectsSet = explode(",", $objModule->getStrAspect());
        $strReturn .= $this->objToolkit->formHeader(class_link::getLinkAdminHref($this->getArrModule("modul"), "saveModuleAspect"));
        $arrAspects = class_module_system_aspect::getObjectList();
        foreach($arrAspects as $objOneAspect)
            $strReturn .= $this->objToolkit->formInputCheckbox("aspect_".$objOneAspect->getSystemid(), $objOneAspect->getStrName(), in_array($objOneAspect->getSystemid(), $arrAspectsSet));

        $strReturn .= $this->objToolkit->formInputHidden("systemid", $this->getSystemid());
        $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
        $strReturn .= $this->objToolkit->formClose();

        return $strReturn;
    }

    /**
     * @return string
     * @permissions right5
     */
    protected function actionSaveModuleAspect() {
        $arrParams = array();
        foreach($this->getAllParams() as $strName => $intValue)
            if(uniStrpos($strName, "aspect_") !== false)
                $arrParams[] = uniSubstr($strName, 7);

        $objModule = class_module_system_module::getModuleBySystemid($this->getSystemid());
        $objModule->setStrAspect(implode(",", $arrParams));

        $objModule->updateObjectToDb();
        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "list", "peClose=1&blockAction=1"));
    }


    /**
     * Shows information about the current system
     *
     * @return string
     * @permissions edit
     */
    protected function actionSystemInfo() {
        $strReturn = "";

        $objPluginmanager = new class_pluginmanager(interface_systeminfo::STR_EXTENSION_POINT);
        /** @var interface_systeminfo[] $arrPlugins */
        $arrPlugins = $objPluginmanager->getPlugins();

        foreach($arrPlugins as $objOnePlugin) {
            $strContent = $this->objToolkit->dataTable(null, $objOnePlugin->getArrContent());
            $strReturn .= $this->objToolkit->getFieldset($objOnePlugin->getStrTitle(), $strContent);
        }

        return $strReturn;
    }

    // -- SystemSettings ------------------------------------------------------------------------------------

    /**
     * Creates a form to edit systemsettings or updates them
     *
     * @return string "" in case of success
     * @autoTestable
     * @permissions right1
     */
    protected function actionSystemSettings() {
        $strReturn = "";
        //Check for needed rights
        if($this->getParam("save") != "true") {
            //Create a warning before doing s.th.
            $strReturn .= $this->objToolkit->warningBox($this->getLang("warnung_settings"));

            $arrTabs = array();

            $arrSettings = class_module_system_setting::getAllConfigValues();
            /** @var class_module_system_module $objCurrentModule */
            $objCurrentModule = null;
            $strRows = "";
            foreach($arrSettings as $objOneSetting) {
                if($objCurrentModule === null || $objCurrentModule->getIntNr() != $objOneSetting->getIntModule()) {
                    $objTemp = $this->getModuleDataID($objOneSetting->getIntModule(), true);
                    if($objTemp !== null) {
                        //In the first loop, ignore the output
                        if($objCurrentModule !== null) {
                            //Build a form to return
                            $strTabContent = $this->objToolkit->formHeader(class_link::getLinkAdminHref($this->getArrModule("modul"), "systemSettings"));
                            $strTabContent .= $strRows;
                            $strTabContent .= $this->objToolkit->formInputHidden("save", "true");
                            $strTabContent .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
                            $strTabContent .= $this->objToolkit->formClose();
                            $arrTabs[$this->getLang("modul_titel", $objCurrentModule->getStrName())] = $strTabContent;
                        }
                        $strRows = "";
                        $objCurrentModule = $objTemp;
                    }
                }
                //Build the rows
                //Print a help-text?
                $strHelper = $this->getLang($objOneSetting->getStrName()."hint", $objCurrentModule->getStrName());
                if($strHelper != "!".$objOneSetting->getStrName()."hint!")
                    $strRows .= $this->objToolkit->formTextRow($strHelper);

                //The input element itself
                if($objOneSetting->getIntType() == 0) {
                    $arrDD = array();
                    $arrDD["true"] = $this->getLang("commons_yes");
                    $arrDD["false"] = $this->getLang("commons_no");
                    $strRows .= $this->objToolkit->formInputDropdown("set[".$objOneSetting->getSystemid()."]", $arrDD, $this->getLang($objOneSetting->getStrName(), $objCurrentModule->getStrName()), $objOneSetting->getStrValue());
                }
                elseif($objOneSetting->getIntType() == 3) {
                    $strRows .= $this->objToolkit->formInputPageSelector("set[".$objOneSetting->getSystemid()."]", $this->getLang($objOneSetting->getStrName(), $objCurrentModule->getStrName()), $objOneSetting->getStrValue());
                }
                else {
                    $strRows .= $this->objToolkit->formInputText("set[".$objOneSetting->getSystemid()."]", $this->getLang($objOneSetting->getStrName(), $objCurrentModule->getStrName()), $objOneSetting->getStrValue());
                }
            }
            //Build a form to return -> include the last module
            $strTabContent = $this->objToolkit->formHeader(class_link::getLinkAdminHref($this->getArrModule("modul"), "systemSettings"));
            $strTabContent .= $strRows;
            $strTabContent .= $this->objToolkit->formInputHidden("save", "true");
            $strTabContent .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
            $strTabContent .= $this->objToolkit->formClose();

            $arrTabs[$this->getLang("modul_titel", $objCurrentModule->getStrName())] = $strTabContent;

            $strReturn .= $this->objToolkit->getTabbedContent($arrTabs);
        }
        else {
            //Seems we have to update a few records
            $arrSettings = $this->getAllParams();
            foreach($arrSettings["set"] as $strKey => $strValue) {
                $objSetting = new class_module_system_setting($strKey);
                $objSetting->setStrValue($strValue);
                $objSetting->updateObjectToDb();
            }
            $strReturn .= $this->objToolkit->warningBox($this->getLang("settings_updated"));
        }

        return $strReturn;
    }


    /**
     * Loads the list of all systemtasks available and creates the form required to trigger a task
     *
     * @return string
     * @autoTestable
     * @permissions right2
     */
    protected function actionSystemTasks() {
        $strReturn = "";
        $strTaskOutput = "";

        //include the list of possible tasks
        //TODO: move to common helper, see class_module_system_admin_xml. additionally use the common plugin mechanism
        $arrFiles = class_resourceloader::getInstance()->getFolderContent("/admin/systemtasks/", array(".php"), false, function(&$strOneFile) {
            if($strOneFile == "class_systemtask_base.php" || $strOneFile == "interface_admin_systemtask.php")
                return false;

            $strOneFile = uniSubstr($strOneFile, 0, -4);
            $strOneFile = new $strOneFile();

            if($strOneFile instanceof interface_admin_systemtask)
                return true;
            else
                return false;
        });

        //react on special task-commands?
        if($this->getParam("task") != "") {
            //search for the matching task
            /** @var $objTask interface_admin_systemtask */
            foreach($arrFiles as $objTask) {
                if($objTask->getStrInternalTaskname() == $this->getParam("task")) {
                    $strTaskOutput .= self::getTaskDialogExecuteCode($this->getParam("execute") == "true", $objTask, "system", "systemTasks", $this->getParam("executedirectly") == "true");
                    break;
                }
            }
        }

        $intI = 0;
        //loop over the found files and group them
        $arrTaskGroups = array();
        foreach($arrFiles as $objTask) {
            if(!isset($arrTaskGroups[$objTask->getGroupIdentifier()]))
                $arrTaskGroups[$objTask->getGroupIdentifier()] = array();

            $arrTaskGroups[$objTask->getGroupIdentifier()][] = $objTask;
        }

        ksort($arrTaskGroups);

        foreach($arrTaskGroups as $strGroupName => $arrTasks) {
            if($strGroupName == "")
                $strGroupName = "default";


            $strReturn .= $this->objToolkit->formHeadline($this->getLang("systemtask_group_".$strGroupName));
            $strReturn .= $this->objToolkit->listHeader();
            /** @var $objOneTask interface_admin_systemtask */
            foreach($arrTasks as $objOneTask) {

                //generate the link to execute the task
                $strLink = class_link::getLinkAdmin(
                    "system",
                    "systemTasks",
                    "&task=".$objOneTask->getStrInternalTaskName(),
                    $objOneTask->getStrTaskname(),
                    $this->getLang("systemtask_run"),
                    "icon_accept"
                );

                $strReturn .= $this->objToolkit->genericAdminList(
                    generateSystemid(),
                    $objOneTask->getStrTaskname(),
                    class_adminskin_helper::getAdminImage("icon_systemtask"),
                    $this->objToolkit->listButton($strLink),
                    $intI++
                );
            }
            $strReturn .= $this->objToolkit->listFooter();
        }

        //include js-code & stuff to handle executions
        $strReturn .= self::getTaskDialogCode();
        $strReturn = $strTaskOutput.$strReturn;

        return $strReturn;
    }

    /**
     * Renders the code to run and execute a systemtask. You only need this if you want to provide the user an additional place
     * to run a systemtask besides the common place at module system / admin.
     *
     * @param bool $bitExecute
     * @param class_systemtask_base $objTask
     * @param string $strModule
     * @param string $strAction
     * @param bool $bitExecuteDirectly
     *
     * @return string
     */
    public static function getTaskDialogExecuteCode($bitExecute, class_systemtask_base $objTask, $strModule = "", $strAction = "", $bitExecuteDirectly = false) {
        $objLang = class_carrier::getInstance()->getObjLang();
        $strTaskOutput = "";


        //If the task is going to be executed, validate the form first (if form is of type class_admin_formgenerator)
        $objAdminForm = null;
        if($bitExecute) {
            $objAdminForm = $objTask->getAdminForm();
            if($objAdminForm !== null && $objAdminForm instanceof class_admin_formgenerator) {
                if(!$objAdminForm->validateForm()) {
                    $bitExecute = false;
                }
            }
        }

        //execute the task or show the form?
        if($bitExecute) {
            if($bitExecuteDirectly) {
                $strTaskOutput .= $objTask->executeTask();
            }
            else {
                $strTaskOutput .= "
                <script type=\"text/javascript\">
                $(function() {
                   setTimeout(function() {
                        KAJONA.admin.systemtask.executeTask('".$objTask->getStrInternalTaskname()."', '".$objTask->getSubmitParams()."');
                        KAJONA.admin.systemtask.setName('".$objLang->getLang("systemtask_runningtask", "system")." ".$objTask->getStrTaskName()."');
                   }, 500);
                 });
                </script>";
            }
        }
        else {
            $strForm = $objTask->generateAdminForm($strModule, $strAction, $objAdminForm);
            if($strForm != "") {
                $strTaskOutput .= $strForm;
            }
            else {
                $strLang = class_carrier::getInstance()->getObjLang()->getLang("systemtask_runningtask", "system");
                $strTaskJS = <<<JS
                $(function() {
                    setTimeout(function() {
                        KAJONA.admin.systemtask.executeTask('{$objTask->getStrInternalTaskName()}', '');
                        KAJONA.admin.systemtask.setName('{$strLang} {$objTask->getStrTaskName()}');
                    }, 500);
                });
JS;
                $strTaskOutput .= "<script type='text/javascript'>".$strTaskJS."</script>";
            }
        }

        return $strTaskOutput.class_carrier::getInstance()->getObjToolkit("admin")->jsDialog(0);
    }

    /**
     * Renders the code to wrap systemtask
     * @return string
     */
    public static function getTaskDialogCode() {
        $objLang = class_carrier::getInstance()->getObjLang();
        $strDialogContent = "<div id=\"systemtaskLoadingDiv\" class=\"loadingContainer loadingContainerBackground\"></div><br /><b id=\"systemtaskNameDiv\"></b><br /><br /><div id=\"systemtaskStatusDiv\"></div><br /><input id=\"systemtaskCancelButton\" type=\"submit\" value=\"".$objLang->getLang("systemtask_cancel_execution", "system")."\" class=\"btn inputSubmit\" /><br />";
        return "<script type=\"text/javascript\">
            var KAJONA_SYSTEMTASK_TITLE = '".$objLang->getLang("systemtask_dialog_title", "system")."';
            var KAJONA_SYSTEMTASK_TITLE_DONE = '".$objLang->getLang("systemtask_dialog_title_done", "system")."';
            var KAJONA_SYSTEMTASK_CLOSE = '".$objLang->getLang("systemtask_close_dialog", "system")."';
            var kajonaSystemtaskDialogContent = '".$strDialogContent."';
            </script>";
    }

    /**
     * Renders a list of records currently locked
     *
     * @permissions right1
     * @return string
     * @autoTestable
     */
    protected function actionLockedRecords() {
        $objArraySectionIterator = new class_array_section_iterator(class_lockmanager::getLockedRecordsCount());
        $objArraySectionIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(class_lockmanager::getLockedRecords($objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        $strReturn = "";
        if(!$objArraySectionIterator->valid()) {
            $strReturn .= $this->getLang("commons_list_empty");
        }

        $strReturn .= $this->objToolkit->listHeader();

        foreach($objArraySectionIterator as $objOneRecord) {

            $strImage = "";
            if($objOneRecord instanceof interface_admin_listable) {
                $strImage = $objOneRecord->getStrIcon();
                if(is_array($strImage))
                    $strImage = class_adminskin_helper::getAdminImage($strImage[0], $strImage[1]);
                else
                    $strImage = class_adminskin_helper::getAdminImage($strImage);
            }

            $strActions = $this->objToolkit->listButton(class_link::getLinkAdmin($this->getArrModule("modul"), "lockedRecords", "&unlockid=".$objOneRecord->getSystemid(), $this->getLang("action_unlock_record"), $this->getLang("action_unlock_record"), "icon_lockerOpen"));
            $objLockUser = new class_module_user_user($objOneRecord->getLockManager()->getLockId());

            $strReturn .= $this->objToolkit->genericAdminList(
                $objOneRecord->getSystemid(),
                $objOneRecord instanceof interface_model ? $objOneRecord->getStrDisplayName() : get_class($objOneRecord),
                $strImage,
                $strActions,
                0,
                get_class($objOneRecord),
                $this->getLang("locked_record_info", array(dateToString(new class_date($objOneRecord->getIntLockTime())), $objLockUser->getStrDisplayName()))
            );
        }

        $strReturn .= $this->objToolkit->listFooter();

        $strReturn .= $this->objToolkit->getSimplePageview($objArraySectionIterator, "system", "systemSessions");

        return $strReturn;
    }


    /**
     * Creates a table filled with the sessions currently registered
     *
     * @autoTestable
     * @return string
     * @permissions right1
     */
    protected function actionSystemSessions() {
        $strReturn = "";
        //react on commands?
        if($this->getParam("logout") == "true") {
            $objSession = new class_module_system_session($this->getSystemid());
            $objSession->setStrLoginstatus(class_module_system_session::$LOGINSTATUS_LOGGEDOUT);
            $objSession->updateObjectToDb();
            class_carrier::getInstance()->getObjDB()->flushQueryCache();
        }

        //showing a list using the pageview
        $objArraySectionIterator = new class_array_section_iterator(class_module_system_session::getNumberOfActiveSessions());
        $objArraySectionIterator->setIntElementsPerPage(_admin_nr_of_rows_);
        $objArraySectionIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(class_module_system_session::getAllActiveSessions($objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        $arrData = array();
        $arrHeader = array();
        $arrHeader[0] = "";
        $arrHeader[1] = $this->getLang("session_username");
        $arrHeader[2] = $this->getLang("session_valid");
        $arrHeader[3] = $this->getLang("session_status");
        $arrHeader[4] = $this->getLang("session_activity");
        $arrHeader[5] = "";
        /** @var $objOneSession class_module_system_session */
        foreach($objArraySectionIterator as $objOneSession) {
            $arrRowData = array();
            $strUsername = "";
            if($objOneSession->getStrUserid() != "") {
                $objUser = new class_module_user_user($objOneSession->getStrUserid());
                $strUsername = $objUser->getStrUsername();
            }
            $arrRowData[0] = class_adminskin_helper::getAdminImage("icon_user");
            $arrRowData[1] = $strUsername;
            $arrRowData[2] = timeToString($objOneSession->getIntReleasetime());
            if($objOneSession->getStrLoginstatus() == class_module_system_session::$LOGINSTATUS_LOGGEDIN)
                $arrRowData[3] = $this->getLang("session_loggedin");
            else
                $arrRowData[3] = $this->getLang("session_loggedout");

            //find out what the user is doing...
            $strLastUrl = $objOneSession->getStrLasturl();
            if(uniStrpos($strLastUrl, "?") !== false)
                $strLastUrl = uniSubstr($strLastUrl, uniStrpos($strLastUrl, "?"));
            $strActivity = "";

            if(uniStrpos($strLastUrl, "admin=1") !== false) {
                $strActivity .= $this->getLang("session_admin");
                foreach(explode("&amp;", $strLastUrl) as $strOneParam) {
                    $arrUrlParam = explode("=", $strOneParam);
                    if($arrUrlParam[0] == "module")
                        $strActivity .= $arrUrlParam[1];
                }
            }
            else {
                $strActivity .= $this->getLang("session_portal");
                if($strLastUrl == "")
                    $strActivity .= defined("_pages_indexpage_") ? _pages_indexpage_ : "";
                else {
                    foreach(explode("&amp;", $strLastUrl) as $strOneParam) {
                        $arrUrlParam = explode("=", $strOneParam);
                        if($arrUrlParam[0] == "page")
                            $strActivity .= $arrUrlParam[1];
                    }

                    if($strActivity == $this->getLang("session_portal") && uniSubstr($strLastUrl, 0, 5) == "image") {
                        $strActivity .= $this->getLang("session_portal_imagegeneration");
                    }
                }
            }

            $arrRowData[4] = $strActivity;
            if($objOneSession->getStrLoginstatus() == class_module_system_session::$LOGINSTATUS_LOGGEDIN)
                $arrRowData[5] = class_link::getLinkAdmin("system", "systemSessions", "&logout=true&systemid=".$objOneSession->getSystemid(), "", $this->getLang("session_logout"), "icon_delete");
            else
                $arrRowData[5] = class_adminskin_helper::getAdminImage("icon_deleteDisabled");
            $arrData[] = $arrRowData;
        }
        $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrData);
        $strReturn .= $this->objToolkit->getSimplePageview($objArraySectionIterator, "system", "systemSessions");

        return $strReturn;
    }


    /**
     * Fetches the entries from the system-log an prints them as preformatted text
     *
     * @return string
     * @autoTestable
     * @permissions right3
     */
    protected function actionSystemlog() {

        //load logfiles available
        $objFilesystem = new class_filesystem();
        $arrFiles = $objFilesystem->getFilelist(_projectpath_."/log", array(".log"));

        $arrTabs = array();

        foreach($arrFiles as $strName) {
            $objFilesystem->openFilePointer(_projectpath_."/log/".$strName, "r");
            $strLogContent = $objFilesystem->readLastLinesFromFile(20);
            $strLogContent = str_replace(array("INFO", "ERROR"), array("INFO   ", "ERROR  "), $strLogContent);
            $arrLogEntries = explode("\r", $strLogContent);
            $objFilesystem->closeFilePointer();

            $arrTabs[$strName] = $this->objToolkit->getPreformatted($arrLogEntries);
        }

        return $this->objToolkit->getTabbedContent($arrTabs);
    }

    /**
     * Renders the list of changes for the passed systemrecord.
     * May be called from other modules in order to get the rendered list for a single record.
     * In most cases rendered as a overlay, so in folderview mode
     *
     * @param string $strSystemid sytemid to filter
     * @param string $strSourceModule source-module, required for a working pageview
     * @param string $strSourceAction source-action, required for a working pageview
     * @param bool $bitBlockFolderview
     *
     * @return string
     * @since 3.4.0
     * @autoTestable
     * @permissions changelog
     */
    public function actionGenericChangelog($strSystemid = "", $strSourceModule = "system", $strSourceAction = "genericChangelog", $bitBlockFolderview = false) {

        if(!$bitBlockFolderview && $this->getParam("bitBlockFolderview") == "")
            $this->setArrModuleEntry("template", "/folderview.tpl");

        if($strSystemid == "")
            $strSystemid = $this->getSystemid();

        $strReturn = "";
//        check needed rights - done twice since public and callable by not only the controller
//        if(!class_carrier::getInstance()->getObjRights()->validatePermissionString(class_rights::$STR_RIGHT_CHANGELOG, $this->getObjModule()))
//            return $this->getLang("commons_error_permissions");

        //showing a list using the pageview
        $objArraySectionIterator = new class_array_section_iterator(class_module_system_changelog::getLogEntriesCount($strSystemid));
        $objArraySectionIterator->setIntElementsPerPage(_admin_nr_of_rows_);
        $objArraySectionIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(class_module_system_changelog::getLogEntries($strSystemid, $objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        $arrData = array();
        $arrHeader = array();
        $arrHeader[] = $this->getLang("commons_date");
        $arrHeader[] = $this->getLang("change_user");
        if($strSystemid == "")
            $arrHeader[] = $this->getLang("change_module");
        if($strSystemid == "")
            $arrHeader[] = $this->getLang("change_record");
        $arrHeader[] = $this->getLang("change_action");
        $arrHeader[] = $this->getLang("change_property");
        $arrHeader[] = $this->getLang("change_oldvalue");
        $arrHeader[] = $this->getLang("change_newvalue");

        /** @var $objOneEntry class_changelog_container */
        foreach($objArraySectionIterator as $objOneEntry) {
            $arrRowData = array();

            /** @var interface_versionable|class_model $objTarget */
            $objTarget = $objOneEntry->getObjTarget();

            $strOldValue = $objOneEntry->getStrOldValue();
            $strNewValue = $objOneEntry->getStrNewValue();

            if($objTarget != null) {
                $strOldValue = $objTarget->renderVersionValue($objOneEntry->getStrProperty(), $strOldValue);
                $strNewValue = $objTarget->renderVersionValue($objOneEntry->getStrProperty(), $strNewValue);
            }

            $arrRowData[] = dateToString($objOneEntry->getObjDate());
            $arrRowData[] = $this->objToolkit->getTooltipText(uniStrTrim($objOneEntry->getStrUsername(), 15), $objOneEntry->getStrUsername());
            if($strSystemid == "")
                $arrRowData[] = $objTarget != null ? $objTarget->getArrModule("modul") : "";
            if($strSystemid == "")
                $arrRowData[] = $objTarget != null ? $this->objToolkit->getTooltipText(uniStrTrim($objTarget->getVersionRecordName(), 20), $objTarget->getVersionRecordName()." ".$objOneEntry->getStrSystemid()) : "";
            $arrRowData[] = $objTarget != null ? $this->objToolkit->getTooltipText(uniStrTrim($objTarget->getVersionActionName($objOneEntry->getStrAction()), 15), $objTarget->getVersionActionName($objOneEntry->getStrAction())) : "";
            $arrRowData[] = $objTarget != null ? $this->objToolkit->getTooltipText(uniStrTrim($objTarget->getVersionPropertyName($objOneEntry->getStrProperty()), 20), $objTarget->getVersionPropertyName($objOneEntry->getStrProperty())) : "";
            $arrRowData[] = $this->objToolkit->getTooltipText(uniStrTrim($strOldValue, 20), $strOldValue);
            $arrRowData[] = $this->objToolkit->getTooltipText(uniStrTrim($strNewValue, 20), $strNewValue);

            $arrData[] = $arrRowData;
        }
        $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrData);

        $strReturn .= $this->objToolkit->getSimplePageview($objArraySectionIterator, $strSourceModule, $strSourceAction, "&systemid=".$strSystemid."&bitBlockFolderview=".$this->getParam("bitBlockFolderview"));

        return $strReturn;
    }

    /**
     * Renders the list of aspects available
     *
     * @return string
     * @autoTestable
     * @permissions right5
     */
    protected function actionAspects() {
        $objIterator = new class_array_section_iterator(class_module_system_aspect::getObjectCount());
        $objIterator->setPageNumber($this->getParam("pv"));
        $objIterator->setArraySection(class_module_system_aspect::getObjectList("", $objIterator->calculateStartPos(), $objIterator->calculateEndPos()));
        return $this->renderList($objIterator, false, "aspectList");
    }

    /**
     * Delegate to actionNewAspect
     *
     * @return string
     * @see actionNewAspect
     */
    protected function actionEditAspect() {
        return $this->actionNewAspect("edit");
    }

    /**
     * Creates the form to edit an existing aspect or to create a new one
     *
     * @param string $strMode
     * @param class_admin_formgenerator $objFormManager
     *
     * @return string
     * @permissions right5
     */
    protected function actionNewAspect($strMode = "new", class_admin_formgenerator $objFormManager = null) {

        $objAspect = null;
        if($strMode == "new") {
            $objAspect = new class_module_system_aspect();
        }
        else if($strMode == "edit") {
            $objAspect = new class_module_system_aspect($this->getSystemid());
            if(!$objAspect->rightEdit())
                $objAspect = null;
        }

        if($objAspect != null) {

            if($objFormManager == null)
                $objFormManager = $this->getFormForAspect($objAspect);

            $objFormManager->addField(new class_formentry_hidden("", "mode"))->setStrValue($strMode);
            $strReturn = $objFormManager->renderForm(class_link::getLinkAdminHref($this->getArrModule("modul"), "saveAspect"));

        }
        else
            $strReturn = $this->getLang("commons_error_permissions");

        return $strReturn;
    }

    /**
     * Creates the admin-form to edit / create an aspect
     *
     * @param class_module_system_aspect $objAspect
     *
     * @return class_admin_formgenerator
     */
    private function getFormForAspect(class_module_system_aspect $objAspect) {
        $objFormManager = new class_admin_formgenerator("aspect", $objAspect);
        $objFormManager->generateFieldsFromObject();
        return $objFormManager;
    }

    /**
     * saves the submitted form-data as a new aspect or updates an existing one
     *
     * @throws class_exception
     * @return string, "" in case of success
     * @permissions right5
     */
    protected function actionSaveAspect() {
        $objAspect = null;

        if($this->getParam("mode") == "new")
            $objAspect = new class_module_system_aspect();
        else if($this->getParam("mode") == "edit")
            $objAspect = new class_module_system_aspect($this->getSystemid());

        if($objAspect != null) {

            $objFormManager = $this->getFormForAspect($objAspect);

            if(!$objFormManager->validateForm())
                return $this->actionNewAspect($this->getParam("mode"), $objFormManager);

            $objFormManager->updateSourceObject();

            if(!$objAspect->updateObjectToDb())
                throw new class_exception("Error creating new aspect", class_exception::$level_ERROR);
        }
        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "aspects"));
        return "";
    }

    /**
     * Deletes an aspect
     *
     * @throws class_exception
     * @return string
     */
    protected function actionDeleteAspect() {
        $objAspect = new class_module_system_aspect($this->getSystemid());
        if($objAspect->rightDelete() && $objAspect->rightRight5()) {
            if(!$objAspect->deleteObject())
                throw new class_exception("Error deleting aspect", class_exception::$level_ERROR);

            $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "aspects"));
        }
        else
            return $this->getLang("commons_error_permissions");
        return "";
    }


    /**
     * About Kajona, credits and co
     *
     * @return string
     * @autoTestable
     * @permissions view
     */
    protected function actionAbout() {
        $strReturn = "";
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part1"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2a_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2a"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2b_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2b"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part5_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part5"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part3_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part3"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part4"));
        return $strReturn;
    }


    /**
     * Creates a form to send mails to specific users.
     * @return class_admin_formgenerator
     */
    private function getMailForm() {
        $objFormgenerator = new class_admin_formgenerator("mail", new class_module_system_common());
        $objFormgenerator->addField(new class_formentry_text("mail", "recipient"))->setStrLabel($this->getLang("mail_recipient"))->setBitMandatory(true)->setObjValidator(new class_email_validator());
        $objFormgenerator->addField(new class_formentry_user("mail", "cc"))->setStrLabel($this->getLang("mail_cc"));
        $objFormgenerator->addField(new class_formentry_text("mail", "subject"))->setStrLabel($this->getLang("mail_subject"))->setBitMandatory(true);
        $objFormgenerator->addField(new class_formentry_textarea("mail", "body"))->setStrLabel($this->getLang("mail_body"))->setBitMandatory(true);
        return $objFormgenerator;
    }


    /**
     * Generates a form in order to send an email.
     * This form is generic, so it may be called from several places.
     * If a mail-address was passed by param "mail_recipient", the form tries to send the message by mail,
     * otherwise (default) the message is delivered using the messaging. Therefore the param mail_to_id is expected when being
     * triggered externally.
     *
     * @param class_admin_formgenerator $objForm
     *
     * @return string
     * @since 3.4
     * @autoTestable
     * @permissions view
     */
    protected function actionMailForm(class_admin_formgenerator $objForm = null) {
        $this->setArrModuleEntry("template", "/folderview.tpl");

        if($objForm == null)
            $objForm = $this->getMailForm();

        return $objForm->renderForm(class_link::getLinkAdminHref($this->getArrModule("modul"), "sendMail"));
    }

    /**
     * Sends an email. In most cases this mail was generated using the form
     * provided by actionMailForm
     *
     * @return string
     * @since 3.4
     * @permissions view
     */
    protected function actionSendMail() {

        $objForm = $this->getMailForm();

        if(!$objForm->validateForm())
            return $this->actionMailForm($objForm);

        $this->setArrModuleEntry("template", "/folderview.tpl");
        $objUser = new class_module_user_user($this->objSession->getUserID());

        //mail or internal message?
        $objMailValidator = new class_email_validator();
        $objEmail = new class_mail();

        $objEmail->setSender($objUser->getStrEmail());
        $arrRecipients = explode(",", $this->getParam("mail_recipient"));
        foreach($arrRecipients as $strOneRecipient)
            if($objMailValidator->validate($strOneRecipient))
                $objEmail->addTo($strOneRecipient);

        if($objForm->getField("mail_cc")->getStrValue() != "") {
            $objUser = new class_module_user_user($objForm->getField("mail_cc")->getStrValue());
            $objEmail->addCc($objUser->getStrEmail());
        }

        $objEmail->setSubject($objForm->getField("mail_subject")->getStrValue());
        $objEmail->setText($objForm->getField("mail_body")->getStrValue());

        if($objEmail->sendMail())
            return $this->getLang("mail_send_success");
        else
            return $this->getLang("mail_send_error");
    }


    /**
     * Loads the data for one module
     *
     * @param int $intModuleID
     * @param bool $bitZeroIsSystem
     *
     * @return class_module_system_module
     */
    private function getModuleDataID($intModuleID, $bitZeroIsSystem = false) {
        $arrModules = class_module_system_module::getAllModules();

        if($intModuleID != 0 || !$bitZeroIsSystem) {
            foreach($arrModules as $objOneModule) {
                if($objOneModule->getIntNr() == $intModuleID)
                    return $objOneModule;
            }
        }
        elseif($intModuleID == 0 && $bitZeroIsSystem) {
            foreach($arrModules as $objOneModule) {
                if($objOneModule->getStrName() == "system")
                    return $objOneModule;
            }
        }
        return null;
    }

}

