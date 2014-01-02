<?php
/*"******************************************************************************************************
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                             *
********************************************************************************************************/


class class_project_setup {
    public static function setUp() {

        echo "<b>Kajona V4 project setup.</b>\nCreates the folder-structure required to build a new project.\n\n";

        $strCurFolder = __DIR__;

        echo "core-path: ".$strCurFolder.", folder found: ".substr($strCurFolder, -4)."\n";

        if(substr($strCurFolder, -4) != "core") {
            echo "current folder must be named core!";
            return;
        }


        echo "loading core...\n\n";
        include __DIR__."/bootstrap.php";

        $arrModules = scandir(_corepath_);

        $arrModules = array_filter(
            $arrModules,
            function($strValue) {
                return preg_match("/(module|element|_)+.*/i", $strValue);
            }
        );


        self::checkDir("/admin");
        self::createAdminRedirect();

        self::checkDir("/project");
        self::checkDir("/project/log");
        self::checkDir("/project/dbdumps");
        self::checkDir("/project/lang");
        self::checkDir("/project/system");
        self::checkDir("/project/system/config");
        //self::checkDir("/project/system/classes");
        self::checkDir("/project/portal");
        self::checkDir("/project/temp");
        self::checkDir("/templates");
        self::checkDir("/files");
        self::checkDir("/files/cache");
        self::checkDir("/files/downloads");
        self::checkDir("/files/images");
        self::checkDir("/files/public");

        self::checkDir("/templates/default");
        self::checkDir("/templates/default/js");
        self::checkDir("/templates/default/css");
        self::checkDir("/templates/default/tpl");
        self::checkDir("/templates/default/pics");

        self::createLangProjectEntry();
        self::createDefaultTemplateEntry();


        echo "searching for files on root-path...\n";
        foreach($arrModules as $strSingleModule) {
            if(!is_dir(_corepath_."/".$strSingleModule))
                continue;

            $arrContent = scandir(_corepath_."/".$strSingleModule);
            foreach($arrContent as $strSingleEntry) {
                if(substr($strSingleEntry, -5) == ".root") {
                    echo "copy ".$strSingleEntry." to "._realpath_."/".substr($strSingleEntry, 0, -5)."\n";
                    copy(_corepath_."/".$strSingleModule."/".$strSingleEntry, _realpath_."/".substr($strSingleEntry, 0, -5));
                }
            }
        }


        echo "\n<b>Kajona V4 template setup.</b>\nCreates the default-template-pack required to render pages.\n";
        echo "Files already existing are NOT overwritten.\n";


        foreach($arrModules as $strSingleModule) {
            if(is_dir(_corepath_."/".$strSingleModule."/templates")) {
                $arrEntries = scandir(_corepath_."/".$strSingleModule."/templates");
                foreach($arrEntries as $strOneFolder) {
                    if($strOneFolder != "." && $strOneFolder != ".." && is_dir(_corepath_."/".$strSingleModule."/templates/".$strOneFolder)) {
                        if($strOneFolder == "default")
                            self::copyFolder(_corepath_."/".$strSingleModule."/templates", _realpath_."/templates", array(".tpl"));
                        else
                            self::copyFolder(_corepath_."/".$strSingleModule."/templates", _realpath_."/templates");
                    }
                }
            }

            if(is_dir(_corepath_."/".$strSingleModule."/files"))
                self::copyFolder(_corepath_."/".$strSingleModule."/files", _realpath_."/files");
        }


        echo "\n<b>Kajona V4 htaccess setup</b>\n";
        self::createAllowHtaccess("/files/cache/.htaccess");
        self::createAllowHtaccess("/files/images/.htaccess");
        self::createAllowHtaccess("/files/public/.htaccess");
        self::createAllowHtaccess("/templates/.htaccess");

        self::createDenyHtaccess("/project/.htaccess");

    }


