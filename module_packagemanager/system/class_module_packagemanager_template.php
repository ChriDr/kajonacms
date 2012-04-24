<?php
/*"******************************************************************************************************
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_module_tags_tag.php 4471 2012-01-25 16:49:25Z sidler $                                    *
********************************************************************************************************/

/**
 * A model-class for template-packs.
 * Since not part of the regular system-table, it only acts as some kind of
 * wrapper.
 *
 * @package module_packagemanager
 * @author sidler@mulchprod.de
 * @since 4.0
 *
 * @targetTable templatepacks.templatepack_id
 */
class class_module_packagemanager_template extends class_model implements interface_model, interface_admin_listable  {

    /**
     * @var string
     * @tableColumn templatepack_name
     */
    private $strName = "";

    private $arrMetadata = array();

    /**
     * Constructor to create a valid object
     *
     * @param string $strSystemid (use "" on new objects)
     */
    public function __construct($strSystemid = "") {

        $this->setArrModuleEntry("modul", "packagemanager");
        $this->setArrModuleEntry("moduleId", _packagemanager_module_id_);

		parent::__construct($strSystemid);

    }

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
        return "icon_dot.gif";
    }

    /**
     * In nearly all cases, the additional info is rendered left to the action-icons.
     * @return string
     */
    public function getStrAdditionalInfo() {
        $strReturn = "";

        if($this->arrMetadata["version"] != "")
            $strReturn .= $this->getLang("pack_version")." ".$this->arrMetadata["version"];

        if($this->arrMetadata["author"] != "")
            $strReturn .= " ".$this->getLang("pack_author")." ".$this->arrMetadata["author"];

        return $strReturn;
    }

    /**
     * If not empty, the returned string is rendered below the common title.
     * @return string
     */
    public function getStrLongDescription() {
        return $this->arrMetadata["description"];
    }

    /**
     * Initialises the current object, if a systemid was given
     *
     */
    protected function initObjectInternal() {
        parent::initObjectInternal();
        $this->arrMetadata = $this->getMetadata();
    }

    /**
     * Deletes the tag with the given systemid from the system
     *
     * @return bool
     */
    protected function deleteObjectInternal() {

        //delete all files from the filesystem
        $objFilesystem = new class_filesystem();
        $objFilesystem->folderDeleteRecursive(_templatepath_."/".$this->getStrName());

        return parent::deleteObjectInternal();
    }

    /**
     * Fetches the list of packs available
     *
     * @static
     * @param null|int $intStart
     * @param null|int $intEnd
     * @return class_module_templatemanager_template[]
     */
    public static function getAllTemplatepacks($intStart = null, $intEnd = null) {
        $strQuery = "SELECT templatepack_id
                       FROM "._dbprefix_."templatepacks
                   ORDER BY templatepack_name ASC ";

        $arrRows = class_carrier::getInstance()->getObjDB()->getPArray($strQuery, array(), $intStart, $intEnd);

        $arrReturn = array();
        foreach($arrRows as $arrOneRow)
            $arrReturn[] = new class_module_packagemanager_template($arrOneRow["templatepack_id"]);

        return $arrReturn;
    }

    /**
     * Fetches the list of packs available
     *
     * @static
     * @return int
     */
    public static function getAllTemplatepacksCount() {
        $strQuery = "SELECT COUNT(*)
                       FROM "._dbprefix_."templatepacks ";

        $arrRow = class_carrier::getInstance()->getObjDB()->getPRow($strQuery, array());
        return $arrRow["COUNT(*)"];
    }

    /**
     * Synchronized the list of template-packs available in the filesystem
     * with the list of packs stored at the database.
     * @static
     */
    public static function syncTemplatepacks() {
        //scan the list of packs available in the filesystem
        $objFilesystem = new class_filesystem();
        $arrFolders = $objFilesystem->getCompleteList("/templates");

        //scan packs installed
        $arrPacksInstalled = self::getAllTemplatepacks();

        foreach($arrFolders["folders"] as $strOneFolder) {
            $bitFolderFound = false;
            //search the pack in the list of available ones
            foreach($arrPacksInstalled as $objOnePack) {
                if($objOnePack->getStrName() == $strOneFolder) {
                    $bitFolderFound = true;
                    break;
                }
            }
            if(!$bitFolderFound) {
                $objPack = new class_module_packagemanager_template();
                $objPack->setStrName($strOneFolder);
                $objPack->updateObjectToDb();
                $objPack->setIntRecordStatus(0);
            }
        }

        //scan folders not existing any more
        foreach($arrPacksInstalled as $objOnePack) {
            if(!in_array($objOnePack->getStrName(), $arrFolders["folders"]))
                $objOnePack->deleteObject();
        }
    }

    public function setIntRecordStatus($intRecordStatus, $bitFireStatusChangeEvent = true) {
        if($intRecordStatus == 1) {
            //if set to active, mark all other packs as invalid
            $strQuery = "SELECT templatepack_id
                          FROM "._dbprefix_."templatepacks,
                               "._dbprefix_."system
                         WHERE system_id = templatepack_id
                           AND system_status = 1";
            $arrRows = $this->objDB->getPArray($strQuery, array());
            foreach($arrRows as $arrSingleRow) {
                $objPack = new class_module_packagemanager_template($arrSingleRow["templatepack_id"]);
                $objPack->setIntRecordStatus(0);
            }

            //update the active-pack constant
            $objSetting = class_module_system_setting::getConfigByName("_packagemanager_defaulttemplate_");
            $objSetting->setStrValue($this->getStrName());
            $objSetting->updateObjectToDb();
            $this->flushCompletePagesCache();
        }

        return parent::setIntRecordStatus($intRecordStatus, $bitFireStatusChangeEvent);
    }


    public function getMetadata() {
        $arrMetadata = array();
        $arrMetadata["name"] = "";
        $arrMetadata["author"] = "";
        $arrMetadata["description"] = "";
        $arrMetadata["version"] = "";
        $arrMetadata["licence"] = "";
        $arrMetadata["url"] = "";

        //try to load the metadata.xml file
        if(is_file(_realpath_._templatepath_."/".$this->strName."/metadata.xml")) {
            $objXML = new class_xml_parser();
            $objXML->loadFile(_templatepath_."/".$this->strName."/metadata.xml");
            $arrTree = $objXML->xmlToArray();

            $arrMetadata["name"]        = $arrTree["templatepack"]["0"]["name"]["0"]["value"];
            $arrMetadata["author"]      = $arrTree["templatepack"]["0"]["author"]["0"]["value"];
            $arrMetadata["description"] = $arrTree["templatepack"]["0"]["description"]["0"]["value"];
            $arrMetadata["version"]     = $arrTree["templatepack"]["0"]["version"]["0"]["value"];
            $arrMetadata["licence"]     = $arrTree["templatepack"]["0"]["licence"]["0"]["value"];
            $arrMetadata["url"]         = $arrTree["templatepack"]["0"]["url"]["0"]["value"];
        }

        return $arrMetadata;
    }

    public function setStrName($strName) {
        $this->strName = $strName;
    }

    public function getStrName() {
        return $this->strName;
    }
}