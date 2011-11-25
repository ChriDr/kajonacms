<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                            *
********************************************************************************************************/

/**
 * The Base-Class for all other admin-classes
 *
 * @package module_system
 */
abstract class class_admin {


	/**
     * Instance of class_config
     *
     * @var class_config
     */
	protected $objConfig = null;			//Object containing config-data
	/**
	 * Instance of class_db
	 *
	 * @var class_db
	 */
	protected $objDB = null;				//Object to the database
	/**
	 * Instance of class_toolkit_admin
	 *
	 * @var class_toolkit_admin
	 */
	protected $objToolkit = null;			//Toolkit-Object
	/**
	 * Instance of class_session
	 *
	 * @var class_session
	 */
	protected $objSession = null;			//Object containting the session-management
	/**
	 * Instance of class_template
	 *
	 * @var class_template
	 */
	protected $objTemplate = null;			//Object to handle templates
	/**
	 * Instance of class_rights
	 *
	 * @var class_rights
	 */
	protected $objRights = null;			//Object handling the right-stuff

	/**
	 * Instance of class_texte
	 *
	 * @var class_texte
	 */
	private   $objText = null;				//Object managing the textfiles

	/**
	 * Instance of class_modul_system_common
	 *
	 * @var class_modul_system_common
	 */
	private $objSystemCommon = null;

    /**
     * Instance of the current modules' definition
     *
     * @var class_modul_system_module
     */
    private $objModule = null;

	private   $strAction;			        //current action to perform (GET/POST)
	private   $strSystemid;			        //current systemid
	private   $arrParams;			        //array containing other GET / POST / FILE variables
	private   $strArea;				        //String containing the current Area - admin or portal or installer or download
	private   $strTextBase;                 //String containing the current module to be used to load texts
	private   $arrHistory;			        //Stack cotaining the 5 urls last visited
	protected $arrModule;			        //Array containing Infos about the current modul
	protected $strTemplateArea;		        //String containing the current Area for the templateobject
	protected $strOutput;                   //String containing the output generated by an internal action
	private   $arrOutput;					      //Array containing the admin-output
	protected $arrValidationErrors = array();     //Array to keep found validation errors

	/**
	 * Constructor
	 *
	 * @param array $arrModul
	 * @param string $strSystemid
	 */
	public function __construct($arrModul = array(), $strSystemid = "") {

		$arrModul["p_name"] 			= "modul_admin";
		$arrModul["p_author"] 			= "sidler@mulchprod.de";
		$arrModul["p_nummer"] 			= _system_modul_id_;

		//default-template: main.tpl
		if(!key_exists("template", $arrModul))
		    $arrModul["template"] 		= "/main.tpl";

		//Registering Area
		$this->strArea = "admin";

		//Merging Module-Data
		$this->arrModule =  $arrModul;

		//GET / POST / FILE Params
		$this->arrParams = getAllPassedParams();

		//Setting SystemID
		if($strSystemid == "") {
			if(isset($this->arrParams["systemid"]))
				$this->setSystemid($this->arrParams["systemid"]);
			else
				$this->strSystemid = "";
		}
		else
			$this->setSystemid($strSystemid);


		//Generating all the needed Objects. For this we use our cool cool carrier-object
		//take care of loading just the necessary objects
		$objCarrier = class_carrier::getInstance();
		$this->objConfig = $objCarrier->getObjConfig();
		$this->objDB = $objCarrier->getObjDB();
		$this->objToolkit = $objCarrier->getObjToolkit($this->strArea);
		$this->objSession = $objCarrier->getObjSession();
		$this->objText = $objCarrier->getObjText();
		$this->objTemplate = $objCarrier->getObjTemplate();
		$this->objRights = $objCarrier->getObjRights();
		$this->objSystemCommon = new class_modul_system_common($strSystemid);


		//Setting template area LATERON THE SKIN IS BEING SET HERE
		$this->setTemplateArea("");

		//Writing to the history
        if(!_xmlLoader_)
            $this->setHistory();

		//And keep the action
		$this->strAction = $this->getParam("action");
		//in most cases, the list is the default action if no other action was passed
		if($this->strAction == "")
		    $this->strAction = "list";

		//set the correct language to the text-object
		$this->objText->setStrTextLanguage($this->objSession->getAdminLanguage(true));

		$this->strTextBase = $this->arrModule["modul"];

        //define the print-view, if requested
        if($this->getParam("printView") != "")
             $this->arrModule["template"] = "/print.tpl";
	}

