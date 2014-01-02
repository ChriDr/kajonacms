<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                      *
********************************************************************************************************/


/**
 * The base class for all page-elements
 *
 * @package module_pages
 * @author sidler@mulchprod.de
 * @abstract
 *
 * @module elements
 * @moduleId _pages_elemente_modul_id_
 */
abstract class class_element_admin extends class_admin {

    const STR_ANNOTATION_ELEMENTCONTENTTITLE = "@elementContentTitle";

    private $bitDoValidation = false;

    protected $arrParamData = array();

    /**
     * @var class_admin_formgenerator
     */
    private $objAdminForm = null;

    /**
     * Holds the content generated by the element to be places within the (hidden) system-form
     * elements aka. optional elements.
     *
     * @var string
     */
    private $strSystemFormElements = "";

    private $arrElementData = array();

    private $arrValidationErrors = array();

    /**
     * Constructor
     */
    public function __construct() {

        parent::__construct();

        if(validateSystemid($this->getSystemid()))
            $this->loadElementData();
    }



    /**
     * @return class_admin_formgenerator|null
     */
    public function getAdminForm() {
        if($this->objAdminForm == null) {
            $objAnnotations = new class_reflection($this);
            $arrTargetTables = $objAnnotations->getAnnotationValuesFromClass(class_orm_mapper::STR_ANNOTATION_TARGETTABLE);
            if(count($arrTargetTables) == 0) {
                return null;
            }


            $this->objAdminForm = new class_admin_formgenerator("", $this);
            $this->objAdminForm->generateFieldsFromObject();
        }

        return $this->objAdminForm;
    }


    /**
     * Legacy method for elements not yet supporting the annotation based forms / content updates
     *
     * @param array $arrElementData
     * @deprecated
     * @return string
     */
    public function getEditForm($arrElementData) {

    }

    /**
     * Hook-method to modify the form generated based on the current elements' annotations
     * Overwrite if required.
     *
     * @param class_admin_formgenerator $objForm
     *
     * @return class_admin_formgenerator
     */
    protected function updateEditForm(class_admin_formgenerator $objForm) {
        return $objForm;
    }

    /**
     * Forces the element to return a form and adds als stuff needed by the system to handle the request properly
     *
     * @param string $strMode edit || new
     *
     * @return string
     */
    final public function actionEdit($strMode = "edit") {

        //split modes - legacy definitions or coooooool declarative processing
        $objAnnotations = new class_reflection($this);
        $arrTargetTables = $objAnnotations->getAnnotationValuesFromClass(class_orm_mapper::STR_ANNOTATION_TARGETTABLE);
        if(count($arrTargetTables) == 0) {
            return $this->generateLegacyEdit($strMode);
        }


        $objORM = new class_orm_mapper($this);
        $objORM->initObjectFromDb();
        $objForm = $this->getAdminForm();

        //validation errors?
        if($this->bitDoValidation) {
            $objForm->validateForm();
        }

        $bitShow = false;

        $objStartDate = null;
        if(isset($this->arrElementData["system_date_start"]) && $this->arrElementData["system_date_start"] > 0) {
            $objStartDate = new class_date($this->arrElementData["system_date_start"]);
            $bitShow = true;
        }

        $objEndDate = null;
        if(isset($this->arrElementData["system_date_end"]) && $this->arrElementData["system_date_end"] > 0) {
            $objEndDate = new class_date($this->arrElementData["system_date_end"]);
            $bitShow = true;
        }

        $strInternalTitle = (isset($this->arrElementData["page_element_ph_title"]) ? $this->arrElementData["page_element_ph_title"] : "");
        if($strInternalTitle != "") {
            $bitShow = true;
        }


        $objForm->addFieldToHiddenGroup(new class_formentry_text("", "page_element_ph_title"))->setStrLabel($this->getLang("page_element_ph_title", "pages"))->setStrValue($strInternalTitle);
        $objForm->addFieldToHiddenGroup(new class_formentry_date("", "start"))->setStrLabel($this->getLang("page_element_start", "pages"))->setStrValue($objStartDate);
        $objForm->addFieldToHiddenGroup(new class_formentry_date("", "end"))->setStrLabel($this->getLang("page_element_end", "pages"))->setStrValue($objEndDate);
        $objForm->setBitHiddenElementsVisible($bitShow);
        $objForm->setStrHiddenGroupTitle($this->getLang("page_element_system_folder", "pages"));

        //Language is placed right here instead as a hidden field
        if($strMode == "edit") {
            $objForm->addField(new class_formentry_hidden("", "page_element_ph_language"))->setStrValue($this->arrElementData["page_element_ph_language"]);
        }
        else {
            $objForm->addField(new class_formentry_hidden("", "page_element_ph_language"))->setStrValue($this->getLanguageToWorkOn());
        }

        $objForm->addField(new class_formentry_hidden("", "placeholder"))->setStrValue($this->getParam("placeholder"));
        $objForm->addField(new class_formentry_hidden("", "systemid"))->setStrValue($this->getSystemid());
        $objForm->addField(new class_formentry_hidden("", "mode"))->setStrValue($strMode);
        $objForm->addField(new class_formentry_hidden("", "element"))->setStrValue($this->getParam("element"));

        //An finally the submit Button
        if($this->getParam("pe") != "") {
            $objForm->addField(new class_formentry_hidden("", "peClose"))->setStrValue("1")->setStrEntryName("peClose");
        }

        $strReturn = $objForm->renderForm(getLinkAdminHref("pages_content", "saveElement"));
        return $strReturn;
    }

