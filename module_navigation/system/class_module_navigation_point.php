<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                             *
********************************************************************************************************/

/**
 * Model for a navigation point itself
 *
 * @package module_navigation
 * @author sidler@mulchprod.de
 * @targetTable navigation.navigation_id
 *
 * @module navigation
 * @moduleId _navigation_modul_id_
 */
class class_module_navigation_point extends class_model implements interface_model, interface_admin_listable {

    /**
     * @var string
     * @tableColumn navigation_name
     * @tableColumnDatatype char254
     * @fieldMandatory
     * @fieldType text
     * @fieldLabel commons_name
     *
     * @addSearchIndex
     */
    private $strName = "";

    /**
     * @var string
     * @tableColumn navigation_page_e
     * @tableColumnDatatype char254
     * @fieldType file
     * @fieldLabel navigation_page_e
     *
     * @addSearchIndex
     */
    private $strPageE = "";

    /**
     * @var string
     * @tableColumn navigation_page_i
     * @tableColumnDatatype char254
     * @fieldType page
     * @fieldLabel navigation_page_i
     *
     * @addSearchIndex
     */
    private $strPageI = "";

    /**
     * @var string
     * @tableColumn navigation_folder_i
     * @tableColumnDatatype char20
     * @addSearchIndex
     */
    private $strFolderI = "";

    /**
     * @var string
     * @tableColumn navigation_target
     * @tableColumnDatatype char254
     * @fieldType dropdown
     * @fieldDDValues [_self => navigation_tagetself],[_blank => navigation_tagetblank]
     * @fieldLabel navigation_target
     */
    private $strTarget = "";

    /**
     * @var string
     * @tableColumn navigation_image
     * @tableColumnDatatype char254
     * @fieldType image
     * @fieldLabel commons_image
     */
    private $strImage = "";

    /**
     * Internal field, used for navigation nodes added by other modules
     *
     * @var string
     */
    private $strLinkAction = "";

    /**
     * Internal field, used for navigation nodes added by other modules
     *
     * @var string
     */
    private $strLinkSystemid = "";

    /**
     * Indicates if the node is generated by either a real navigation-tree / a page-tree or
     * by a foreign node injecting new nodes into the tree.
     * @var bool
     */
    private $bitIsForeignNode = false;

    private $bitIsPagealias = false;



    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName() {
        return $this->getStrName();
    }

    /**
     * Returns the icon the be used in lists.
     * Please be aware, that only the filename should be returned, the wrapping by getImageAdmin() is
     * done afterwards.
     *
     * @return string the name of the icon, not yet wrapped by getImageAdmin()
     */
    public function getStrIcon() {
        return "icon_treeLeaf";
    }

    /**
     * In nearly all cases, the additional info is rendered left to the action-icons.
     *
     * @return string
     */
    public function getStrAdditionalInfo() {
        $strNameInternal = $this->getStrPageI();
        $strNameExternal = $this->getStrPageE();
        $strNameFolder = "";
        if(validateSystemid($this->getStrFolderI())) {
            $objFolder = new class_module_pages_folder($this->getStrFolderI());
            $strNameFolder = $objFolder->getStrName();
        }

        return $strNameInternal.$strNameExternal.$strNameFolder;
    }

    /**
     * If not empty, the returned string is rendered below the common title.
     *
     * @return string
     */
    public function getStrLongDescription() {
        return "";
    }


    /**
     * Loads all navigation points one layer under the given systemid
     *
     * @param string $strSystemid
     * @param bool $bitJustActive
     * @param null $intStart
     * @param null $intEnd
     *
     * @internal param $bool
     * @return class_module_navigation_point[]
     * @static
     */
    public static function getNaviLayer($strSystemid, $bitJustActive = false, $intStart = null, $intEnd = null) {
        $strQuery = "SELECT *
                          FROM "._dbprefix_."navigation,
                               "._dbprefix_."system_right,
                               "._dbprefix_."system
                     LEFT JOIN "._dbprefix_."system_date
                            ON system_id = system_date_id
    			         WHERE system_id = navigation_id
    			           AND system_prev_id = ?
    			           AND system_id = right_id
    			             ".($bitJustActive ? " AND system_status = 1 " : "")."
    			      ORDER BY system_sort ASC, system_comment ASC";
        $arrRows = class_carrier::getInstance()->getObjDB()->getPArray($strQuery, array($strSystemid), $intStart, $intEnd);
        $arrReturn = array();
        foreach($arrRows as $arrOneRow) {
            class_orm_rowcache::addSingleInitRow($arrOneRow);
            $objNavigationPoint = class_objectfactory::getInstance()->getObject($arrOneRow["system_id"]);
            $arrReturn[] = $objNavigationPoint;
        }

        return $arrReturn;
    }


