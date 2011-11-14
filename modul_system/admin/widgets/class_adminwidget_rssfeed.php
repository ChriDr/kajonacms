<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_adminwidget_rssfeed.php 3530 2011-01-06 12:30:26Z sidler $	                            *
********************************************************************************************************/


/**
 * @package modul_dashboard
 *
 */
class class_adminwidget_rssfeed extends class_adminwidget implements interface_adminwidget {
    
    /**
     * Basic constructor, registers the fields to be persisted and loaded
     *
     */
    public function __construct() {
        parent::__construct();
        //register the fields to be persisted and loaded
        $this->setPersistenceKeys(array("feedurl", "posts"));
    }
    
    /**
     * Allows the widget to add additional fields to the edit-/create form. 
     * Use the toolkit class as usual.
     *
     * @return string
     */
    public function getEditForm() {
        $strReturn = "";
        $strReturn .= $this->objToolkit->formInputText("feedurl", $this->getText("rssfeed_feedurl"), $this->getFieldValue("feedurl"));
        $strReturn .= $this->objToolkit->formInputText("posts", $this->getText("rssfeed_posts"), $this->getFieldValue("posts"));
        return $strReturn;
    }
    
    /**
     * This method is called, when the widget should generate it's content.
     * Return the complete content using the methods provided by the base class.
     * Do NOT use the toolkit right here! 
     *
     * @return string
     */
    public function getWidgetOutput() {
        $strReturn = "";
        
        //request the xml...
        $strContent = @file_get_contents($this->getFieldValue("feedurl"));
        
        try {
            $objRemoteloader = new class_remoteloader();
            $objRemoteloader->setStrHost(str_ireplace("http://", "", $this->getFieldValue("feedurl")));
            $objRemoteloader->setIntPort(0);
            $strContent = $objRemoteloader->getRemoteContent();
        }
        catch (class_exception $objExeption) {
            $strContent = "";
        }
        
        if($strContent != "") {
        	$objXmlparser = new class_xml_parser();
            $objXmlparser->loadString($strContent);
            
            $arrFeed = $objXmlparser->xmlToArray();
            
            if(count($arrFeed) >= 1) {
                
                //rss feed
                if(isset($arrFeed["rss"])) {
                    $intCounter = 0;
                    foreach ($arrFeed["rss"][0]["channel"][0]["item"] as $arrOneItem) {
                    	
                        $strTitle = (isset($arrOneItem["title"][0]["value"]) ? $arrOneItem["title"][0]["value"] : "");
                        $strLink = (isset($arrOneItem["link"][0]["value"]) ? $arrOneItem["link"][0]["value"] : "");
                        
                        $strReturn .= $this->widgetText("<a href=\"".$strLink."\" target=\"_blank\">".$strTitle."</a>");
                        $strReturn .= $this->widgetSeparator();
                                                
                        if(++$intCounter >= $this->getFieldValue("posts"))
                           break;
                    
                    }
                }
                
                //atom feed
                if(isset($arrFeed["feed"]) && isset($arrFeed["feed"][0]["entry"])) {
                    $intCounter = 0;
                    foreach ($arrFeed["feed"][0]["entry"] as $arrOneItem) {
                        
                        $strTitle = (isset($arrOneItem["title"][0]["value"]) ? $arrOneItem["title"][0]["value"] : "");
                        $strLink = (isset($arrOneItem["link"][0]["attributes"]["href"]) ? $arrOneItem["link"][0]["attributes"]["href"] : "");
            
                        $strReturn .= $this->widgetText("<a href=\"".$strLink."\" target=\"_blank\">".$strTitle."</a>");
                        $strReturn .= $this->widgetSeparator();
                        
                        if(++$intCounter >= $this->getFieldValue("posts"))
                           break;
                    
                    }
                }
            }
            else {
                $strContent = $this->getText("rssfeed_errorparsing");
            }                       
                                
        }
        else
            $strReturn .= $this->getText("rssfeed_errorloading");
            
            
        
        return $strReturn;
    }
    
    
    /**
     * Return a short (!) name of the widget.
     *
     * @return 
     */
    public function getWidgetName() {
        return $this->getText("rssfeed_name");
    }
    
}


?>