    /**
     * Method still being kept for legacy elements, so admin-elements not yet switched to annotations
     * @param string $strMode
     *
     * @return string
     */
    private function generateLegacyEdit($strMode = "edit") {

        $strReturn = "";
        //Right before we do anything, load the data of the current element
        $arrElementData = $this->arrElementData;

        //Load the form generated by the element
        $strFormElement = $this->getEditForm(array_merge($arrElementData, $this->getAllParams()));

        //Start by creating the form & action
        $strReturn .= $this->objToolkit->formHeader(getLinkAdminHref("pages_content", "saveElement"), "elEditForm");

        //validation errors?
        if($this->bitDoValidation)
            $this->validateForm();

        $strReturn .= $this->objToolkit->getValidationErrors($this, "saveElement");


        //add a folder containing optional system-fields
        $strSystemFields = "";
        $bitShow = false;

        $objStartDate = null;
        if(isset($arrElementData["system_date_start"]) && $arrElementData["system_date_start"] > 0) {
            $objStartDate = new class_date($arrElementData["system_date_start"]);
            $bitShow = true;
        }

        $objEndDate = null;
        if(isset($arrElementData["system_date_end"]) && $arrElementData["system_date_end"] > 0) {
            $objEndDate = new class_date($arrElementData["system_date_end"]);
            $bitShow = true;
        }

        $strInternalTitle = (isset($arrElementData["page_element_ph_title"]) ? $arrElementData["page_element_ph_title"] : "");
        if($strInternalTitle != "") {
            $bitShow = true;
        }


        $strSystemFields .= $this->objToolkit->formInputText("page_element_ph_title", $this->getLang("page_element_ph_title", "pages"), $strInternalTitle);

        $strSystemFields .= $this->objToolkit->formDateSingle("start", $this->getLang("page_element_start", "pages"), $objStartDate);
        $strSystemFields .= $this->objToolkit->formDateSingle("end", $this->getLang("page_element_end", "pages"), $objEndDate);

        //add content from sub-classes
        $strSystemFields .= $this->strSystemFormElements;

        $strReturn .= $this->objToolkit->formOptionalElementsWrapper($strSystemFields, $this->getLang("page_element_system_folder", "pages"), $bitShow);

        //Adding the element-stuff
        $strReturn .= $strFormElement;

        //Language is placed right here instead as a hidden field
        if($strMode == "edit") {
            $strReturn .= $this->objToolkit->formInputHidden("page_element_ph_language", $arrElementData["page_element_ph_language"]);
        }
        else {
            $strReturn .= $this->objToolkit->formInputHidden("page_element_ph_language", $this->getLanguageToWorkOn());
        }

        $strReturn .= $this->objToolkit->formInputHidden("placeholder", $this->getParam("placeholder"));
        $strReturn .= $this->objToolkit->formInputHidden("systemid", $this->getSystemid());
        $strReturn .= $this->objToolkit->formInputHidden("mode", $strMode);
        $strReturn .= $this->objToolkit->formInputHidden("element", $this->getParam("element"));

        //An finally the submit Button
        $strEventhandler = "";
        if($this->getParam("pe") == 1) {
            $strReturn .= $this->objToolkit->formInputHidden("peClose", "1");
        }

        $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"), "Submit", $strEventhandler);
        $strReturn .= $this->objToolkit->formClose();
        //and close the element
        return $strReturn;
    }



