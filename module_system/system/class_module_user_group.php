<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                   *
********************************************************************************************************/

/**
 * Model for a user-group, can be based on any type of usersource
 * Groups are NOT represented in the system-table.
 *
 * @package module_user
 * @author sidler@mulchprod.de
 */
class class_module_user_group extends class_model implements interface_model  {

    private $strSubsystem = "kajona";
    private $strName = "";

    /**
     *
     * @var interface_usersources_group
     */
    private $objSourceGroup;


    /**
     * Constructor to create a valid object
     *
     * @param string $strSystemid (use "" on new objects)
     */
    public function __construct($strSystemid = "") {
        $this->setArrModuleEntry("modul", "user");
        $this->setArrModuleEntry("moduleId", _user_modul_id_);

		parent::__construct($strSystemid);

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
        return "user group ".$this->getStrSystemid();
    }

    /**
     * Initalises the current object, if a systemid was given
     *
     */
    public function initObject() {
        $strQuery = "SELECT * FROM "._dbprefix_."user_group WHERE group_id=?";
        $arrRow = $this->objDB->getPRow($strQuery, array($this->getSystemid()));

        if(count($arrRow) > 0) {
            $this->setStrName($arrRow["group_name"]);
            $this->setStrSubsystem($arrRow["group_subsystem"]);
        }
    }

    /**
     * Updates the current object to the database
     *
     * @return bool
     */
    public function updateObjectToDb($strPrevId = false) {
        //mode-splitting
        if($this->getSystemid() == "") {
            class_logger::getInstance()->addLogRow("saved new group subsystem ".$this->getStrSubsystem()." / ".$this->getStrSystemid(), class_logger::$levelInfo);
            $strGrId = generateSystemid();
            $this->setSystemid($strGrId);
            $strQuery = "INSERT INTO "._dbprefix_."user_group
                          (group_id, group_subsystem, group_name) VALUES
                          (?, ?, ?)";


            $bitReturn = $this->objDB->_pQuery($strQuery, array($strGrId, $this->getStrSubsystem(), $this->getStrName()));

            //create the new instance on the remote-system
            $objSources = new class_modul_user_sourcefactory();
            $objProvider = $objSources->getUsersource($this->getStrSubsystem());
            $objTargetGroup = $objProvider->getNewGroup();
            $objTargetGroup->updateObjectToDb();
            $objTargetGroup->setNewRecordId($this->getSystemid());
            $this->objDB->flushQueryCache();

            return $bitReturn;
        }
        else {
            class_logger::getInstance()->addLogRow("updated group ".$this->getStrName(), class_logger::$levelInfo);
            $strQuery = "UPDATE "._dbprefix_."user_group
                            SET group_subsystem=?,
                                group_name=?
                            WHERE group_id=?";
            return $this->objDB->_pQuery($strQuery, array($this->getStrSubsystem(), $this->getStrName(), $this->getSystemid()));
        }
    }

    /**
	 * Returns all groups from database
	 *
     * @param int $intStart
     * @param int $intEnd
	 * @return array of class_module_user_group
	 * @static
	 */
	public static function getAllGroups($intStart = false, $intEnd = false) {
		$strQuery = "SELECT group_id FROM "._dbprefix_."user_group ORDER BY group_name";

        if($intStart !== false && $intEnd !== false)
            $arrIds = class_carrier::getInstance()->getObjDB()->getPArraySection($strQuery, array(), $intStart, $intEnd);
        else
            $arrIds = class_carrier::getInstance()->getObjDB()->getPArray($strQuery, array());
		$arrReturn = array();
		foreach($arrIds as $arrOneId)
		    $arrReturn[] = new class_module_user_group($arrOneId["group_id"]);

		return $arrReturn;
	}

    /**
     * Fetches the number of groups available
     * @return int
     */
    public static function getNumberOfGroups() {
        $strQuery = "SELECT COUNT(*) FROM "._dbprefix_."user_group";
        $arrRow = class_carrier::getInstance()->getObjDB()->getPRow($strQuery, array());
        return $arrRow["COUNT(*)"];
    }

    /**
     * Returns the number of members of the current group.
     * @return int
     */
    public function getNumberOfMembers() {
        $this->loadSourceObject();
        return $this->objSourceGroup->getNumberOfMembers();
    }

	/**
	 * Deletes the given group
	 *
	 * @return bool
	 */
	public function deleteGroup() {
	    class_logger::getInstance()->addLogRow("deleted group with id ".$this->getSystemid(), class_logger::$levelInfo);

        //Delete related group
        $this->getObjSourceGroup()->deleteGroup();

        $strQuery = "DELETE FROM "._dbprefix_."user_group WHERE group_id=?";
        $bitReturn = $this->objDB->_pQuery($strQuery, array($this->getSystemid()));
        class_core_eventdispatcher::notifyRecordDeletedListeners($this->getSystemid());
        return $bitReturn;
	}

    /**
     * Loads the mapped source-object
     */
    private function loadSourceObject() {
        if($this->objSourceGroup == null) {
            $objUsersources = new class_modul_user_sourcefactory();
            $this->setObjSourceGroup($objUsersources->getSourceGroup($this));
        }
    }

    /**
     * Loads a group by its name, returns null of not found
     * @param string $strName
     * @return class_module_user_group
     *
     */
    public static function getGroupByName($strName) {
		$objFactory = new class_modul_user_sourcefactory();
        return $objFactory->getGroupByName($strName);
	}


    // --- GETTERS / SETTERS --------------------------------------------------------------------------------
    public function getStrSubsystem() {
        return $this->strSubsystem;
    }

    public function setStrSubsystem($strSubsystem) {
        $this->strSubsystem = $strSubsystem;
    }

    /**
     *
     * @return interface_usersources_group
     */
    public function getObjSourceGroup() {
        $this->loadSourceObject();
        return $this->objSourceGroup;
    }

    public function setObjSourceGroup($objSourceGroup) {
        $this->objSourceGroup = $objSourceGroup;
    }

    public function getStrName() {
        return $this->strName;
    }

    public function setStrName($strName) {
        $this->strName = $strName;
    }


}