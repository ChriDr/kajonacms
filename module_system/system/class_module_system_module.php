<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                *
********************************************************************************************************/

/**
 * Model for a single system-module
 *
 * @package module_system
 * @targetTable system_module.module_id
 *
 * @module system
 * @moduleId _system_modul_id_
 */
class class_module_system_module extends class_model implements interface_model, interface_admin_listable {

    /**
     * @var string
     * @tableColumn system_module.module_name
     * @tableColumnDatatype char254
     */
    private $strName = "";

    /**
     * @var string
     * @tableColumn system_module.module_filenameportal
     * @tableColumnDatatype char254
     */
    private $strNamePortal = "";

    /**
     * @var string
     * @tableColumn system_module.module_xmlfilenameportal
     * @tableColumnDatatype char254
     */
    private $strXmlNamePortal = "";

    /**
     * @var string
     * @tableColumn system_module.module_filenameadmin
     * @tableColumnDatatype char254
     */
    private $strNameAdmin = "";

    /**
     * @var string
     * @tableColumn system_module.module_xmlfilenameadmin
     * @tableColumnDatatype char254
     */
    private $strXmlNameAdmin = "";

    /**
     * @var string
     * @tableColumn system_module.module_version
     * @tableColumnDatatype char254
     */
    private $strVersion = "";

    /**
     * @var int
     * @tableColumn system_module.module_date
     * @tableColumnDatatype int
     */
    private $intDate = "";

    /**
     * @var int
     * @tableColumn system_module.module_navigation
     * @tableColumnDatatype int
     */
    private $intNavigation = "";

    /**
     * @var int
     * @tableColumn system_module.module_nr
     * @tableColumnDatatype int
     */
    private $intNr = "";

    /**
     * @var string
     * @tableColumn system_module.module_aspect
     * @tableColumnDatatype char254
     */
    private $strAspect = "";

    /**
     * @var class_module_system_module[]
     */
    private static $arrModules = array();

    /**
     * @var string[][]
     */
    private static $arrModuleData = array();

    /**
     * Constructor to create a valid object
     *
     * @param string $strSystemid
     */
    public function __construct($strSystemid = "") {
        //base class
        parent::__construct($strSystemid);

        if(validateSystemid($strSystemid)) {
            self::$arrModules[$strSystemid] = $this;
        }
    }

    /**
     * Loads all module data from the database
     */
    public static function staticConstruct() {
        self::loadModuleData();
    }


    /**
     * Initialises the internal modules-cache.
     * Loads all module-data into a single array.
     * Avoids multiple queries against the module-table.
     *
     * @param bool $bitCache
     *
     * @return array
     * @static
     */
    private static function loadModuleData($bitCache = true) {

        if((count(self::$arrModuleData) == 0 || !$bitCache) && count(class_carrier::getInstance()->getObjDB()->getTables()) > 0) {
            $strQuery = "SELECT *
                           FROM " . _dbprefix_ . "system_right,
                                " . _dbprefix_ . "system_module,
                                " . _dbprefix_ . "system
                      LEFT JOIN " . _dbprefix_ . "system_date
                             ON system_id = system_date_id
                          WHERE system_id = right_id
                            AND system_id=module_id
                       ORDER BY system_sort ASC, system_comment ASC   ";

            $arrRows = class_carrier::getInstance()->getObjDB()->getPArray($strQuery, array(), null, null, $bitCache);
            class_orm_rowcache::addArrayOfInitRows($arrRows);
            self::$arrModuleData = $arrRows;
        }
        return self::$arrModuleData;
    }


    /**
     * Overwritten in order to flush the cached module-data
     * @return bool
     */
    protected function deleteObjectInternal() {
        self::flushCache();
        return parent::deleteObjectInternal();
    }


    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName() {
        return $this->getStrName();
    }

    /**
     * Returns the icon the be used in lists.
     * Please be aware, that only the filename should be returned, the wrapping by getImageAdmin() is
     * done afterwards.
     *
     * @return string the name of the icon, not yet wrapped by getImageAdmin()
     */
    public function getStrIcon() {
        return "icon_module";
    }

    /**
     * In nearly all cases, the additional info is rendered left to the action-icons.
     *
     * @return string
     */
    public function getStrAdditionalInfo() {
        return "V " . $this->getStrVersion() . " &nbsp;(" . timeToString($this->getIntDate(), true) . ")";
    }

    /**
     * If not empty, the returned string is rendered below the common title.
     *
     * @return string
     */
    public function getStrLongDescription() {
        $objAdminInstance = $this->getAdminInstanceOfConcreteModule();
        if($objAdminInstance != null) {
            $strDescription = $objAdminInstance->getModuleDescription();
        }
        else {
            $strDescription = "";
        }

        return $strDescription;
    }