    /**
     * Overwrite this function, if you want to validate passed form-input
     *
     * @return mixed
     * @deprecated
     */
    public function getRequiredFields() {
        return array();
    }

    /**
     * Method used to validate posted form-values.
     * NOTE: To work with this method, the derived class needs to implement
     * a method "getRequiredFields()", returning an array of field to validate.
     * The array returned by getRequiredFields() has to fit the format
     *  [fieldname] = type, whereas type can be one of
     * string, number, email, folder, systemid
     * The array saved in $this->$arrValidationErrors return by this method is empty in case of no validation Errors,
     * otherwise an array with the structure
     * [nonvalidField] = text from objText
     * is being created.
     *
     * @return bool
     * @deprecated
     */
    public function validateForm() {
        $arrReturn = array();

        $arrFieldsToCheck = $this->getRequiredFields();

        foreach($arrFieldsToCheck as $strFieldname => $strType) {

            //backwards compatibility
            if($strType == "string")
                $strType = "text";

            //backwards compatibility
            if($strType == "number")
                $strType = "numeric";

            $strValue = $this->getParam($strFieldname);

            if($strType == "date") {
                $objDate = new class_date("0");
                $objDate->generateDateFromParams($strFieldname, $this->getAllParams());
                $strValue = $objDate;
            }

            $objValidator = $this->getValidatorInstance($strType);
            if(!$objValidator->validate($strValue)) {
                if($this->getLang("required_" . $strFieldname) != "!required_" . $strFieldname . "!") {
                    $arrReturn[$strFieldname] = $this->getLang("required_" . $strFieldname);
                }
                else if($this->getLang($strFieldname) != "!" . $strFieldname . "!") {
                    $arrReturn[$strFieldname] = $this->getLang($strFieldname);
                }
                else {
                    $arrReturn[$strFieldname] = $this->getLang("required_" . $strFieldname);
                }

            }

        }
        $this->arrValidationErrors = array_merge($this->arrValidationErrors, $arrReturn);
        return (count($this->arrValidationErrors) == 0);
    }


    /**
     * Loads the validator identified by the passed name.
     *
     * @param string $strName
     * @return interface_validator
     * @throws class_exception
     * @deprecated
     */
    private function getValidatorInstance($strName) {
        $strClassname = "class_".$strName."_validator";
        if(class_resourceloader::getInstance()->getPathForFile("/system/validators/".$strClassname.".php")) {
            return new $strClassname();
        }
        else
            throw new class_exception("failed to load validator of type ".$strClassname, class_exception::$level_ERROR);
    }