    /**
     * Generates a navigation layer for the portal.
     * Either based on the "real" navigation as maintained in module navigation
     * or generated out of the linked pages-folders.
     * If theres a link to a folder, the first page/folder within the folder is
     * linked to the current point.
     *
     * @param string $strSystemid
     *
     * @return class_module_navigation_point
     */
    public static function getDynamicNaviLayer($strSystemid) {

        $arrReturn = array();

        //split modes  - regular navigation or generated out of the pages / folders

        /** @var $objNode class_module_navigation_point|class_module_navigation_tree */
        $objNode = class_objectfactory::getInstance()->getObject($strSystemid);

        //current node is a navigation-node
        if($objNode instanceof class_module_navigation_point || $objNode instanceof class_module_navigation_tree) {

            //check where the point links to - navigation-point or pages-entry
            if($objNode instanceof class_module_navigation_tree && validateSystemid($objNode->getStrFolderId())) {
                $arrReturn = self::loadPageLevelToNavigationNodes($objNode->getStrFolderId());
            }
            else
                $arrReturn = self::getNaviLayer($strSystemid, true);
        }
        //current node belongs to pages
        else if($objNode instanceof class_module_pages_page || $objNode instanceof class_module_pages_folder) {
            //load the page-level below
            $arrReturn = self::loadPageLevelToNavigationNodes($strSystemid);
        }


        return $arrReturn;
    }


    /**
     * Loads all navigation-points linking on the passed page
     *
     * @param string $strPagename
     *
     * @static
     * @return mixed
     */
    public static function loadPagePoint($strPagename) {
        $objDB = class_carrier::getInstance()->getObjDB();
        $arrReturn = array();
        $strQuery = "SELECT *
                       FROM "._dbprefix_."navigation,
                            "._dbprefix_."system_right,
                            "._dbprefix_."system
                  LEFT JOIN "._dbprefix_."system_date
                         ON system_id = system_date_id
    			      WHERE system_id = navigation_id
                        AND navigation_page_i = ?
                        AND system_id = right_id
        	            AND system_status = 1";
        $arrRows = $objDB->getPArray($strQuery, array($strPagename));
        class_orm_rowcache::addArrayOfInitRows($arrRows);
        foreach($arrRows as $arrOneId)
            $arrReturn[] = class_objectfactory::getInstance()->getObject($arrOneId["system_id"]);

        return $arrReturn;
    }


    /**
     * Loads the level of pages and/or folders stored under a single systemid.
     * Transforms a page- or a folder-node into a navigation-node.
     * This node is used for portal-actions only, so there's no way to edit the node.
     *
     * @param string $strSourceId
     *
     * @return class_module_navigation_point[]|array
     * @since 3.4
     */
    private static function loadPageLevelToNavigationNodes($strSourceId) {

        $arrPages = class_module_pages_page::getObjectList($strSourceId);
        $arrReturn = array();

        //transform the sublevel
        foreach($arrPages as $objOneEntry) {
            //validate status
            if($objOneEntry->getIntRecordStatus() == 0 || !$objOneEntry->rightView())
                continue;

            $objLanguage = new class_module_languages_language();

            if($objOneEntry instanceof class_module_pages_page) {

                //validate if the page to be linked has a template assigned and at least a single element created
                if($objOneEntry->getIntType() == class_module_pages_page::$INT_TYPE_ALIAS
                    || ($objOneEntry->getStrTemplate() != "" && count(class_module_pages_pageelement::getPlainElementsOnPage($objOneEntry->getSystemid(), true, $objLanguage->getStrPortalLanguage())) > 0)
                ) {

                    $objPoint = new class_module_navigation_point();
                    $objPoint->setStrName($objOneEntry->getStrBrowsername() != "" ? $objOneEntry->getStrBrowsername() : $objOneEntry->getStrName());
                    $objPoint->setIntRecordStatus(1, false);

                    //if in alias mode, then check what type of target is requested
                    if($objOneEntry->getIntType() == class_module_pages_page::$INT_TYPE_ALIAS) {
                        $strAlias = uniStrtolower($objOneEntry->getStrAlias());
                        if(uniStrpos($strAlias, "http") !== false) {
                            $objPoint->setStrPageE($objOneEntry->getStrAlias());
                        }
                        else {
                            $objPoint->setStrPageI($objOneEntry->getStrAlias());
                        }

                        $objPoint->setStrTarget($objOneEntry->getStrTarget());
                    }
                    else {
                        $objPoint->setStrPageI($objOneEntry->getStrName());
                    }

                    $objPoint->setSystemid($objOneEntry->getSystemid());

                    $arrReturn[] = $objPoint;
                }
            }
        }

        //merge with elements on the page - if given
        /** @var $objInstance class_module_pages_page */
        $objInstance = class_objectfactory::getInstance()->getObject($strSourceId);
        if($objInstance instanceof class_module_pages_page) {

            if($objInstance->getIntType() != class_module_pages_page::$INT_TYPE_ALIAS)
                $arrReturn = array_merge($arrReturn, self::getAdditionalEntriesForPage($objInstance));
            //else
            //    $arrReturn = array_merge($arrReturn, self::getAdditionalEntriesForPage(class_module_pages_page::getPageByName($objInstance->getStrAlias())));

        }

        return $arrReturn;
    }


