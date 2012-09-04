<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_element_lastmodified.php 3958 2011-06-30 09:18:24Z sidler $                              *
********************************************************************************************************/

/**
 * Loads the last-modified date of the current page and prepares it for output
 *
 * @package element_lastmodified
 * @author sidler@mulchprod.de
 */
class class_element_lastmodified_portal extends class_element_portal implements interface_portal_element {

    /**
     * Constructor
     *
     * @param class_module_pages_pageelement|mixed $objElementData
     */
	public function __construct($objElementData) {
		parent::__construct($objElementData);
	}

    /**
     * Looks up the last modified-date of the current page
     *
     * @return string the prepared html-output
     */
	public function loadData() {
		$strReturn = "";
        //load the current page
        $objPage = class_module_pages_page::getPageByName($this->getPagename());
        $strReturn .= $this->getLang("lastmodified").timeToString($objPage->getIntLmTime());
		return $strReturn;
	}

}