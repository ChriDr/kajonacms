<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_element_search.php 3730 2011-04-06 21:56:16Z jschroeter $	                                    *
********************************************************************************************************/

/**
 * Portal element of the search-module
 *
 * @package module_search
 * @author sidler@mulchprod.de
 */
class class_element_search_portal extends class_element_portal implements interface_portal_element {


    /**
     * @param class_module_pages_pageelement|mixed $objElementData
     */
    public function __construct($objElementData) {
        parent::__construct($objElementData);
        $this->setArrModuleEntry("table", _dbprefix_."element_search");
        $this->setStrCacheAddon(getPost("searchterm").getGet("searchterm"));
	}

    /**
     * Loads the search-class and passes control
     *
     * @return string
     */
	public function loadData() {
		$strReturn = "";
		//Load the data
		$objSearchModule = class_module_system_module::getModuleByName("search");
		if($objSearchModule != null) {
    		$objSearch = $objSearchModule->getPortalInstanceOfConcreteModule($this->arrElementData);
            $strReturn = $objSearch->action();
		}
		return $strReturn;
	}

}