    /**
     * Loads the data of the current element
     *
     * @return mixed
     */
    public final function loadElementData() {

        $objAnnotations = new class_reflection($this);
        $arrTargetTables = $objAnnotations->getAnnotationValuesFromClass(class_orm_mapper::STR_ANNOTATION_TARGETTABLE);
        $strTargetTable = "";
        if(count($arrTargetTables) != 0) {
            $objORM = new class_orm_mapper($this);
            $objORM->initObjectFromDb();
            $arrTables = explode(".", $arrTargetTables[0]);
            $strTargetTable = _dbprefix_.$arrTables[0];

        }
        else if($this->getArrModule("table") != "")
            $strTargetTable =  $this->getArrModule("table");

        //Element-Table given?
        if($strTargetTable != "") {
            $strQuery = "SELECT *
    					 FROM " . $strTargetTable . ",
    					 	  " . _dbprefix_ . "element,
    					 	  " . _dbprefix_ . "page_element,
    					 	  " . _dbprefix_ . "system
    					 LEFT JOIN " . _dbprefix_ . "system_date
    					    ON (system_id = system_date_id)
    					 WHERE element_name = page_element_ph_element
    					   AND page_element_id = content_id
    					   AND system_id = content_id
    					   AND system_id = ? ";
        }
        else {
            $strQuery = "SELECT *
    					 FROM " . _dbprefix_ . "element,
    					 	  " . _dbprefix_ . "page_element,
    					 	  " . _dbprefix_ . "system
    					 LEFT JOIN " . _dbprefix_ . "system_date
    					    ON (system_id = system_date_id)
    					 WHERE element_name = page_element_ph_element
    					   AND page_element_id = system_id
    					   AND system_id = ? ";

        }
        $this->arrElementData = class_carrier::getInstance()->getObjDB()->getPRow($strQuery, array($this->getSystemid()));
        return $this->arrElementData;
    }


    /**
     * @throws class_exception
     * @return void
     */
    public function updateForeignElement() {
        $objAnnotations = new class_reflection($this);
        $arrTargetTables = $objAnnotations->getAnnotationValuesFromClass(class_orm_mapper::STR_ANNOTATION_TARGETTABLE);
        if(count($arrTargetTables) != 0) {
            $objORM = new class_orm_mapper($this);
            $objORM->updateStateToDb();
        }

        //legacy code
        $strElementTableColumns = $this->getArrModule("tableColumns");
        if($strElementTableColumns != "") {

            //open new tx
            class_carrier::getInstance()->getObjDB()->transactionBegin();

            $arrElementParams = $this->getArrParamData();

            $arrTableRows = explode(",", $strElementTableColumns);
            if(count($arrTableRows) > 0) {
                $arrInserts = array();
                $arrParams = array();

                foreach($arrTableRows as $strTableColumnName) {

                    $strColumnValue = "";
                    if(isset($arrElementParams[$strTableColumnName])) {
                        $strColumnValue = $arrElementParams[$strTableColumnName];
                    }

                    $arrParams[] = $strColumnValue;
                    $arrInserts[] = " " . class_carrier::getInstance()->getObjDB()->encloseColumnName($strTableColumnName) . " = ? ";
                }

                $strRowUpdates = implode(", ", $arrInserts);
                $strUpdateQuery = " UPDATE " . $this->getTable() . " SET "
                        . $strRowUpdates .
                        " WHERE content_id= ? ";

                $arrParams[] = $this->getSystemid();

                if(!class_carrier::getInstance()->getObjDB()->_pQuery($strUpdateQuery, $arrParams)) {
                    class_carrier::getInstance()->getObjDB()->transactionRollback();
                }
                else {
                    class_carrier::getInstance()->getObjDB()->transactionCommit();
                }
            }
            else {
                throw new class_exception("Element has invalid tableRows value!!!", class_exception::$level_ERROR);
            }
        }
        else {
            //To remain backwards-compatible:
            //Call the save-method of the element instead or if the element wants to update its data specially
            if(method_exists($this, "actionSave") && !$this->actionSave($this->getSystemid())) {
                throw new class_exception("Element returned error saving to database!!!", class_exception::$level_ERROR);
            }
        }
    }

