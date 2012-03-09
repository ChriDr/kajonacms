<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id: installer_sc_downloads.php 4071 2011-08-17 10:32:17Z sidler $                                  *
********************************************************************************************************/

/**
 * Installer of the downloads samplecontent
 *
 * @package modul_downloads
 */
class class_installer_sc_downloads implements interface_sc_installer  {

    /**
     * @var class_db
     */
    private $objDB;
    private $strContentLanguage;

    /**
     * Does the hard work: installs the module and registers needed constants
     *
     * @return string
     */
    public function install() {
        $strReturn = "";

        //fetch navifolder-id
        $strNaviFolderId = "";
        $arrFolder = class_module_pages_folder::getFolderList();
        foreach($arrFolder as $objOneFolder)
            if($objOneFolder->getStrName() == "mainnavigation")
                $strNaviFolderId = $objOneFolder->getSystemid();



        $strReturn .= "Creating new downloads...\n";
        $objDownloads = new class_module_mediamanager_repo();
        $objDownloads->setStrTitle("Sample downloads");
        $objDownloads->setStrPath("/files/downloads");
        $objDownloads->updateObjectToDb();
        $strDownloadsID = $objDownloads->getSystemid();
        $objDownloads->syncRepo();


        $strReturn .= "Creating new downloads page...\n";

        $objPage = new class_module_pages_page();
        $objPage->setStrName("downloads");
        $objPage->setStrBrowsername("Downloads");
        $objPage->setStrTemplate("kajona_demo.tpl");
        $objPage->updateObjectToDb($strNaviFolderId);

        $strDownloadsPageId = $objPage->getSystemid();
        $strReturn .= "ID of new page: ".$strDownloadsPageId."\n";
        $strReturn .= "Adding pagelement to new page\n";

        $objPagelement = new class_module_pages_pageelement();
        if(class_module_pages_element::getElement("downloads") != null) {
            $objPagelement->setStrPlaceholder("dl1_downloads");
            $objPagelement->setStrName("dl1");
            $objPagelement->setStrElement("downloads");
            $objPagelement->updateObjectToDb($strDownloadsPageId);
            $strElementId = $objPagelement->getSystemid();
            $strQuery = "UPDATE "._dbprefix_."element_downloads
                            SET download_id = ?,
                                download_template = ?
                            WHERE content_id = ? ";
            if($this->objDB->_pQuery($strQuery, array($strDownloadsID, "downloads.tpl", $strElementId)))
                $strReturn .= "downloads element created.\n";
            else
                $strReturn .= "Error creating downloads element.\n";
        }


        $strReturn .= "Adding headline-element to new page\n";
        if(class_module_pages_element::getElement("row") != null) {
            $objPagelement = new class_module_pages_pageelement();
            $objPagelement->setStrPlaceholder("headline_row");
            $objPagelement->setStrName("headline");
            $objPagelement->setStrElement("row");
            $objPagelement->updateObjectToDb($strDownloadsPageId);
            $strElementId = $objPagelement->getSystemid();
            $strQuery = "UPDATE "._dbprefix_."element_paragraph
                                SET paragraph_title = ?
                                WHERE content_id = ?";
            if($this->objDB->_pQuery($strQuery, array("Downloads", $strElementId)))
                $strReturn .= "Headline element created.\n";
            else
                $strReturn .= "Error creating headline element.\n";
        }


        return $strReturn;
    }

    public function setObjDb($objDb) {
        $this->objDB = $objDb;
    }

    public function setStrContentlanguage($strContentlanguage) {
        $this->strContentLanguage = $strContentlanguage;
    }

    public function getCorrespondingModule() {
        return "mediamanager";
    }

}