    /**
     * Triggers all subelements in order to fetch the additional navigation
     * entries.
     *
     * @param class_module_pages_page $objPage
     *
     * @see class_element_portal::getNavigationEntries()
     * @return class_module_navigation_point[]|array
     * @since 4.0
     */
    private static function getAdditionalEntriesForPage(class_module_pages_page $objPage) {
        $arrReturn = array();
        $objLanguage = new class_module_languages_language();
        $arrPlainElements = class_module_pages_pageelement::getPlainElementsOnPage($objPage->getSystemid(), true, $objLanguage->getStrPortalLanguage());

        $strOldPageName = $objPage->getParam("page");

        foreach($arrPlainElements as $arrOneElementOnPage) {
            //Build the class-name for the object
            $strClassname = uniSubstr($arrOneElementOnPage["element_class_portal"], 0, -4);


            if($strClassname::providesNavigationEntries()) {

                /** @var  class_element_portal $objElement */
                $objElement = new $strClassname(new class_module_pages_pageelement($arrOneElementOnPage["system_id"]));
                $objElement->setParam("page", $objPage->getStrName());

                $arrNavigationPoints = $objElement->getNavigationEntries();
                if($arrNavigationPoints !== false) {
                    $arrReturn = array_merge($arrReturn, $arrNavigationPoints);
                }
            }

        }

        $objPage->setParam("page", $strOldPageName);

        return $arrReturn;
    }


    /**
     * @return string
     */
    public function getStrName() {
        return $this->strName;
    }

    /**
     * @return string
     */
    public function getStrPageI() {
        return uniStrtolower($this->strPageI);
    }

    /**
     * @return string
     */
    public function getStrPageE() {
        return $this->strPageE;
    }

    /**
     * @return string
     */
    public function getStrTarget() {
        return $this->strTarget != "" ? $this->strTarget : "_self";
    }

    /**
     * @return string
     */
    public function getStrImage() {
        return $this->strImage;
    }

    /**
     * @param string $strName
     * @return void
     */
    public function setStrName($strName) {
        $this->strName = $strName;
    }

    /**
     * @param string $strPageE
     * @return void
     */
    public function setStrPageE($strPageE) {
        $this->strPageE = $strPageE;
    }

    /**
     * @param string $strPageI
     * @return void
     */
    public function setStrPageI($strPageI) {
        $this->strPageI = $strPageI;
    }

    /**
     * @param string $strTarget
     * @return void
     */
    public function setStrTarget($strTarget) {
        $this->strTarget = $strTarget;
    }

    /**
     * @param string $strImage
     * @return void
     */
    public function setStrImage($strImage) {
        $strImage = uniStrReplace(_webpath_, "", $strImage);
        $this->strImage = $strImage;
    }

    /**
     * @return string
     * @return void
     */
    public function getStrFolderI() {
        return $this->strFolderI;
    }

    /**
     * @param string $strFolderI
     * @return void
     */
    public function setStrFolderI($strFolderI) {
        $this->strFolderI = $strFolderI;
    }

    /**
     * @param string $strLinkAction
     * @return void
     */
    public function setStrLinkAction($strLinkAction) {
        $this->strLinkAction = $strLinkAction;
    }

    /**
     * @return string
     */
    public function getStrLinkAction() {
        return $this->strLinkAction;
    }

    /**
     * @param string $strLinkSystemid
     * @return void
     */
    public function setStrLinkSystemid($strLinkSystemid) {
        $this->strLinkSystemid = $strLinkSystemid;
    }

    /**
     * @return string
     */
    public function getStrLinkSystemid() {
        return $this->strLinkSystemid;
    }

    /**
     * @param boolean $bitIsForeignNode
     * @return void
     */
    public function setBitIsForeignNode($bitIsForeignNode) {
        $this->bitIsForeignNode = $bitIsForeignNode;
    }

    /**
     * @return boolean
     */
    public function getBitIsForeignNode() {
        return $this->bitIsForeignNode;
    }

    /**
     * @param string $bitIsPagenode
     * @return void
     */
    public function setBitIsPagealias($bitIsPagenode) {
        $this->bitIsPagealias = $bitIsPagenode;
    }

    /**
     * @return bool
     */
    public function getBitIsPagealias() {
        return $this->bitIsPagealias;
    }



}