    // --- Common Methods -----------------------------------------------------------------------------------


	/**
	 * Writes a value to the params-array
	 *
	 * @param string $strKey Key
	 * @param mixed $mixedValue Value
     * @return void
	 */
	public function setParam($strKey, $mixedValue) {
		$this->arrParams[$strKey] = $mixedValue;
	}

	/**
	 * Returns a value from the params-Array
	 *
	 * @param string $strKey
	 * @return string else ""
	 */
	public function getParam($strKey) {
		if(isset($this->arrParams[$strKey])) {
			return $this->arrParams[$strKey];
        }
		else {
			return "";
        }
	}

	/**
	 * Returns the complete Params-Array
	 *
	 * @return mixed
	 * @final
	 */
	public final function getAllParams() {
	    return $this->arrParams;
	}

	/**
	 * returns the action used for the current request
	 *
	 * @return string
	 * @final
	 */
	public final function getAction() {
	    return (string)$this->strAction;
	}

    /**
     * Overwrites the current action
     * @param string $strAction
     */
    public final function setAction($strAction) {
	    $this->strAction = $strAction;
	}


    // --- SystemID & System-Table Methods ------------------------------------------------------------------

	/**
	 * Sets the current SystemID
	 *
	 * @param string $strID
	 * @return bool
	 * @final
	 */
	public final function setSystemid($strID) {
		if(validateSystemid($strID)) {
			$this->strSystemid = $strID;
			return true;
		}
		else
			return false;
	}

	/**
	 * Returns the current SystemID
	 *
	 * @return string
	 * @final
	 */
	public final function getSystemid() {
		return $this->strSystemid;
	}

	/**
	 * Returns the current instance of the class_rights
	 *
	 * @return object
	 * @final
	 */
	public final  function getObjRights() {
	    return $this->objRights;
	}

	/**
	 * Negates the status of a systemRecord
	 *
	 * @param string $strSystemid
	 * @return bool
	 */
	public function setStatus($strSystemid = "") {
		if($strSystemid == "")
			$strSystemid = $this->getSystemid();
        $objCommon = new class_modul_system_common($strSystemid);
		return $objCommon->setStatus();
	}

	/**
	 * Gets the status of a systemRecord
	 *
	 * @param string $strSystemid
	 * @return int
	 */
	public function getStatus($strSystemid = "") {
		if($strSystemid == "0" || $strSystemid == "")
			$strSystemid = $this->getSystemid();
        $objCommon = new class_modul_system_common($strSystemid);
		return $objCommon->getStatus();
	}

	/**
	 * Returns the name of the user who last edited the record
	 *
	 * @param string $strSystemid
	 * @return string
	 */
	public function getLastEditUser($strSystemid = "") {
		if($strSystemid == 0 || $strSystemid == "")
			$strSystemid = $this->getSystemid();
        $objCommon = new class_modul_system_common($strSystemid);
		return $objCommon->getLastEditUser();
	}

	/**
	 * Gets the Prev-ID of a record
	 *
	 * @param string $strSystemid
	 * @return string
	 */
	public function getPrevId($strSystemid = "") {
		if($strSystemid == "")
			$strSystemid = $this->getSystemid();
        $objCommon = new class_modul_system_common($strSystemid);
		return $objCommon->getPrevId();

	}

	/**
	 * Sets the Position of a SystemRecord in the currect level one position upwards or downwards
	 *
	 * @param string $strIdToShift
	 * @param string $strDirection upwards || downwards
	 * @return void
	 */
	public function setPosition($strIdToShift, $strDirection = "upwards") {
	    $this->objSystemCommon->setPosition($strIdToShift, $strDirection);
        $this->flushCompletePagesCache();
	}

    /**
	 * Sets the Position of a SystemRecord in the currect level one position upwards or downwards
     * and reloads the page viewed before
	 *
	 * @param string $strIdToShift
	 * @param string $strDirection upwards || downwards
     * @since 3.4.0
	 * @return void
	 */
	public function setPositionAndReload($strIdToShift, $strDirection = "upwards") {
	    $this->objSystemCommon->setPosition($strIdToShift, $strDirection);
        $this->flushCompletePagesCache();
        $this->adminReload(_indexpath_."?".$this->getHistory(1));
	}

