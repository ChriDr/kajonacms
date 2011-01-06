<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                *
********************************************************************************************************/

/**
 * Model for a element. This is the "raw"-element, not the element on a page
 * Elements DON'T have systemids!
 * 
 * @package modul_pages
 */
class class_modul_pages_element extends class_model implements interface_model  {

    private $strName = "";
    private $strClassPortal = "";
    private $strClassAdmin = "";
    private $intRepeat = "";
    private $intCachetime = "";
    private $strVersion = "";

    /**
     * Constructor to create a valid object
     *
     * @param string $strSystemid (use "" on new objects)
     */
    public function __construct($strSystemid = "") {
        $arrModul = array();
        $arrModul["name"] 				= "modul_pages";
		$arrModul["author"] 			= "sidler@mulchprod.de";
		$arrModul["moduleId"] 			= _pages_modul_id_;
		$arrModul["table"]       		= _dbprefix_."element";
		$arrModul["modul"]				= "pages";

		//base class
		parent::__construct($arrModul, "");

        $this->setSystemid($strSystemid);

		//init current object
		if($strSystemid != "")
		    $this->initObject();
    }

    /**
     * @see class_model::getObjectTables();
     * @return array
     */
    protected function getObjectTables() {
        return array();
    }

    /**
     * @see class_model::getObjectDescription();
     * @return string
     */
    protected function getObjectDescription() {
        return "";
    }


    /**
     * Initalises the current object, if a systemid was given
     *
     */
    public function initObject() {
        $strQuery = "SELECT * FROM "._dbprefix_."element WHERE element_id='".$this->objDB->dbsafeString($this->getSystemid())."'";
        $arrRow = $this->objDB->getRow($strQuery);

        $this->setStrName($arrRow["element_name"]);
        $this->setStrClassAdmin($arrRow["element_class_admin"]);
        $this->setStrClassPortal($arrRow["element_class_portal"]);
        $this->setIntCachetime($arrRow["element_cachetime"]);
        $this->setIntRepeat($arrRow["element_repeat"]);
        $this->setStrVersion($arrRow["element_version"]);

        $this->setSystemid($arrRow["element_id"]);
    }

    
    /**
     * Updates the current object to the database
     * @overwrites class_model::updateObjectToDb()
     */
    public function updateObjectToDb($strPrevId = false) {
        if($this->getSystemid() == "") {

            $strElementid = generateSystemid();
            $this->setSystemid($strElementid);

            $strQuery = "INSERT INTO "._dbprefix_."element
					(element_id, element_name, element_class_portal, element_class_admin, element_repeat, element_cachetime, element_version) VALUES
					('".$this->objDB->dbsafeString($this->getSystemid())."', '".$this->objDB->dbsafeString($this->getStrName())."',
					 '".$this->objDB->dbsafeString($this->getStrClassPortal())."', '".$this->objDB->dbsafeString($this->getStrClassAdmin())."',
                      ".(int)$this->getIntRepeat().", ".(int)$this->getIntCachetime().", '".dbsafeString($this->getStrVersion())."')";

            return $this->objDB->_query($strQuery);
        }
        else {
            $strQuery = "UPDATE "._dbprefix_."element SET
                            element_name = '".$this->objDB->dbsafeString($this->getStrName())."',
                            element_class_portal = '".$this->objDB->dbsafeString($this->getStrClassPortal())."',
                            element_class_admin = '".$this->objDB->dbsafeString($this->getStrClassAdmin())."',
                            element_cachetime = '".$this->objDB->dbsafeString($this->getIntCachetime())."',
                            element_repeat = ".$this->objDB->dbsafeString($this->getIntRepeat()).",
                            element_version = '".$this->objDB->dbsafeString($this->getStrVersion())."'
                            WHERE element_id='".$this->objDB->dbsafeString($this->getSystemid())."'";
            return $this->objDB->_query($strQuery);
        }
    }

    /**
	 * Loads all installed Elements
	 *
	 * @return mixed
	 * @static
	 */
	public static function getAllElements() {
		$strQuery = "SELECT element_id FROM "._dbprefix_."element ORDER BY element_name";

		$arrIds = class_carrier::getInstance()->getObjDB()->getArray($strQuery);
		$arrReturn = array();
		foreach($arrIds as $arrOneId)
		    $arrReturn[] = new class_modul_pages_element($arrOneId["element_id"]);

		return $arrReturn;
	}

	/**
	 * Returns the element using the given element-name
	 *
	 * @param string $strName
	 * @return class_modul_pages_element
	 */
	public static function getElement($strName) {
		$strQuery = "SELECT element_id FROM "._dbprefix_."element WHERE element_name='".dbsafeString($strName)."'";
		$arrId = class_carrier::getInstance()->getObjDB()->getRow($strQuery);
		if(isset($arrId["element_id"]))
            return new class_modul_pages_element($arrId["element_id"]);
        else
            return null;
	}

	/**
	 * Deletes one element
	 *
	 * @return bool
	 */
	public function deleteElement() {
	    $strQuery = "DELETE FROM "._dbprefix_."element WHERE element_id='".dbsafeString($this->getSystemid())."'";
	    return $this->objDB->_query($strQuery);
	}

    /**
     * Factory method, creates an instance of the admin-element represented by this page-element.
     * The admin-element won't get initialized by a systemid, so you shouldn't retrieve
     * it for further usings.
     *
     * @return object An instance of the admin-class linked by the current element
     */
    public function getAdminElementInstance() {
        include_once(_adminpath_."/elemente/".$this->getStrClassAdmin());
        //Build the class-name
        $strElementClass = str_replace(".php", "", $this->getStrClassAdmin());
        //and finally create the object
        $objElement = new $strElementClass();
        return $objElement;
    }

// --- GETTERS / SETTERS --------------------------------------------------------------------------------
    
    public function getStrName() {
        return $this->strName;
    }
    public function getStrClassPortal() {
        return $this->strClassPortal;
    }
    public function getStrClassAdmin() {
        return $this->strClassAdmin;
    }
    public function getIntRepeat() {
        return (int)$this->intRepeat;
    }
    public function getIntCachetime() {
        return $this->intCachetime;
    }

    /**
     * Returns a readable representation of the current elements' name.
     * Searches the lang-file for an entry element_NAME_name.
     *
     * @return string
     */
    public function getStrReadableName() {
        $strName = class_carrier::getInstance()->getObjText()->getText("element_".$this->getStrName()."_name", "elemente", "admin");
        if($strName == "!element_".$this->getStrName()."_name!")
            $strName = $this->getStrName();
        return $strName;
    }

   
    public function setStrName($strName) {
        $this->strName = $strName;
    }
    public function setStrClassPortal($strClassPortal) {
        $this->strClassPortal = $strClassPortal;
    }
    public function setStrClassAdmin($strClassAdmin) {
        $this->strClassAdmin = $strClassAdmin;
    }
    public function setIntRepeat($intRepeat) {
        $this->intRepeat = $intRepeat;
    }
    public function setIntCachetime($intCachetime) {
        $this->intCachetime = $intCachetime;
    }

    public function getStrVersion() {
        return $this->strVersion;
    }

    public function setStrVersion($strVersion) {
        $this->strVersion = $strVersion;
    }


}
?>