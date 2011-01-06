<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$						               	*
********************************************************************************************************/

/**
 * Portal-part of the news-element
 *
 * @package modul_news
 */
class class_element_news extends class_element_portal implements interface_portal_element {


	 /**
     * Contructor
     *
     * @param mixed $arrElementData
     */
	public function __construct($objElementData) {
        $arrModule = array();
		$arrModule["name"] 			= "element_news";
		$arrModule["author"] 		= "sidler@mulchprod.de";
		$arrModule["moduleId"] 		= _pages_elemente_modul_id_;
		$arrModule["table"]	    	= _dbprefix_."element_news";

        parent::__construct($arrModule, $objElementData);
	}


    /**
     * Loads the news-class and passes control
     *
     * @return string
     */
	public function loadData() {
		$strReturn = "";
		//Load the data
		$objNewsModule = class_modul_system_module::getModuleByName("news");
		if($objNewsModule != null) {
    		$strClassName = uniStrReplace(".php", "", $objNewsModule->getStrNamePortal());
    		$objNews = new $strClassName($this->arrElementData);
            $strReturn = $objNews->action();
		}
		return $strReturn;
	}



    /**
     * Overwrite this method if you'd like to perform special actions if as soon as content
     * was loaded from the cache.
     * Make sure to return a proper boolean value, otherwise the cached entry may get invalid.
     *
     * @return boolean
     */
    public function onLoadFromCache() {
        //update the news shown, if in details mode
        if($this->getParam("action") == "newsDetail") {
            $objNews = new class_modul_news_news($this->getParam("systemid"));
            $objNews->increaseHits();
        }

        return true;
    }

}
?>