	/**
	 * Sets the position of systemid using a given value.
	 *
	 * @param string $strIdToSet
	 * @param int $intPosition
	 */
	public function setAbsolutePosition($strIdToSet, $intPosition) {
		$this->objSystemCommon->setAbsolutePosition($strIdToSet, $intPosition);
        $this->flushCompletePagesCache();
	}

	/**
	 * Returns the data for a registered module
	 *
	 * @param string $strName
	 * @param bool $bitCache
	 * @return mixed
	 */
	public function getModuleData($strName, $bitCache = true) {
	    return $this->objSystemCommon->getModuleData($strName, $bitCache);

	}

	/**
	 * Returns the SystemID of a installed module
	 *
	 * @param string $strModule
	 * @return string "" in case of an error
	 */
	public function getModuleSystemid($strModule) {
        $objModule = class_modul_system_module::getModuleByName($strModule);
        if($objModule != null)
            return $objModule->getSystemid();
        else
            return "";
	}

	/**
	 * Generates a sorted array of systemids, reaching from the passed systemid up
	 * until the assigned module-id
	 *
	 * @param string $strSystemid
     * @param string $strStopSystemid
	 * @return mixed
	 */
	public function getPathArray($strSystemid = "", $strStopSystemid = "") {
		if($strSystemid == "")
			$strSystemid = $this->getSystemid();
        if($strStopSystemid == "")
            $strStopSystemid = $this->getModuleSystemid($this->arrModule["modul"]);

		return $this->objSystemCommon->getPathArray($strSystemid, $strStopSystemid);
	}

	/**
	 * Returns a value from the $arrModule array.
	 * If the requested key not exists, returns ""
	 *
	 * @param string $strKey
	 * @return string
	 */
	public function getArrModule($strKey) {
	    if(isset($this->arrModule[$strKey]))
	        return $this->arrModule[$strKey];
	    else
	        return "";
	}

	/**
	 * Writes a key-value-pair to the arrModule
	 *
	 * @param string $strKey
	 * @param mixed $strValue
	 */
	public function setArrModuleEntry($strKey, $strValue) {
	    $this->arrModule[$strKey] = $strValue;
	}

    /**
     * Creates a text-based decription of the current module.
     * Therefore the text-entry module_description should be available.
     *
     * @return string
     * @since 3.2.1
     */
    public function getModuleDescription() {
        $strDesc = $this->getText("module_description");
        if($strDesc != "!module_description!")
            return $strDesc;
        else
            return "";
    }

    // --- HistoryMethods -----------------------------------------------------------------------------------

	/**
	 * Holds the last 5 URLs the user called in the Session
	 * Admin and Portal are seperated arrays, but don't worry anyway...
	 *
	 */
	protected function setHistory() {
	    //Loading the current history from session
		$this->arrHistory = $this->objSession->getSession($this->strArea."History");

		$strQueryString = getServer("QUERY_STRING");
		//Clean Querystring of emtpy actions
		if(uniSubstr($strQueryString, -8) == "&action=")
		   $strQueryString = substr_replace($strQueryString, "", -8);
		//Just do s.th., if not in the rights-mgmt
	    if(uniStrpos($strQueryString, "module=right") !== false)
	       return;
	    //And insert just, if different to last entry
	    if($strQueryString == $this->getHistory())
	       return;
        //If we reach up here, we can enter the current query
		if($this->arrHistory !== false) {
			array_unshift($this->arrHistory, $strQueryString);
			while(count($this->arrHistory) > 5) {
				array_pop($this->arrHistory);
			}
		}
		else {
			$this->arrHistory[] = $strQueryString;
		}
		//saving the new array to session
		$this->objSession->setSession($this->strArea."History", $this->arrHistory);
	}

	/**
	 * Returns the URL at the given position (from HistoryArray)
	 *
	 * @param int $intPosition
	 * @return string
	 */
	protected function getHistory($intPosition = 0) {
		if(isset($this->arrHistory[$intPosition]))
			return $this->arrHistory[$intPosition];
		else
			return "History error!"	;
	}

    // --- TextMethods --------------------------------------------------------------------------------------

    /**
	 * Used to get Text out of Textfiles
	 *
	 * @param string $strName
	 * @param string $strModule
	 * @param string $strArea
	 * @return string
	 */
	public function getText($strName, $strModule = "", $strArea = "") {
		if($strModule == "")
			$strModule = $this->strTextBase;

		if($strArea == "")
			$strArea = $this->strArea;

		//Now we have to ask the Text-Object to return the text
		return $this->objText->getText($strName, $strModule, $strArea);
	}

