<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_module_navigation_tree.php 4582 2012-04-11 18:27:04Z sidler $                              *
********************************************************************************************************/

/**
 * Installer of the guestbook module
 *
 * @package module_guestbook
 */
class class_installer_guestbook extends class_installer_base implements interface_installer {

	public function __construct() {
		$this->setArrModuleEntry("version", "3.4.9");
		$this->setArrModuleEntry("name", "guestbook");
		$this->setArrModuleEntry("name_lang", "Module Guestbook");
		$this->setArrModuleEntry("moduleId", _guestbook_module_id_);

		parent::__construct();
	}

	public function getNeededModules() {
	    return array("system", "pages");
	}

    public function getMinSystemVersion() {
	    return "3.4.1";
	}

    public function install() {

        if(count($this->objDB->getTables()) > 0) {
            $arrModul = $this->getModuleData($this->arrModule["name"]);
            if(count($arrModul) > 0)
                return "<strong>Module already installed!!!</strong><br /><br />";
        }

		$strReturn = "";
		//Tabellen anlegen

		//guestbook-------------------------------------------------------------------------------------
		$strReturn .= "Installing table guestbook_book...\n";

		$arrFields = array();
		$arrFields["guestbook_id"] 		  = array("char20", false);
		$arrFields["guestbook_title"] 	  = array("char254", true);
		$arrFields["guestbook_moderated"] = array("int", true);

		if(!$this->objDB->createTable("guestbook_book", $arrFields, array("guestbook_id")))
			$strReturn .= "An error occured! ...\n";

		//guestbook_post----------------------------------------------------------------------------------
		$strReturn .= "Installing table guestbook_post...\n";

		$arrFields = array();
		$arrFields["guestbook_post_id"]   = array("char20", false);
		$arrFields["guestbook_post_name"] = array("char254", true);
		$arrFields["guestbook_post_email"]= array("char254", true);
		$arrFields["guestbook_post_page"] = array("char254", true);
		$arrFields["guestbook_post_text"] = array("text", true);
		$arrFields["guestbook_post_date"] = array("int", true);

		if(!$this->objDB->createTable("guestbook_post", $arrFields, array("guestbook_post_id")))
			$strReturn .= "An error occured! ...\n";


		//register the module
		$this->registerModule("guestbook", _guestbook_module_id_, "class_module_guestbook_portal.php", "class_module_guestbook_admin.php", $this->arrModule["version"] , true);

		$strReturn .= "Registering system-constants...\n";
		$this->registerConstant("_guestbook_search_resultpage_", "guestbook", class_module_system_setting::$int_TYPE_PAGE, _guestbook_module_id_);




        //Table for page-element
        $strReturn .= "Installing guestbook-element table...\n";

        $arrFields = array();
        $arrFields["content_id"]   		= array("char20", false);
        $arrFields["guestbook_id"] 		= array("char20", true);
        $arrFields["guestbook_template"]= array("char254", true);
        $arrFields["guestbook_amount"] 	= array("int", true);

        if(!$this->objDB->createTable("element_guestbook", $arrFields, array("content_id")))
            $strReturn .= "An error occured! ...\n";

        //Register the element
        $strReturn .= "Registering guestbook-element...\n";
        //check, if not already existing
        $objElement = class_module_pages_element::getElement("guestbook");
        if($objElement === null) {
            $objElement = new class_module_pages_element();
            $objElement->setStrName("guestbook");
            $objElement->setStrClassAdmin("class_element_guestbook_admin.php");
            $objElement->setStrClassPortal("class_element_guestbook_portal.php");
            $objElement->setIntCachetime(3600);
            $objElement->setIntRepeat(1);
            $objElement->setStrVersion($this->getVersion());
            $objElement->updateObjectToDb();
            $strReturn .= "Element registered...\n";
        }
        else {
            $strReturn .= "Element already installed!...\n";
        }



		return $strReturn;

	}


	public function update() {
	    $strReturn = "";
        //check installed version and to which version we can update
        $arrModul = $this->getModuleData($this->getArrModule("name"), false);

        $strReturn .= "Version found:\n\t Module: ".$arrModul["module_name"].", Version: ".$arrModul["module_version"]."\n\n";


        $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.4.0") {
            $strReturn .= $this->update_340_341();
        }

        $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.4.1") {
            $strReturn .= $this->update_341_349();
        }

        return $strReturn."\n\n";
	}



    private function update_340_341() {
        $strReturn = "Updating 3.4.0 to 3.4.1...\n";
        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.4.1");
        $strReturn .= "Updating element-versions...\n";
        $this->updateElementVersion("guestbook", "3.4.1");
        return $strReturn;
    }

    private function update_341_349() {
        $strReturn = "Updating 3.4.1 to 3.4.9...\n";


        $strReturn .= "Adding classes for existing records...\n";
        $strReturn .= "Trees\n";
        foreach(class_module_guestbook_guestbook::getGuestbooks() as $objOneBook) {
            $strQuery = "UPDATE "._dbprefix_."system SET system_class = ? where system_id = ?";
            $this->objDB->_pQuery($strQuery, array( get_class($objOneBook), $objOneBook->getSystemid() ) );
        }

        $strReturn .= "Navigation Points\n";
        $arrRows = $this->objDB->getPArray("SELECT guestbook_post_id FROM "._dbprefix_."guestbook_post, "._dbprefix_."system WHERE system_id = guestbook_post_id AND (system_class IS NULL OR system_class = '')", array());
        foreach($arrRows as $arrOneRow) {
            $strQuery = "UPDATE "._dbprefix_."system SET system_class = ? where system_id = ?";
            $this->objDB->_pQuery($strQuery, array( 'class_module_guestbook_post', $arrOneRow["system_id"] ) );
        }


        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.4.9");
        $strReturn .= "Updating element-versions...\n";
        $this->updateElementVersion("guestbook", "3.4.9");
        return $strReturn;
    }

}
