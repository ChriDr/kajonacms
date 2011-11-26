<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                      *
********************************************************************************************************/

/**
 * Portal-Class of the picture element
 *
 * @package module_pages
 * @author sidler@mulchprod.de
 */
class class_element_image_portal extends class_element_portal implements interface_portal_element  {

	/**
	 * Constructor
	 *
	 * @param mixed $arrElementData
	 */
	public function __construct($objElementData) {
        $arrModule = array();
		$arrModule["name"] 			= "element_image";
		$arrModule["moduleId"] 		= _pages_elemente_modul_id_;
		$arrModule["table"]			= _dbprefix_."element_image";

		parent::__construct($arrModule, $objElementData);
	}


	/**
	 * Returns the ready image-htmlcode
	 *
	 * @return string
	 */
	public function loadData() {
		$strReturn = "";


        $strTemplate = $this->arrElementData["image_template"];
        //fallback
        if($strTemplate == "")
            $strTemplate = "image.tpl";

        //choose template section
        if($this->arrElementData["image_link"] != "")
            $strTemplateID = $this->objTemplate->readTemplate("/element_image/".$strTemplate, "image_link");
        else
            $strTemplateID = $this->objTemplate->readTemplate("/element_image/".$strTemplate, "image");

        $this->arrElementData["image_src"] = urlencode($this->arrElementData["image_image"]);

        //TODO: rename db columns as well and remove this two lines
        $this->arrElementData["image_width"] = $this->arrElementData["image_x"];
        $this->arrElementData["image_height"] = $this->arrElementData["image_y"];

		//Link?
		if($this->arrElementData["image_link"] != "") {
		    //internal page?
		    $objPage = class_modul_pages_page::getPageByName($this->arrElementData["image_link"]);
		    if($objPage->getStrName() != "")
			    $this->arrElementData["link_href"] = getLinkPortalHref($this->arrElementData["image_link"], "");
			else
			    $this->arrElementData["link_href"] = getLinkPortalHref("", $this->arrElementData["image_link"]);
		}


        $strReturn .= $this->fillTemplate($this->arrElementData, $strTemplateID);

		return $strReturn;
	}

}
?>