	/**
	 * Sets the textbase, so the module used to load texts
	 * @param string $strTextbase
	 */
	protected final function setStrTextBase($strTextbase) {
	    $this->strTextBase = $strTextbase;
	}

	/**
	 * Returns the current Text-Object Instance
	 *
	 * @return obj
	 */
	protected function getObjText() {
	    return $this->objText;
	}

	/**
	 * Sets the current area in the template object to have it work as expected
	 *
	 * @param string $strArea
	 */
	protected  function setTemplateArea($strArea) {
	    if($this->objTemplate != null)
		    $this->objTemplate->setArea($this->strArea.$strArea);
	}


    // --- PageCache Features -------------------------------------------------------------------------------

	/**
	 * Deletes the complete Pages-Cache
	 *
	 * @return bool
	 */
	public function flushCompletePagesCache() {
        return class_cache::flushCache("class_element_portal");
	}

	/**
	 * Removes one page from the cache
	 *
	 * @param string $strPagename
	 * @return bool
	 */
	public function flushPageFromPagesCache($strPagename) {
        //since the navigation may depend on page-internal characteristics, the complete cache is
        //flushed instead only the current page
	    return class_cache::flushCache("class_element_portal");
	}




    // --- OutputMethods ------------------------------------------------------------------------------------

	/**
	 * Basic controller method invoking all further methods in order to generate an admin view.
     * Takes care of generating the navigation, title, common JS variables, loading quickhelp texts,...
	 *
	 * @return string
	 * @final
	 */
	public final function getModuleOutput() {

        $this->validateAndUpdateCurrentAspect();

		//Calling the contentsetter
		$this->arrOutput["content"] = $this->getOutputContent();
		$this->arrOutput["mainnavi"] = $this->getOutputMainNavi();
		$this->arrOutput["modulenavi"] = $this->getOutputModuleActionsNavi();
		$this->arrOutput["moduletitle"] = $this->getOutputModuleTitle();
        if(class_modul_system_aspect::getNumberOfAspectsAvailable(true) > 1)
            $this->arrOutput["aspectChooser"] = $this->objToolkit->getAspectChooser($this->arrModule["modul"], $this->getAction(), $this->getSystemid());
		$this->arrOutput["login"] = $this->getOutputLogin();
		$this->arrOutput["quickhelp"] = $this->getQuickHelp();
		$this->arrOutput["module_id"] = $this->arrModule["moduleId"];
		$this->arrOutput["webpathTitle"] = urldecode(str_replace(array("http://", "https://"), array("", ""), _webpath_));
		$this->arrOutput["head"] = "<script type=\"text/javascript\">KAJONA_DEBUG = ".$this->objConfig->getDebug("debuglevel")."; KAJONA_WEBPATH = '"._webpath_."'; KAJONA_BROWSER_CACHEBUSTER = "._system_browser_cachebuster_.";</script>";
		//Loading the desired Template
		//if requested the pe, load different template
        $strTemplateID = "";
		if($this->getParam("peClose") == 1 || $this->getParam("pe") == 1) {
		    //add suffix
		    try {
		        $strTemplate = str_replace(".tpl", "", $this->arrModule["template"])."_portaleditor.tpl";
		        $strTemplateID = $this->objTemplate->readTemplate($strTemplate, "", false, true);
		    } catch (class_exception $objException) {
		        //An error occured. In most cases, this is because the user ist not logged in, so the login-template was requested.
		        if($this->arrModule["template"] == "/login.tpl")
		            throw new class_exception("You have to be logged in to use the portal editor!!!", class_exception::$level_ERROR);
		    }
		}
		else
		    $strTemplateID = $this->objTemplate->readTemplate($this->arrModule["template"]);
		return $this->objTemplate->fillTemplate($this->arrOutput, $strTemplateID);
	}

    /**
     * Validates if the requested module is valid for the current aspect.
     * If necessary, the current aspect is updated.
     *
     * @return void
     */
    private function validateAndUpdateCurrentAspect() {
        if(_xmlLoader_ === true || $this->arrModule["template"] == "/folderview.tpl")
            return;

        $arrModule = $this->getModuleData($this->arrModule["modul"]);
        $strCurrentAspect = class_modul_system_aspect::getCurrentAspectId();
        if(isset($arrModule["module_aspect"]) && $arrModule["module_aspect"] != "") {
            $arrAspects = explode(",", $arrModule["module_aspect"]);
            if(count($arrAspects) == 1 && $arrAspects[0] != $strCurrentAspect) {
                class_modul_system_aspect::setCurrentAspectId($arrAspects[0]);
            }

        }
    }