    /**
     * returns the table used by the element
     *
     * @return string
     */
    public function getTable() {
        $objAnnotations = new class_reflection($this);
        $arrTargetTables = $objAnnotations->getAnnotationValuesFromClass(class_orm_mapper::STR_ANNOTATION_TARGETTABLE);
        if(count($arrTargetTables) != 0) {
            $arrTable = explode(".", $arrTargetTables[0]);
            return _dbprefix_.$arrTable[0];
        }

        //legacy code
        return $this->getArrModule("table");
    }

    /**
     * The label of the first config-value.
     * Overwrite this method if the element makes use of a config-value.
     * The value itself may be read by accessing the instance of class_module_pages_pageelement
     * out of the admin-/portal-element-instance directly.
     *
     * @return string
     */
    public function getConfigVal1Name() {
        return "";
    }

    /**
     * The label of the second config-value.
     * Overwrite this method if the element makes use of a config-value.
     * The value itself may be read by accessing the instance of class_module_pages_pageelement
     * out of the admin-/portal-element-instance directly.
     *
     * @return string
     */
    public function getConfigVal2Name() {
        return "";
    }

    /**
     * The label of the third config-value.
     * Overwrite this method if the element makes use of a config-value.
     * The value itself may be read by accessing the instance of class_module_pages_pageelement
     * out of the admin-/portal-element-instance directly.
     *
     * @return string
     */
    public function getConfigVal3Name() {
        return "";
    }

    /**
     * Returns a short description of the saved content
     * Overwrite if needed
     *
     * @return string
     */
    public function getContentTitle() {
        $objAnnotations = new class_reflection($this);
        $arrProperties = $objAnnotations->getPropertiesWithAnnotation(class_element_admin::STR_ANNOTATION_ELEMENTCONTENTTITLE);
        if(count($arrProperties) > 0) {
            $this->loadElementData();
            $arrKeys = array_keys($arrProperties);
            $strGetter = $objAnnotations->getGetter($arrKeys[0]);
            if($strGetter != null) {
                //explicit casts required? could be relevant, depending on the target column type / database system
                return call_user_func(array($this, $strGetter));
            }
        }
        return "";
    }


    /**
     * Returns a textual description of the current element, based
     * on the lang key element_description.
     *
     * @return string
     * @since 3.2.1
     */
    public function getElementDescription() {
        $strName = uniSubstr(get_class($this), uniStrlen("class_"), -6);
        $strDesc = $this->getLang($strName . "_description");
        if($strDesc == "!" . $strName . "_description!") {
            $strDesc = "";
        }
        return $strDesc;
    }

    /**
     * Overwrite this method, if you want to execute
     * some special actions right after saving the element to the db, e.g.
     * cleanup functions.
     *
     * @since 3.2.1
     * @return void
     */
    public function doAfterSaveToDb() {
    }

    /**
     * Overwrite this method if you want to modify the params to be saved to the
     * database or run other actions right before the element is saved back to the database.
     *
     * @since 3.4.0
     * @return void
     */
    public function doBeforeSaveToDb() {
    }

    /**
     * If the form generated should be validated, pass true. This invokes
     * the internal validation and printing of errors.
     * By default, the value is false. The framework sets the value, so there's no
     * need to call this setter in concrete element classes.
     *
     * @param bool $bitDoValidation
     * @return void
     */
    public final function setDoValidation($bitDoValidation) {
        $this->bitDoValidation = $bitDoValidation;
    }

    /**
     * Sub-classes can use this method to add content to the system-form.
     * Elements in the system-form are hidden by default.
     * Using this form-section is usefull for mostly unused settings.
     *
     * @param string $strContent
     * @return void
     * @since 3.3
     *
     * @todo
     */
    protected final function addOptionalFormElement($strContent) {
        $this->strSystemFormElements .= $strContent;
    }


    /**
     * Returns the array of parameters passed by the request
     *
     * @return array
     */
    public function getArrParamData() {
        return $this->arrParamData;
    }

    /**
     * Sets the array of parameters passed by the request
     *
     * @param array $arrParamData
     * @return void
     */
    public function setArrParamData($arrParamData) {
        $this->arrParamData = $arrParamData;
    }

}

