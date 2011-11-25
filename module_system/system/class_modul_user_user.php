<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                   *
********************************************************************************************************/

/**
 * Model for a user
 * Note: Users do not use the classical system-id relation, so no entry in the system-table
 *
 * @package modul_user
 * @author sidler@mulchprod.de
 */
class class_modul_user_user extends class_model implements interface_model  {
    
    private $strSubsystem = "kajona";
    
    /**
     *
     * @var interface_usersources_user
     */
    private $objSourceUser;
    
    private $strUsername = "";
    
    private $intLogins = 0;
    private $intLastlogin = 0;
    private $intActive = 0;
    private $intAdmin = 0;
    private $intPortal = 0;
    private $strAdminskin = "";
    private $strAdminlanguage = "";
    private $strAuthcode = "";


    /**
     * Constructor to create a valid object
     *
     * @param string $strSystemid (use "" on new objects)
     * @param bool $bitLoadPassword
     */
    public function __construct($strSystemid = "", $bitLoadPassword = false) {
        $arrModul = array();
        $arrModul["name"] 				= "modul_user";
		$arrModul["moduleId"] 			= _user_modul_id_;
		$arrModul["modul"]				= "user";

		//base class
		parent::__construct($arrModul, $strSystemid);

		//init current object
		if($strSystemid != "")
		    $this->initObject($bitLoadPassword);
    }

    /**
     * Initialises the current object, if a systemid was given
     *
     * @param bool $bitPassword Should the password be loaded, too?
     */
    public function initObject($bitPassword = false) {
        $strQuery = "SELECT * FROM "._dbprefix_."user WHERE user_id=?";
        $arrRow = $this->objDB->getPRow($strQuery, array($this->getSystemid()));

        if(count($arrRow) > 0) {
            $this->setStrUsername($arrRow["user_username"]);
            $this->setStrSubsystem($arrRow["user_subsystem"]);
            $this->setIntLogins($arrRow["user_logins"]);
            $this->setIntLastLogin($arrRow["user_lastlogin"]);
            $this->setIntActive($arrRow["user_active"]);
            $this->setIntAdmin($arrRow["user_admin"]);
            $this->setIntPortal($arrRow["user_portal"]);
            $this->setStrAdminskin($arrRow["user_admin_skin"]);
            $this->setStrAdminlanguage($arrRow["user_admin_language"]);
            $this->setSystemid($arrRow["user_id"]);
            $this->setStrAuthcode($arrRow["user_authcode"]);
        }
    }