	/**
	 * Loads the content the module itself created.
	 * Since version 3.4, there's no need to implemented this method on your own.
     * Nevertheless, you can override this method if you want to perform special
     * action / transformations to the content generated right before placing it
     * into the response.
	 *
	 * @return string
     * @deprecated handled by the internal action()
	 */
	protected function getOutputContent() {
	    return $this->strOutput;
	}

	/**
	 * Tries to generate a quick-help button.
	 * Tests for exisiting help texts
	 *
	 * @return string
	 */
	protected function getQuickHelp() {
        $strReturn = "";
        $strText = "";
        $strTextname = "";

        //Text for the current action available?
        //different loading when editing page-elements
        if($this->getParam("module") == "pages_content" && ($this->getParam("action") == "editElement" || $this->getParam("action") == "newElement")) {
            $objElement = null;
            if($this->getParam("action") == "editElement") {
                $objElement = new class_modul_pages_pageelement($this->getSystemid());
            }
            else if ($this->getParam("action") == "newElement") {
                $strPlaceholderElement = $this->getParam("element");
                $objElement = class_modul_pages_element::getElement($strPlaceholderElement);
            }
            //Load the class to create an instance
            include_once _adminpath_."/elemente/".$objElement->getStrClassAdmin();
            //Build the class-name
            $strElementClass = str_replace(".php", "", $objElement->getStrClassAdmin());
            //and finally create the object
            $objElement = new $strElementClass();
            $strTextname = "quickhelp_".$objElement->getArrModule("name");
            $strText = class_carrier::getInstance()->getObjText()->getText($strTextname, $objElement->getArrModule("modul"), "admin");
        }
        else {
            $strTextname = "quickhelp_".$this->strAction;
            $strText = $this->getText($strTextname);
        }


        if($strText != "!".$strTextname."!") {
            //Text found, embed the quickhelp into the current skin
            $strReturn .= $this->objToolkit->getQuickhelp($strText);
        }

        return $strReturn;
	}

	/**
	 * Writes the Main Navi, overwrite if needed ;)
	 * Creates a list of all installed modules
	 *
	 * @return string
	 */
	protected function getOutputMainNavi() {
		if($this->objSession->isLoggedin()) {
			//Loading all Modules
			$arrModules = class_modul_system_module::getModulesInNaviAsArray(class_modul_system_aspect::getCurrentAspectId());
			$intI = 0;
			$arrModuleRows = array();
			foreach ($arrModules as $arrModule) {
				if($this->objRights->rightView($arrModule["module_id"])) {
					//Generate a view infos
				    $arrModuleRows[$intI]["rawName"] = $arrModule["module_name"];
					$arrModuleRows[$intI]["name"] = $this->getText("modul_titel", $arrModule["module_name"]);
					$arrModuleRows[$intI]["link"] = getLinkAdmin($arrModule["module_name"], "", "", $arrModule["module_name"], $arrModule["module_name"], "", true, "adminModuleNavi");
					$arrModuleRows[$intI]["href"] = getLinkAdminHref($arrModule["module_name"], "");
					$intI++;
				}
			}
			//NOTE: Some special Modules need other highlights
			if($this->arrModule["name"] == "modul_pages_elemente")
			    $strCurrent = "module_pages";
			else
			    $strCurrent = $this->arrModule["name"];

			return $this->objToolkit->getAdminModuleNavi($arrModuleRows, $strCurrent);
		}
	}

