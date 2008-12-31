<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2009 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                *
********************************************************************************************************/


include_once(_systempath_."/interface_sc_installer.php");

/**
 * Installer of the pages samplecontent
 *
 * @package modul_pages
 */
class class_installer_sc_01pages implements interface_sc_installer  {

    private $objDB;
    private $strContentLanguage;
    private $strIndexID;
    private $strMasterID;

    /**
     * Does the hard work: installs the module and registers needed constants
     *
     */
    public function install() {
    	
        $strReturn = "";

        $strReturn .= "Creating index-site...\n";
        include_once(_systempath_."/class_modul_pages_page.php");
        $objPage = new class_modul_pages_page();
        $objPage->setStrName("index");

        if($this->strContentLanguage == "de") {
            $objPage->setStrBrowsername("Willkommen");
        }
        else {
            $objPage->setStrBrowsername("Welcome");
        }

        $objPage->setStrTemplate("kajona_demo.tpl");
        //set language to "" - being update by the languages sc installer later
        $objPage->setStrLanguage("");
        $objPage->saveObjectToDb();
        $this->strIndexID = $objPage->getSystemid();
        $strReturn .= "ID of new page: ".$this->strIndexID."\n";
        include_once(_systempath_."/class_modul_pages_pageelement.php");
        $strReturn .= "Adding headline-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("headline_row");
        $objPagelement->setStrName("headline");
        $objPagelement->setStrElement("row");
        $objPagelement->saveObjectToDb($this->strIndexID, "headline_row", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();

        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Willkommen'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                                SET absatz_titel = 'Welcome'
                                WHERE content_id = '".dbsafeString($strElementId)."'";
        }



        if($this->objDB->_query($strQuery))
            $strReturn .= "Headline element created.\n";
        else
            $strReturn .= "Error creating headline element.\n";

        $strReturn .= "Adding paragraph-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("text_paragraph");
        $objPagelement->setStrName("text");
        $objPagelement->setStrElement("paragraph");
        $objPagelement->saveObjectToDb($this->strIndexID, "text_paragraph", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();

        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Herzlichen Glückwunsch!',
                                absatz_inhalt ='Diese Installation von Kajona war erfolgreich. Wir wünschen viel Spaß mit Kajona V3.<br />
                                                Für weitere Informationen und Support besuchen Sie unsere Webseite: <a href=\"http://www.kajona.de\">www.kajona.de</a>'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                                SET absatz_titel = 'Congratulations!',
                                    absatz_inhalt ='This installation of Kajona was successful. Have fun using Kajona!<br />
                                                     For further information, support or proposals, please visit our website: <a href=\"http://www.kajona.de\">www.kajona.de</a>'
                                WHERE content_id = '".dbsafeString($strElementId)."'";
        }


        if($this->objDB->_query($strQuery))
            $strReturn .= "Paragraph element created.\n";
        else
            $strReturn .= "Error creating paragraph element.\n";

        $strReturn .= "Adding image-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("picture1_image");
        $objPagelement->setStrName("picture1");
        $objPagelement->setStrElement("image");
        $objPagelement->saveObjectToDb($this->strIndexID, "picture1_image", _dbprefix_."element_bild", "first");
        $strElementId = $objPagelement->getSystemid();
         $strQuery = "UPDATE "._dbprefix_."element_bild
                            SET bild_bild = '/portal/pics/kajona/login_logo.gif'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
            if($this->objDB->_query($strQuery))
                $strReturn .= "Image element created.\n";
            else
                $strReturn .= "Error creating image element.\n";

        $strReturn .= "Creating system folder...\n";
        include_once(_systempath_."/class_modul_pages_folder.php");
        $objFolder = new class_modul_pages_folder();
        $objFolder->setStrName("_system");
        $objFolder->saveObjectToDb("0");
        $strFolderID = $objFolder->getSystemid();
        $strReturn .= "ID of new folder: ".$strFolderID."\n";
        $strReturn .= "Creating master-page\n";
        $objPage = new class_modul_pages_page();
        $objPage->setStrName("master");
        $objPage->setStrTemplate("master.tpl");
        //set language to "" - being update by the languages sc installer later
        $objPage->setStrLanguage("");
        $objPage->saveObjectToDb($strFolderID);
        $this->strMasterID = $objPage->getSystemid();
        $strReturn .= "ID of new page: ".$this->strMasterID."\n";


        $strReturn .= "Creating error-site...\n";
        include_once(_systempath_."/class_modul_pages_page.php");
        $objPage = new class_modul_pages_page();
        $objPage->setStrName("error");

        if($this->strContentLanguage == "de")
            $objPage->setStrBrowsername("Fehler");
        else
            $objPage->setStrBrowsername("Error");


        $objPage->setStrTemplate("kajona_demo.tpl");
        //set language to "" - being update by the languages sc installer later
        $objPage->setStrLanguage("");
        $objPage->saveObjectToDb($strFolderID);
        $strErrorPageId = $objPage->getSystemid();
        $strReturn .= "ID of new page: ".$strErrorPageId."\n";

        $strReturn .= "Adding headline-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("headline_row");
        $objPagelement->setStrName("headline");
        $objPagelement->setStrElement("row");
        $objPagelement->saveObjectToDb($strErrorPageId, "headline_row", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();

        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Fehler'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Error'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }

        if($this->objDB->_query($strQuery))
            $strReturn .= "Headline element created.\n";
        else
            $strReturn .= "Error creating headline element.\n";

        $strReturn .= "Adding paragraph-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("text_paragraph");
        $objPagelement->setStrName("text");
        $objPagelement->setStrElement("paragraph");
        $objPagelement->saveObjectToDb($strErrorPageId, "text_paragraph", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();


        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                        SET absatz_titel = 'Ein Fehler ist aufgetreten',
                           absatz_inhalt ='Während Ihre Anfrage ist leider ein Fehler aufgetreten.<br />
                                           Bitte versuchen Sie die letzte Aktion erneut.'
                      WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
             $strQuery = "UPDATE "._dbprefix_."element_absatz
                                SET absatz_titel = 'An error occured',
                                    absatz_inhalt ='Maybe the requested page doesn\'t exist anymore.<br />
                                                    Please try it again later.'
                                WHERE content_id = '".dbsafeString($strElementId)."'";
        }

        if($this->objDB->_query($strQuery))
            $strReturn .= "Paragraph element created.\n";
        else
            $strReturn .= "Error creating paragraph element.\n";
            
            
            
            
            
        $strReturn .= "Creating imprint-site...\n";
        include_once(_systempath_."/class_modul_pages_page.php");
        $objPage = new class_modul_pages_page();
        $objPage->setStrName("imprint");
        if($this->strContentLanguage == "de")
            $objPage->setStrBrowsername("Impressum");
        else
            $objPage->setStrBrowsername("Imprint");
        $objPage->setStrTemplate("kajona_demo.tpl");
        //set language to "" - being update by the languages sc installer later
        $objPage->setStrLanguage("");
        $objPage->saveObjectToDb($strFolderID);
        $strImprintPageId = $objPage->getSystemid();
        $strReturn .= "ID of new page: ".$strImprintPageId."\n";
        $strReturn .= "Adding headline-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("headline_row");
        $objPagelement->setStrName("headline");
        $objPagelement->setStrElement("row");
        $objPagelement->saveObjectToDb($strImprintPageId, "headline_row", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();
        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Impressum'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Imprint'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }

        if($this->objDB->_query($strQuery))
            $strReturn .= "Headline element created.\n";
        else
            $strReturn .= "Error creating headline element.\n";

        $strReturn .= "Adding paragraph-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("text_paragraph");
        $objPagelement->setStrName("text");
        $objPagelement->setStrElement("paragraph");
        $objPagelement->saveObjectToDb($strImprintPageId, "text_paragraph", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();


        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                        SET absatz_titel = 'Impressum',
                           absatz_inhalt ='Bitte tragen Sie hier Ihre Kontaktdaten ein.<br />
                                           Nachname, Name<br />
                                           Straße und Hausnummer<br />
                                           PLZ, Ort<br />
                                           Telefon<br />
                                           E-Mail<br />
                                           <br />
                                           Site powered by <a href=\"http://www.kajona.de\" target=\"_blank\" title=\"Kajona³ CMS - empowering your content\">Kajona³</a><br /><a href=\"http://www.kajona.de\" target=\"_blank\" title=\"Kajona³ CMS - empowering your content\"><img src=\"portal/pics/kajona/kajona_poweredby.png\" alt=\"Kajona³\" /></a><br />
                                           '
                      WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
             $strQuery = "UPDATE "._dbprefix_."element_absatz
                        SET absatz_titel = 'Imprint',
                           absatz_inhalt ='Please provide your contact details.<br />
                                           Name, Forename<br />
                                           Street<br />
                                           Zip, City<br />
                                           Phone<br />
                                           Mail<br />
                                           <br />
                                           Site powered by <a href=\"http://www.kajona.de\" target=\"_blank\" title=\"Kajona³ CMS - empowering your content\">Kajona³</a><br /><a href=\"http://www.kajona.de\" target=\"_blank\" title=\"Kajona³ CMS - empowering your content\"><img src=\"portal/pics/kajona/kajona_poweredby.png\" alt=\"Kajona³\" /></a><br />
                                           '
                      WHERE content_id = '".dbsafeString($strElementId)."'";
        }  
        
        if($this->objDB->_query($strQuery))
            $strReturn .= "Paragraph element created.\n";
        else
            $strReturn .= "Error creating paragraph element.\n";


            
            
            
            
        $strReturn .= "Creating sample page...\n";
        include_once(_systempath_."/class_modul_pages_page.php");
        $objPage = new class_modul_pages_page();
        $objPage->setStrName("page_1");
        if($this->strContentLanguage == "de")
            $objPage->setStrBrowsername("Beispielseite 1");
        else
            $objPage->setStrBrowsername("Sample page 1");
        $objPage->setStrTemplate("kajona_demo.tpl");
        //set language to "" - being update by the languages sc installer later
        $objPage->setStrLanguage("");
        $objPage->saveObjectToDb();
        $strSamplePageId = $objPage->getSystemid();
        $strReturn .= "ID of new page: ".$strSamplePageId."\n";
        $strReturn .= "Adding headline-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("headline_row");
        $objPagelement->setStrName("headline");
        $objPagelement->setStrElement("row");
        $objPagelement->saveObjectToDb($strSamplePageId, "headline_row", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();
        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Beispielseite 1'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Sample page 1'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }

        if($this->objDB->_query($strQuery))
            $strReturn .= "Headline element created.\n";
        else
            $strReturn .= "Error creating headline element.\n";

        $strReturn .= "Adding paragraph-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("text_paragraph");
        $objPagelement->setStrName("text");
        $objPagelement->setStrElement("paragraph");
        $objPagelement->saveObjectToDb($strSamplePageId, "text_paragraph", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();


        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
							SET absatz_titel = 'Standard-Absatz',
								absatz_inhalt ='Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
						 	WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
             $strQuery = "UPDATE "._dbprefix_."element_absatz
							SET absatz_titel = 'Standard paragraph',
								absatz_inhalt ='Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
							WHERE content_id = '".dbsafeString($strElementId)."'";
        }  
        
        if($this->objDB->_query($strQuery))
            $strReturn .= "Paragraph element created.\n";
        else
            $strReturn .= "Error creating paragraph element.\n";






       $strReturn .= "Creating sample subpage...\n";
        include_once(_systempath_."/class_modul_pages_page.php");
        $objPage = new class_modul_pages_page();
        $objPage->setStrName("subpage_1");
        if($this->strContentLanguage == "de")
            $objPage->setStrBrowsername("Beispiel-Unterseite 1");
        else
            $objPage->setStrBrowsername("Sample subpage 1");
        $objPage->setStrTemplate("kajona_demo.tpl");
        //set language to "" - being update by the languages sc installer later
        $objPage->setStrLanguage("");
        $objPage->saveObjectToDb();
        $strSampleSubPageId = $objPage->getSystemid();
        $strReturn .= "ID of new page: ".$strSampleSubPageId."\n";
        $strReturn .= "Adding headline-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("headline_row");
        $objPagelement->setStrName("headline");
        $objPagelement->setStrElement("row");
        $objPagelement->saveObjectToDb($strSampleSubPageId, "headline_row", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();
        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Beispiel-Unterseite 1'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
                            SET absatz_titel = 'Sample subpage 1'
                            WHERE content_id = '".dbsafeString($strElementId)."'";
        }

        if($this->objDB->_query($strQuery))
            $strReturn .= "Headline element created.\n";
        else
            $strReturn .= "Error creating headline element.\n";

        $strReturn .= "Adding paragraph-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("text_paragraph");
        $objPagelement->setStrName("text");
        $objPagelement->setStrElement("paragraph");
        $objPagelement->saveObjectToDb($strSampleSubPageId, "text_paragraph", _dbprefix_."element_absatz", "first");
        $strElementId = $objPagelement->getSystemid();


        if($this->strContentLanguage == "de") {
            $strQuery = "UPDATE "._dbprefix_."element_absatz
							SET absatz_titel = 'Standard-Absatz auf Unterseite',
								absatz_inhalt ='Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
						 	WHERE content_id = '".dbsafeString($strElementId)."'";
        }
        else {
             $strQuery = "UPDATE "._dbprefix_."element_absatz
							SET absatz_titel = 'Standard paragraph on subpage',
								absatz_inhalt ='Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
							WHERE content_id = '".dbsafeString($strElementId)."'";
        }  
        
        if($this->objDB->_query($strQuery))
            $strReturn .= "Paragraph element created.\n";
        else
            $strReturn .= "Error creating paragraph element.\n";

        return $strReturn;
    }
    
    public function setObjDb($objDb) {
        $this->objDB = $objDb;
    }
    
    public function setStrContentlanguage($strContentlanguage) {
        $this->strContentLanguage = $strContentlanguage;
    }

    public function getCorrespondingModule() {
        return "pages";
    }
    
}
?>