    private static function createLangProjectEntry() {
        $strContent = <<<TXT

Kajona V4 lang subsystem.

    Since Kajona V4, it is possible to change the default-lang files by deploying them inside the projects'
    lang-folder.
    This provides a way to change texts and labels without breaking them during the next system-update.

    Example: By default, the Template-Manager is titled "Packagemanagement".
    The entry is created by the file

    /core/module_packagemanager/lang/module_packagemanager/lang_packagemanager_en.php -> \$lang["modul_titel"].

    To change the entry to "Packages" or "Modules" copy the original lang-file into the matching folder
    under the project root. Using the example above, that would be:

    /project/lang/module_packagemanager/lang_packagemanager_en.php

    Now change the entry
    \$lang["modul_titel"] = "Packagemanagement";
    to
    \$lang["modul_titel"] = "Packages";

    Reload your browser and enjoy the relabeled interface.


TXT;
        file_put_contents(_realpath_."/project/lang/readme.txt", $strContent);
    }

    private static function createDefaultTemplateEntry() {
        $strContent = <<<TXT

Kajona V4 default template-pack.

Please don't change anything within this folder, updated may break your changes
and overwrite them without further warning.

If you want to adjust or change anything, create a new template pack using the
backend (module package-management, list templates, create new template) and
select the templates to redefine.

Afterwards change the files in your new templatepack and activate the pack
in the backend via the package-management.


TXT;
        file_put_contents(_realpath_."/templates/default/readme.txt", $strContent);
    }


    private static function createAdminRedirect() {
        $strContent  = "<html>\n";
        $strContent .= " <head>\n";
        $strContent .= "  <title>Loading</title>\n";
        $strContent .= "  <meta http-equiv='refresh' content='0; URL=../index.php?admin=1'>\n";
        $strContent .= " </head>\n";
        $strContent .= " <body>Loading...</body>\n";
        $strContent .= "</html>\n";

        file_put_contents(_realpath_."/admin/index.html", $strContent);
    }

    private static function checkDir($strFolder) {
        echo "checking dir "._realpath_.$strFolder."\n";
        if(!is_dir(_realpath_.$strFolder)) {
            mkdir(_realpath_.$strFolder, 0777);
            echo " \t\t... directory created\n";
        }
        else {
            echo " \t\t... already existing.\n";
        }
    }


    private static function copyFolder($strSourceFolder, $strTargetFolder, $arrExcludeSuffix = array()) {
        $arrEntries = scandir($strSourceFolder);
        foreach($arrEntries as $strOneEntry) {
            if($strOneEntry == "." || $strOneEntry == ".." || $strOneEntry == ".svn" || in_array(uniSubstr($strOneEntry, uniStrrpos($strOneEntry, ".")), $arrExcludeSuffix))
                continue;

            if(is_file($strSourceFolder."/".$strOneEntry) && !is_file($strTargetFolder."/".$strOneEntry)) {
                echo "copying file ".$strSourceFolder."/".$strOneEntry." to ".$strTargetFolder."/".$strOneEntry."\n";
                if(!is_dir($strTargetFolder))
                    mkdir($strTargetFolder, 0777, true);

                copy($strSourceFolder."/".$strOneEntry, $strTargetFolder."/".$strOneEntry);
                chmod($strTargetFolder."/".$strOneEntry, 0777);
            }
            else if(is_dir($strSourceFolder."/".$strOneEntry)) {
                self::copyFolder($strSourceFolder."/".$strOneEntry, $strTargetFolder."/".$strOneEntry, $arrExcludeSuffix);
            }
        }
    }

    private static function createDenyHtaccess($strPath) {
        echo "placing deny htaccess in ".$strPath."\n";
        $strContent = "\n\nDeny from all\n\n";
        file_put_contents(_realpath_.$strPath, $strContent);
    }

    private static function createAllowHtaccess($strPath) {
        echo "placing allow htaccess in ".$strPath."\n";
        $strContent = "\n\nAllow from all\n\n";
        file_put_contents(_realpath_.$strPath, $strContent);
    }
}

echo "<pre>";

class_project_setup::setUp();

echo "</pre>";