	/**
	 * Writes the navigaiton to represent the action-navigation.
	 * Each module can create its own actions
	 *
	 * @return string
	 */
	private function getOutputModuleActionsNavi() {
		if($this->objSession->isLoggedin()) {
		    $arrItems = $this->getOutputModuleNavi();
		    $arrFinalItems = array();

            $objModule = class_modul_system_module::getModuleByName($this->arrModule["modul"]);
		    //build array of final items
		    foreach($arrItems as $arrOneItem) {
		        $bitAdd = false;
		        switch ($arrOneItem[0]) {
            	case "view":
                    if($objModule->rightView())
                        $bitAdd = true;
	        		break;
	        	case "edit":
                    if($objModule->rightEdit())
                        $bitAdd = true;
                    break;
                case "delete":
                    if($objModule->rightDelete())
                        $bitAdd = true;
                    break;
                case "right":
                    if($objModule->rightRight())
                        $bitAdd = true;
                    break;
                case "right1":
                    if($objModule->rightRight1())
                        $bitAdd = true;
                    break;
                case "right2":
                    if($objModule->rightRight2())
                        $bitAdd = true;
                    break;
                case "right3":
                    if($objModule->rightRight3())
                        $bitAdd = true;
                    break;
                case "right4":
                    if($objModule->rightRight4())
                        $bitAdd = true;
                    break;
                case "right5":
                    if($objModule->rightRight5())
                        $bitAdd = true;
                    break;
                case "":
                    $bitAdd = true;
                    break;
                default:
                    break;
		        }

		        if($bitAdd || $arrOneItem[1] == "")
                    $arrFinalItems[] = $arrOneItem[1];
		    }

			//Pass to the skin-object
            return $this->objToolkit->getAdminModuleActionNavi($arrFinalItems);
		}
	}


	/**
	 * Writes the ModuleNavi, overwrite if needed
	 * Use two-dim arary:
	 * array[
	 *     array["right", "link"],
	 *     array["right", "link"]
	 * ]
	 *
	 * @return array array containing all links
	 */
	protected function getOutputModuleNavi() {
		return array();
	}

	/**
	 * Writes the ModuleTitle, overwrite if needed
	 *
	 */
	protected function getOutputModuleTitle() {
	    if($this->getText("modul_titel") != "!modul_titel!")
	       return $this->getText("modul_titel");
	    else
	       return $this->arrModule["name"];
	}

	/**
	 * Writes the SessionInfo, overwrite if needed
	 *
	 */
	protected function getOutputLogin() {
		$objLogin = new class_modul_login_admin();
		return $objLogin->getLoginStatus();
	}

    /**
     * This method triggers the internal processing.
     * It may be overridden if required, e.g. to implement your own action-handling.
     * By default, the method to be called is set up out of the action-param passed.
     * Example: The action requested is names "newPage". Therefore, the framework tries to
     * call actionNewPage(). If no method matching the schema is found, an exception is being thrown.
     * The actions' output is saved back to self::strOutput and, in is returned in addition.
     * Returning the content is only implemented to remain backwards compatible with older implementations.
     *
     *
     * @param string $strAction
     * @return string
     * @since 3.4
     */
    public function action($strAction = "") {

        if($strAction == "")
            $strAction = $this->strAction;
        else
            $this->strAction = $strAction;

        //search for the matching method - build method name
        $strMethodName = "action".uniStrtoupper($strAction[0]).uniSubstr($strAction, 1);

        if(method_exists($this, $strMethodName)) {

            //validate the loading channel - xml or regular
            if(_xmlLoader_ === true) {
                //check it the method is allowed for xml-requests
                $objAnnotations = new class_annotations(get_class($this));
                if(!$objAnnotations->hasMethodAnnotation($strMethodName, "@xml") && substr(get_class($this), -3) != "xml")
                    throw new class_exception("called method ".$strMethodName." not allowed for xml-requests", class_exception::$level_FATALERROR);

                if($this->arrModule["modul"] != $this->getParam("module")) {
                    header(class_http_statuscodes::$strSC_UNAUTHORIZED);
                    throw new class_exception("you are not authorized/authenticated to call this action", class_exception::$level_FATALERROR);
                }
            }


            $this->strOutput = $this->$strMethodName();
        }
        else {
            $objReflection = new ReflectionClass($this);
            //if the pe was requested and the current module is a login-module, there are unsufficient permsissions given
            if($this->arrModule["template"] == "/login.tpl" && $this->getParam("pe") != "")
                throw new class_exception("You have to be logged in to use the portal editor!!!", class_exception::$level_ERROR);

            if(get_class($this) == "class_modul_login_admin_xml") {
                header(class_http_statuscodes::$strSC_UNAUTHORIZED);
                throw new class_exception("you are not authorized/authenticated to call this action", class_exception::$level_FATALERROR);
            }

            throw new class_exception("called method ".$strMethodName." not existing for class ".$objReflection->getName(), class_exception::$level_FATALERROR);
        }

        return $this->strOutput;
    }


    //--- FORM-Validation -----------------------------------------------------------------------------------

