<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2008 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
* 																										*
* 	installer_element_tellafriend.php																    *
* 	Installer of the tellafriend element      															*
*																										*
*-------------------------------------------------------------------------------------------------------*
*	$Id$                          *
********************************************************************************************************/

require_once(_systempath_."/class_installer_base.php");
require_once(_systempath_."/interface_installer.php");
include_once(_systempath_."/class_modul_pages_element.php");

/**
 * Installer to install a tellafriend-element to use in the portal
 *
 * @package modul_pages
 */
class class_installer_element_tellafriend extends class_installer_base implements interface_installer {

    /**
     * Constructor
     *
     */
	public function __construct() {
		$arrModule["version"] 		= "3.1.0";
		$arrModule["name"] 			= "element_tellafriend";
		$arrModule["name_lang"] 	= "Element tellafriend";
		$arrModule["nummer2"] 		= _pages_inhalte_modul_id_;
		parent::__construct($arrModule);
	}

	public function getNeededModules() {
	    return array("system", "pages");
	}
	
    public function getMinSystemVersion() {
	    return "3.0.9";
	}

	public function hasPostInstalls() {
	    //needed: pages
	    try {
		    $objModule = class_modul_system_module::getModuleByName("pages");
		}
		catch (class_exception $objE) {
		    return false;
		}

	    //check, if not already existing
		try {
		    $objElement = class_modul_pages_element::getElement("tellafriend");
		}
		catch (class_exception $objEx)  {
		}
        if($objElement == null)
            return true;

        return false;
	}

	public function install() {
    }

    public function postInstall() {
		$strReturn = "";

		$strReturn = "";

		//Table for page-element
		$strReturn .= "Installing tellafriend-element table...\n";
		
		$arrFields = array();
		$arrFields["content_id"] 			= array("char20", false);
		$arrFields["tellafriend_template"] 	= array("char254", true);
		$arrFields["tellafriend_error"] 	= array("char254", true);
		$arrFields["tellafriend_success"] 	= array("char254", true);
		
		if(!$this->objDB->createTable("element_tellafriend", $arrFields, array("content_id")))
			$strReturn .= "An error occured! ...\n";

		//Register the element
		$strReturn .= "Registering tellafriend-element...\n";
		//check, if not already existing
		try {
		    $objElement = class_modul_pages_element::getElement("tellafriend");
		}
		catch (class_exception $objEx)  {
		}
		if($objElement == null) {
		    $objElement = new class_modul_pages_element();
		    $objElement->setStrName("tellafriend");
		    $objElement->setStrClassAdmin("class_element_tellafriend.php");
		    $objElement->setStrClassPortal("class_element_tellafriend.php");
		    $objElement->setIntCachetime(-1);
		    $objElement->setIntRepeat(0);
			$objElement->saveObjectToDb();
			$strReturn .= "Element registered...\n";
		}
		else {
			$strReturn .= "Element already installed!...\n";
		}
		return $strReturn;
	}


	public function update() {

	}
}
?>