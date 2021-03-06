<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                         *
********************************************************************************************************/


/**
 * Installer handling the installation of the stats module
 *
 * @package module_stats
 * @moduleId _stats_modul_id_
 */
class class_installer_stats extends class_installer_base implements interface_installer_removable {

	public function install() {
        $strReturn = "";

		//Stats table -----------------------------------------------------------------------------------
		$strReturn .= "Installing table stats...\n";

		$arrFields = array();
		$arrFields["stats_id"] 		= array("char20", false);
		$arrFields["stats_ip"] 		= array("char20", true);
		$arrFields["stats_hostname"]= array("char254", true);
		$arrFields["stats_date"] 	= array("int", true);
		$arrFields["stats_page"] 	= array("char254", true);
		$arrFields["stats_language"]= array("char10", true);
		$arrFields["stats_referer"] = array("char254", true);
		$arrFields["stats_browser"] = array("char254", true);
		$arrFields["stats_session"] = array("char100", true);

        if(!$this->objDB->createTable("stats_data", $arrFields, array("stats_id"), array("stats_date", "stats_hostname", "stats_page", "stats_referer", "stats_browser"), false)) {
            $strReturn .= "An error occurred! ...\n";
        }

        $strReturn .= "Installing table ip2country...\n";

        $arrFields = array();
		$arrFields["ip2c_ip"] 		= array("char20", false);
		$arrFields["ip2c_name"] 	= array("char100", false);

		if(!$this->objDB->createTable("stats_ip2country", $arrFields, array("ip2c_ip"), array(), false))
			$strReturn .= "An error occurred! ...\n";


		//register module
		$this->registerModule(
            "stats",
            _stats_modul_id_,
            "class_module_stats_portal.php",
            "class_module_stats_admin.php",
            $this->objMetadata->getStrVersion(),
            true,
            "",
            "class_module_stats_admin_xml.php"
        );

		$strReturn .= "Registering system-constants...\n";
		//Number of rows in the login-log
		$this->registerConstant("_stats_nrofrecords_", "25", class_module_system_setting::$int_TYPE_INT, _stats_modul_id_);
		$this->registerConstant("_stats_duration_online_", "300", class_module_system_setting::$int_TYPE_INT, _stats_modul_id_);
		$this->registerConstant("_stats_exclusionlist_", _webpath_, class_module_system_setting::$int_TYPE_STRING, _stats_modul_id_);


        $strReturn .= "Setting aspect assignments...\n";
        if(class_module_system_aspect::getAspectByName("management") != null) {
            $objModule = class_module_system_module::getModuleByName($this->objMetadata->getStrTitle());
            $objModule->setStrAspect(class_module_system_aspect::getAspectByName("management")->getSystemid());
            $objModule->updateObjectToDb();
        }

		return $strReturn;
	}

    /**
     * Validates whether the current module/element is removable or not.
     * This is the place to trigger special validations and consistency checks going
     * beyond the common metadata-dependencies.
     *
     * @return bool
     */
    public function isRemovable() {
        return true;
    }

    /**
     * Removes the elements / modules handled by the current installer.
     * Use the reference param to add a human readable logging.
     *
     * @param string &$strReturn
     *
     * @return bool
     */
    public function remove(&$strReturn) {

        $strReturn .= "Deleting settings...\n";
        foreach(array("_stats_nrofrecords_", "_stats_duration_online_", "_stats_exclusionlist_") as $strOneSetting) {
            if(class_module_system_setting::getConfigByName($strOneSetting) !== null)
                class_module_system_setting::getConfigByName($strOneSetting)->deleteObject();
        }

        //delete the module-node
        $strReturn .= "Deleting the module-registration...\n";
        $objModule = class_module_system_module::getModuleByName($this->objMetadata->getStrTitle(), true);
        if(!$objModule->deleteObject()) {
            $strReturn .= "Error deleting module, aborting.\n";
            return false;
        }

        //delete the tables
        foreach(array("stats_data", "stats_ip2country") as $strOneTable) {
            $strReturn .= "Dropping table ".$strOneTable."...\n";
            if(!$this->objDB->_pQuery("DROP TABLE ".$this->objDB->encloseTableName(_dbprefix_.$strOneTable)."", array())) {
                $strReturn .= "Error deleting table, aborting.\n";
                return false;
            }
        }

        return true;
    }


    public function update() {
	    $strReturn = "";
        //check installed version and to which version we can update
        $arrModul = class_module_system_module::getPlainModuleData($this->objMetadata->getStrTitle(), false);

        $strReturn .= "Version found:\n\t Module: ".$arrModul["module_name"].", Version: ".$arrModul["module_version"]."\n\n";

        $arrModul = class_module_system_module::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModul["module_version"] == "3.4.2") {
            $strReturn .= $this->update_342_349();
        }

        $arrModul = class_module_system_module::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModul["module_version"] == "3.4.9") {
            $strReturn .= $this->update_349_40();
        }

        $arrModul = class_module_system_module::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModul["module_version"] == "4.0") {
            $strReturn .= $this->update_40_41();
        }

        $arrModul = class_module_system_module::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModul["module_version"] == "4.1") {
            $strReturn .= "Updating 4.1 to 4.2...\n";
            $this->updateModuleVersion("stats", "4.2");
        }

        $arrModul = class_module_system_module::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModul["module_version"] == "4.2") {
            $strReturn .= "Updating 4.2 to 4.3...\n";
            $this->updateModuleVersion("stats", "4.3");
        }

        $arrModul = class_module_system_module::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModul["module_version"] == "4.3") {
            $strReturn .= "Updating 4.3 to 4.4...\n";
            $this->updateModuleVersion("stats", "4.4");
        }

        $arrModul = class_module_system_module::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModul["module_version"] == "4.4") {
            $strReturn .= "Updating 4.4 to 4.5...\n";
            $this->updateModuleVersion("stats", "4.5");
        }

        return $strReturn."\n\n";
	}



    private function update_342_349() {
        $strReturn = "Updating 3.4.2 to 3.4.9...\n";

        $strReturn .= "Setting aspect assignments...\n";
        if(class_module_system_aspect::getAspectByName("management") != null) {
            $objModule = class_module_system_module::getModuleByName($this->objMetadata->getStrTitle());
            $objModule->setStrAspect(class_module_system_aspect::getAspectByName("management")->getSystemid());
            $objModule->updateObjectToDb();
        }

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("stats", "3.4.9");
        return $strReturn;
    }

    private function update_349_40() {
        $strReturn = "Updating 3.4.9 to 4.0...\n";

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("stats", "4.0");
        return $strReturn;
    }

    private function update_40_41() {
        $strReturn = "Updating 4.0 to 4.1...\n";

        $strReturn .= "Updating module-definitions...\n";
        $objModule = class_module_system_module::getModuleByName("stats", true);
        $objModule->setStrNamePortal("");
        $objModule->updateObjectToDb();

        $strReturn .= "Deleting unused files...\n";
        $objFS = new class_filesystem();
        $objFS->fileDelete("/core/module_stats/portal/class_module_stats_portal.php");
        $objFS->folderDelete("/core/module_stats/portal");


        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("stats", "4.1");
        return $strReturn;
    }


}
