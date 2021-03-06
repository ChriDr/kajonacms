<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                         *
********************************************************************************************************/

/**
 * Portal-class of the navigation element, loads the navigation-portal class
 *
 * @package module_navigation
 * @author sidler@mulchprod.de
 *
 * @targetTable element_navigation.content_id
 */
class class_element_navigation_portal extends class_element_portal implements interface_portal_element {

    /**
     * Loads the navigation-class and passes control
     *
     * @return string
     */
    public function loadData() {
        $strReturn = "";

        $objNaviModule = class_module_system_module::getModuleByName("navigation");
        if($objNaviModule != null) {
            $objNavigation = $objNaviModule->getPortalInstanceOfConcreteModule($this->arrElementData);
            $strReturn = $objNavigation->action();
        }

        return $strReturn;
    }

    /**
     * no anchor here, plz
     *
     * @return string
     */
    protected function getAnchorTag() {
        return "";
    }

}