    /**
     * Updates the current object to the database
     * <b>ATTENTION</b> If you don't want to update the password, set it to "" before!
     *
     * @return bool
     */
    public function updateObjectToDb($strPrevid = false) {

        if($this->getSystemid() == "") {
            $strUserid = generateSystemid();
            $this->setSystemid($strUserid);
            $strQuery = "INSERT INTO "._dbprefix_."user (
                        user_id, user_active,
                        user_admin, user_portal,
                        user_admin_skin, user_admin_language,
                        user_logins, user_lastlogin, user_authcode, user_subsystem, user_username

                        ) VALUES (?,?,?,?,?,?,?,?,?,?,?)";

            class_logger::getInstance()->addLogRow("new user for subsystem ".$this->getStrSubsystem()." / ".$this->getStrUsername(), class_logger::$levelInfo);

            $bitReturn = $this->objDB->_pQuery($strQuery, array(
                $strUserid,
                (int)$this->getIntActive(),
                (int)$this->getIntAdmin(),
                (int)$this->getIntPortal(),
                $this->getStrAdminskin(),
                $this->getStrAdminlanguage(),
                0,
                0,
                $this->getStrAuthcode(),
                $this->getStrSubsystem(),
                $this->getStrUsername()
            ));
            
            //create the new instance on the remote-system
            $objSources = new class_modul_user_sourcefactory();
            $objProvider = $objSources->getUsersource($this->getStrSubsystem());
            $objTargetUser = $objProvider->getNewUser();
            $objTargetUser->updateObjectToDb();
            $objTargetUser->setNewRecordId($this->getSystemid());
            $this->objDB->flushQueryCache();
            
            //intial dashboard widgets
            $objDashboard = new class_modul_dashboard_widget();
            $objDashboard->createInitialWidgetsForUser($this->getSystemid());
            
            return $bitReturn;
        }
        else {

            $strQuery = "UPDATE "._dbprefix_."user SET
                    user_active=?, user_admin=?, user_portal=?, user_admin_skin=?, user_admin_language=?, user_logins = ?, user_lastlogin = ?, user_authcode = ?, user_subsystem = ?,
                    user_username =?
                    WHERE user_id = ?";

            $arrParams = array(
                    (int)$this->getIntActive(),
                    (int)$this->getIntAdmin(), (int)$this->getIntPortal(), $this->getStrAdminskin(), $this->getStrAdminlanguage(),
                    (int)$this->getIntLogins(), (int)$this->getIntLastLogin(), $this->getStrAuthcode(),
                    $this->getStrSubsystem(), $this->getStrUsername(),
                    $this->getSystemid()
               );
                   

            class_logger::getInstance()->addLogRow("updated userfor subsystem ".$this->getStrSubsystem()." / ".$this->getStrUsername(), class_logger::$levelInfo);
            return $this->objDB->_pQuery($strQuery, $arrParams);
        }
    }
   

    /**
     * Fetches all available users an returns them in an array
     *
     * @param string $strUsernameFilter
     * @param int $intStart
     * @param int $intEnd
     * @return mixed
     */
    public static function getAllUsers($strUsernameFilter = "", $intStart = false, $intEnd = false) {
        $strQuery = "SELECT user_id FROM "._dbprefix_."user WHERE user_username LIKE ? ORDER BY user_username, user_subsystem ASC";
        
            

        if($intStart !== false && $intEnd !== false)
            $arrIds = class_carrier::getInstance()->getObjDB()->getPArraySection($strQuery, array($strUsernameFilter."%"), $intStart, $intEnd);
        else
            $arrIds = class_carrier::getInstance()->getObjDB()->getPArray($strQuery, array($strUsernameFilter."%"));
        

		$arrReturn = array();
		foreach($arrIds as $arrOneId)
		    $arrReturn[] = new class_modul_user_user($arrOneId["user_id"]);

		return $arrReturn;
    }

    /**
     * Counts the number of users created
     * 
     * @param string $strUsernameFilter
     * @return int
     */
    public static function getNumberOfUsers($strUsernameFilter = "") {
        $strQuery = "SELECT COUNT(*) FROM "._dbprefix_."user WHERE user_username LIKE ? ";
        $arrRow = class_carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strUsernameFilter."%"));
		return $arrRow["COUNT(*)"];
    }
    
    /** 
     * Fetches all available active users with the given username an returns them in an array
     *
     * @param string $strName
     * @param boolean $bitOnlyActive
     * @return mixed
     * 
     */
    public static function getAllUsersByName($strName, $bitOnlyActive = true) {
        $objSubsystem = new class_modul_user_sourcefactory();
        $objUser = $objSubsystem->getUserByUsername($strName);
        if($objUser != null)
            return array($objUser);
        else 
            return null;
    }


    /**
     * Deletes a user from the systems
     *
     * @param string $strUserid
     * @return bool
     */
    public function deleteUser() {
        class_logger::getInstance()->addLogRow("deleted user with id ".$this->getSystemid(), class_logger::$levelInfo);
        $strQuery = "DELETE FROM "._dbprefix_."user WHERE user_id=?";
        //call other models that may be interested
        $this->getObjSourceUser()->deleteUser();
        $bitReturn = $this->objDB->_pQuery($strQuery, array($this->getSystemid()));
        $this->additionalCallsOnDeletion($this->getSystemid());
        
        return $bitReturn;
    }

    
    /**
     * Returns an array of group-ids the current user is assigned to
     * @return array string 
     */
    public function getArrGroupIds() {
        $this->loadSourceObject();
        return $this->objSourceUser->getGroupIdsForUser();
    }
    
    public function getStrEmail() {
        $this->loadSourceObject();
        return $this->objSourceUser->getStrEmail();
    }
    
    public function getStrForename() {
        $this->loadSourceObject();
        return $this->objSourceUser->getStrForename();
    }
    
    public function getStrName() {
        $this->loadSourceObject();
        return $this->objSourceUser->getStrName();
    }
    
    private function loadSourceObject() {
        if($this->objSourceUser == null) {
            $objUsersources = new class_modul_user_sourcefactory();
            $this->setObjSourceUser($objUsersources->getSourceUser($this));
        }
    }
    
    
    

// --- GETTERS / SETTERS --------------------------------------------------------------------------------

    
    public function getLongDate() {
        return $this->longDate;
    }
    public function getIntLogins() {
        return $this->intLogins;
    }
    public function getIntLastLogin() {
        return $this->intLastlogin;
    }
    public function getIntActive() {
        return $this->intActive;
    }
    public function getIntAdmin() {
        return $this->intAdmin;
    }
    public function getIntPortal() {
        return $this->intPortal;
    }
    public function getStrAdminskin() {
        return $this->strAdminskin;
    }
    public function getStrAdminlanguage() {
        return $this->strAdminlanguage;
    }

    public function getStrUsername() {
        return $this->strUsername;
    }

    public function setStrUsername($strUsername) {
        $this->strUsername = $strUsername;
    }

    public function setIntLogins($intLogins) {
        if($intLogins == "")
            $intLogins = 0;
        $this->intLogins = $intLogins;
    }
    public function setIntLastLogin($intLastLogin) {
        if($intLastLogin == "")
            $intLastLogin = 0;
        $this->intLastlogin = $intLastLogin;
    }
    public function setIntActive($intActive) {
        if($intActive == "")
            $intActive = 0;
        $this->intActive = $intActive;
    }
    public function setIntAdmin($intAdmin) {
        if($intAdmin == "")
            $intAdmin = 0;
        $this->intAdmin = $intAdmin;
    }
    public function setIntPortal($intPortal) {
        if($intPortal == "")
            $intPortal = 0;
        $this->intPortal = $intPortal;
    }
    public function setStrAdminskin($strAdminskin) {
        $this->strAdminskin = $strAdminskin;
    }
    public function setStrAdminlanguage($strAdminlanguage) {
        $this->strAdminlanguage = $strAdminlanguage;
    }

    public function getStrAuthcode() {
        return $this->strAuthcode;
    }

    public function setStrAuthcode($strAuthcode) {
        $this->strAuthcode = $strAuthcode;
    }

    public function getStrSubsystem() {
        return $this->strSubsystem;
    }

    public function setStrSubsystem($strSubsystem) {
        $this->strSubsystem = $strSubsystem;
    }

    /**
     *
     * @return interface_usersources_user
     */
    public function getObjSourceUser() {
        $this->loadSourceObject();
        return $this->objSourceUser;
    }

    public function setObjSourceUser($objSourceUser) {
        $this->objSourceUser = $objSourceUser;
    }



    

}
?>