    /**
     * Method used to validate posted form-values.
     * NOTE: To work with this method, the derived class needs to implement
     * a method "getRequiredFields()", returning an array of field to validate.
     * The array returned by getRequiredFields() has to fit the format
     *  [fieldname] = type, whereas type can be one of
     * string, number, email, folder, systemid
     *
     * The array saved in $this->$arrValidationErrors return by this method is empty in case of no validation Errors,
     * otherwise an array with the structure
     * [nonvalidField] = text from objText
     * is being created.
     *
     * @return bool
     */
    protected function validateForm() {
        $arrReturn = array();

        $arrFieldsToCheck = $this->getRequiredFields();

        foreach($arrFieldsToCheck as $strFieldname => $strType) {

            $bitAdd = false;

            if($strType == "string") {
                if(!checkText($this->getParam($strFieldname), 2))
                    $bitAdd = true;
            }
            else if($strType == "character") {
                if(!checkText($this->getParam($strFieldname), 1))
                    $bitAdd = true;
            }
            elseif($strType == "number") {
                if(!checkNumber($this->getParam($strFieldname)))
                    $bitAdd = true;
            }
            elseif($strType == "email") {
                if(!checkEmailaddress($this->getParam($strFieldname)))
                    $bitAdd = true;
            }
            elseif($strType == "folder") {
                if(!checkFolder($this->getParam($strFieldname)))
                    $bitAdd = true;
            }
            elseif($strType == "systemid") {
                if(!validateSystemid($this->getParam($strFieldname)))
                    $bitAdd = true;
            }
            elseif($strType == "date") {
                if(!checkNumber($this->getParam($strFieldname))) {
                    $objDate = new class_date("0");
                    $objDate->generateDateFromParams($strFieldname, $this->getAllParams());
                    if((int)$objDate->getLongTimestamp() == 0)
                        $bitAdd = true;
                }
            }
            else {
                $arrReturn[$strFieldname] = "No or unknown validation-type for ".$strFieldname." given";
            }

            if($bitAdd) {
                if( $this->getText("required_".$strFieldname) != "!required_".$strFieldname."!")
                    $arrReturn[$strFieldname] = $this->getText("required_".$strFieldname);
                else if($this->getText($strFieldname) != "!".$strFieldname."!")
                    $arrReturn[$strFieldname] = $this->getText($strFieldname);
                else
                    $arrReturn[$strFieldname] = $this->getText("required_".$strFieldname);
            }

        }
        $this->arrValidationErrors = array_merge($this->arrValidationErrors, $arrReturn);
        return (count($this->arrValidationErrors) == 0);
    }

    /**
     * Overwrite this function, if you want to validate passed form-input
     *
     * @return mixed
     */
    public function getRequiredFields() {
        return array();
    }

    /**
     * Returns the array of validationErrors
     *
     * @return mixed
     */
    public function getValidationErrors() {
        return $this->arrValidationErrors;
    }

    /**
     * Adds a validation error to the array of errors
     *
     * @param string $strField
     * @param string $strErrormessage
     */
    public function addValidationError($strField, $strErrormessage) {
        $this->arrValidationErrors[$strField] = $strErrormessage;
    }

    /**
     * Use this method to reload a specific url.
     * <b>Use ONLY this method and DO NOT use header("Location: ...");</b>
     *
     * @param string $strUrlToLoad
     */
    public function adminReload($strUrlToLoad) {
        //filling constants
        $strUrlToLoad = str_replace("_webpath_", _webpath_, $strUrlToLoad);
        $strUrlToLoad = str_replace("_indexpath_", _indexpath_, $strUrlToLoad);
        //No redirect, if close-Command for admin-area should be sent
        if($this->getParam("peClose") == "") {
            header("Location: ".str_replace("&amp;", "&", $strUrlToLoad));
        }
    }

    /**
     * Loads the language to edit content
     *
     * @return string
     */
    public function getLanguageToWorkOn() {
        return $this->objSystemCommon->getStrAdminLanguageToWorkOn();
    }

    /**
     * Returns the current instance of class_modul_system_module, based on the current subclass.
     * Lazy-loading, so loaded on first access.
     * @return class_modul_system_module|null
     */
    protected function getObjModule() {

        if($this->objModule == null)
            $this->objModule = class_modul_system_module::getModuleByName($this->arrModule["modul"]);

        return $this->objModule;
    }
}

?>