    /**
     * Loads an array containing all installed modules from database
     *
     * @param bool $intStart
     * @param bool $intEnd
     *
     * @return class_module_system_module[]
     * @static
     */
    public static function getAllModules($intStart = null, $intEnd = null) {
        $arrRows = self::loadModuleData();

        $arrReturn = array();
        $intI = 0;
        foreach($arrRows as $arrOneRow) {
            if($intStart !== null && $intEnd !== null) {
                if($intI >= $intStart && $intI <= $intEnd) {
                    $arrReturn[] = class_module_system_module::getModuleBySystemid($arrOneRow["module_id"]);
                }
            }
            else {
                $arrReturn[] = class_module_system_module::getModuleBySystemid($arrOneRow["module_id"]);
            }

            $intI++;
        }

        return $arrReturn;
    }

    /**
     * Counts the number of modules available
     *
     * @param string $strPrevid
     * @return int
     * @static
     */
    public static function getObjectCount($strPrevid = "") {
        return count(self::loadModuleData());
    }

    /**
     * Tries to look up a module using the given name. If the module
     * is not active / not installed, null is returned instead
     *
     * @param string $strName
     * @param bool $bitIgnoreStatus
     *
     * @return class_module_system_module
     * @static
     */
    public static function getModuleByName($strName, $bitIgnoreStatus = false) {

        //check if the module is already cached
        foreach(self::$arrModules as $objOneModule) {
            if(!$bitIgnoreStatus && $objOneModule->getStrName() == $strName) {
                return $objOneModule;
            }
        }

        $arrModules = self::loadModuleData();
        $arrRow = array();
        foreach($arrModules as $arrOneModule) {
            if($arrOneModule["module_name"] == $strName) {
                $arrRow = $arrOneModule;
            }
        }

        if(count($arrRow) >= 1) {
            //check the status right here - better performance due to cached queries
            if(!$bitIgnoreStatus) {
                if($arrRow["system_status"] == "1") {
                    return self::getModuleBySystemid($arrRow["module_id"]);
                }
                else {
                    return null;
                }

            }
            else {
                return self::getModuleBySystemid($arrRow["module_id"]);
            }
        }
        else {
            return null;
        }
    }


    /**
     * Creates a new instance of a module or returns an already instantiated one.
     * For modules, this is the preferred way of generating instances.
     *
     * @param string $strSystemid
     *
     * @return class_module_system_module
     * @static
     */
    public static function getModuleBySystemid($strSystemid) {
        if(isset(self::$arrModules[$strSystemid])) {
            return self::$arrModules[$strSystemid];
        }

        return new class_module_system_module($strSystemid);

    }

    /**
     * Looks up the id of a module using the passed module-number
     *
     * @param int $strNr
     *
     * @return string $strNr
     * @static
     */
    public static function getModuleIdByNr($strNr) {
        $arrModules = self::loadModuleData();
        $arrRow = array();
        foreach($arrModules as $arrOneModule) {
            if($arrOneModule["module_nr"] == $strNr) {
                $arrRow = $arrOneModule;
            }
        }

        if(count($arrRow) >= 1) {
            return $arrRow["module_id"];
        }
        else {
            return "";
        }
    }

    /**
     * Looks up all modules being active and allowed to appear in the admin-navigation
     * Creates a simple array, NO OBJECTS
     *
     * @param string $strAspectFilter
     *
     * @return array[]
     * @static
     */
    public static function getModulesInNaviAsArray($strAspectFilter = "") {

        $arrParams = array();
        if($strAspectFilter != "") {
            $arrParams[] = "%" . $strAspectFilter . "%";
            $strAspectFilter = " AND (module_aspect = '' OR module_aspect IS NULL OR module_aspect LIKE ? )";
        }

        //Loading all Modules
        $strQuery = "SELECT module_id, module_name
		               FROM " . _dbprefix_ . "system_module,
		                    " . _dbprefix_ . "system
		              WHERE module_navigation = 1
		                AND system_status = 1
		                AND module_id = system_id
                            " . $strAspectFilter . "
		              ORDER BY system_sort ASC, system_comment ASC";
        return class_carrier::getInstance()->getObjDB()->getPArray($strQuery, $arrParams);
    }

    /**
     * Factory method, creates an instance of the admin-module referenced by the current
     * module-object.
     * The object returned is being initialized with a systemid optionally.
     *
     * @param string $strSystemid
     *
     * @return interface_admin|class_admin_controller
     */
    public function getAdminInstanceOfConcreteModule($strSystemid = "") {
        if($this->getStrNameAdmin() != "" && uniStrpos($this->getStrNameAdmin(), ".php") !== false) {
            //creating an instance of the wanted module
            $strClassname = uniStrReplace(".php", "", $this->getStrNameAdmin());
            if(validateSystemid($strSystemid)) {
                $objModule = new $strClassname($strSystemid);
            }
            else {
                $objModule = new $strClassname();
            }
            return $objModule;
        }
        else {
            return null;
        }
    }

    /**
     * Factory method, creates an instance of the portal-module referenced by the current
     * module-object.
     * The object returned is being initialized with the config-array optionally.
     *
     * @param string $arrElementData
     *
     * @return interface_portal
     */
    public function getPortalInstanceOfConcreteModule($arrElementData = null) {
        if($this->getStrNamePortal() != "" && uniStrpos($this->getStrNamePortal(), ".php") !== false) {
            //creating an instance of the wanted module
            $strClassname = uniStrReplace(".php", "", $this->getStrNamePortal());
            if(is_array($arrElementData)) {
                $objModule = new $strClassname($arrElementData);
            }
            else {
                $objModule = new $strClassname(array());
            }
            return $objModule;
        }
        else {
            return null;
        }
    }

    /**
     * Returns the data for a registered module as given in the database
     *
     * @param string $strName
     * @param bool $bitCache
     *
     * @return mixed
     */
    public static function getPlainModuleData($strName, $bitCache = true) {
        $arrModules = self::loadModuleData($bitCache);

        foreach($arrModules as $arrOneModule) {
            if($arrOneModule["module_name"] == $strName) {
                return $arrOneModule;
            }
        }

        return array();
    }

    /**
     * Flushes the internal module-cache, so the rows queried from the database
     * @return void
     */
    public static function flushCache() {
        self::$arrModules = array();
        self::$arrModuleData = array();
    }

    /**
     * @return string
     */
    public function getStrName() {
        return $this->strName;
    }

    /**
     * @return string
     */
    public function getStrNamePortal() {
        return $this->strNamePortal;
    }

    /**
     * @return string
     */
    public function getStrXmlNamePortal() {
        return $this->strXmlNamePortal;
    }

    /**
     * @return string
     */
    public function getStrNameAdmin() {
        return $this->strNameAdmin;
    }

    /**
     * @return string
     */
    public function getStrXmlNameAdmin() {
        return $this->strXmlNameAdmin;
    }

    /**
     * @return string
     */
    public function getStrVersion() {
        return $this->strVersion;
    }

    /**
     * @return int
     */
    public function getIntDate() {
        return $this->intDate;
    }

    /**
     * @return int
     */
    public function getIntNavigation() {
        return $this->intNavigation;
    }

    /**
     * @return int
     */
    public function getIntNr() {
        return $this->intNr;
    }

    /**
     * @param string $strName
     * @return void
     */
    public function setStrName($strName) {
        $this->strName = $strName;
    }

    /**
     * @param string $strPortal
     * @return void
     */
    public function setStrNamePortal($strPortal) {
        $this->strNamePortal = $strPortal;
    }

    /**
     * @param string $strXmlPortal
     * @return void
     */
    public function setStrXmlNamePortal($strXmlPortal) {
        $this->strXmlNamePortal = $strXmlPortal;
    }

    /**
     * @param string $strAdmin
     * @return void
     */
    public function setStrNameAdmin($strAdmin) {
        $this->strNameAdmin = $strAdmin;
    }

    /**
     * @param string $strXmlAdmin
     * @return void
     */
    public function setStrXmlNameAdmin($strXmlAdmin) {
        $this->strXmlNameAdmin = $strXmlAdmin;
    }

    /**
     * @param string $strVersion
     * @return void
     */
    public function setStrVersion($strVersion) {
        $this->strVersion = $strVersion;
    }

    /**
     * @param int $intDate
     * @return void
     */
    public function setIntDate($intDate) {
        $this->intDate = $intDate;
    }

    /**
     * @param int $intNavigation
     * @return void
     */
    public function setIntNavigation($intNavigation) {
        $this->intNavigation = $intNavigation;
    }

    /**
     * @param int $intNr
     * @return void
     */
    public function setIntNr($intNr) {
        $this->intNr = $intNr;
    }

    /**
     * @return string
     */
    public function getStrAspect() {
        return $this->strAspect;
    }

    /**
     * @param string $strAspect
     * @return void
     */
    public function setStrAspect($strAspect) {
        $this->strAspect = $strAspect;
    }

}

//init the caches
class_module_system_module::staticConstruct();