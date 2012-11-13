<?php
/*"******************************************************************************************************
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                  *
********************************************************************************************************/


/**
 * Implementation to handle module-packages. List all installed module-packages and starts the installation / update.
 *
 * @package module_packagemanager
 * @author sidler@mulchprod.de
 * @since 4.0
 */
class class_module_packagemanager_packagemanager_module implements interface_packagemanager_packagemanager {

    /**
     * @var class_module_packagemanager_metadata
     */
    protected $objMetadata;


    /**
     * Returns a list of installed packages, so a single metadata-entry
     * for each package.
     *
     * @return class_module_packagemanager_metadata[]
     */
    public function getInstalledPackages() {
        $arrReturn = array();

        //loop all modules
        $arrModules = class_resourceloader::getInstance()->getArrModules();

        foreach($arrModules as $strOneModule) {
            try {
                $objMetadata = new class_module_packagemanager_metadata();
                $objMetadata->autoInit("/core/".$strOneModule);
                $arrReturn[] = $objMetadata;
            }
            catch(class_exception $objEx) {

            }
        }

        return $arrReturn;
    }


    /**
     * Copies the extracted(!) package from the temp-folder
     * to the target-folder.
     * In most cases, this is either located at /core or at /templates.
     * The original should be deleted afterwards.
     *
     * @throws class_exception
     */
    public function move2Filesystem() {
        $strSource = $this->objMetadata->getStrPath();
        $strTarget = $this->objMetadata->getStrTarget();

        if(!is_dir(_realpath_.$strSource))
            throw new class_exception("current package ".$strSource." is not a folder.", class_exception::$level_ERROR);

        class_logger::getInstance(class_logger::PACKAGEMANAGEMENT)->addLogRow("moving ".$strSource." to /core/".$strTarget, class_logger::$levelInfo);

        $objFilesystem = new class_filesystem();
        //set a chmod before copying the files - at least try to
        $objFilesystem->chmod("/core/".$strTarget, 0777);

        $objFilesystem->folderCopyRecursive($strSource, "/core/".$strTarget, true);
        $this->objMetadata->setStrPath("/core/".$strTarget);

        //reset chmod after copying the files
        $objFilesystem->chmod("/core/".$strTarget);

        $objFilesystem->folderDeleteRecursive($strSource);
    }


    /**
     * Invokes the installer, if given.
     * The installer itself is capable of detecting whether an update or a plain installation is required.
     *
     * @throws class_exception
     * @return string
     */
    public function installOrUpdate() {
        $strReturn = "";

        if(uniStrpos($this->getObjMetadata()->getStrPath(), "core") === false)
            throw new class_exception("Current module not located at /core.", class_exception::$level_ERROR);

        if(!$this->isInstallable())
            throw new class_exception("Current module isn't installable, not all requirements are given", class_exception::$level_ERROR);

        //search for an existing installer
        $objFilesystem = new class_filesystem();
        $arrInstaller = $objFilesystem->getFilelist($this->objMetadata->getStrPath()."/installer/", array(".php"));

        if($arrInstaller === false)
            return "";

        //start with modules
        foreach($arrInstaller as $strOneInstaller) {

            //skip samplecontent files
            if(uniStrpos($strOneInstaller, "class_") === false)
                continue;

            //skip element installers at first run
            if(uniStrpos($strOneInstaller, "element") === false) {
                class_logger::getInstance(class_logger::PACKAGEMANAGEMENT)->addLogRow("triggering updateOrInstall() on installer ".$strOneInstaller.", all requirements given", class_logger::$levelInfo);
                //trigger update or install
                $strName = uniSubstr($strOneInstaller, 0, -4);
                /** @var $objInstaller interface_installer */
                $objInstaller = new $strName();
                $strReturn .= $objInstaller->installOrUpdate();
            }
        }

        //proceed with elements
        foreach($arrInstaller as $strOneInstaller) {
            //skip samplecontent files
            if(uniStrpos($strOneInstaller, "element") !== false) {
                class_logger::getInstance(class_logger::PACKAGEMANAGEMENT)->addLogRow("triggering updateOrInstall() on installer ".$strOneInstaller.", all requirements given", class_logger::$levelInfo);
                //trigger update or install
                $strName = uniSubstr($strOneInstaller, 0, -4);
                /** @var $objInstaller interface_installer */
                $objInstaller = new $strName();
                $strReturn .= $objInstaller->installOrUpdate();
            }
        }


        $strReturn .= "Updating default template pack...\n";
        $this->updateDefaultTemplate();


        return $strReturn;
    }


    protected function updateDefaultTemplate() {
        $objFilesystem = new class_filesystem();
        class_logger::getInstance(class_logger::PACKAGEMANAGEMENT)->addLogRow("updating default template from /core/".$this->objMetadata->getStrPath(), class_logger::$levelInfo);
        if(is_dir(_realpath_."/".$this->objMetadata->getStrPath()."/templates/default/js"))
            $objFilesystem->folderCopyRecursive($this->objMetadata->getStrPath()."/templates/default/js", "/templates/default/js", true);

        if(is_dir(_realpath_."/".$this->objMetadata->getStrPath()."/templates/default/css"))
            $objFilesystem->folderCopyRecursive($this->objMetadata->getStrPath()."/templates/default/css", "/templates/default/css", true);

        if(is_dir(_realpath_."/".$this->objMetadata->getStrPath()."/templates/default/pics"))
            $objFilesystem->folderCopyRecursive($this->objMetadata->getStrPath()."/templates/default/pics", "/templates/default/pics", true);

    }

    public function setObjMetadata($objMetadata) {
        $this->objMetadata = $objMetadata;
    }

    public function getObjMetadata() {
        return $this->objMetadata;
    }

    /**
     * Validates, whether the current package is installable or not.
     * In nearly all cases
     *
     * @return bool
     */
    public function isInstallable() {

        if(!$this->getObjMetadata()->getBitProvidesInstaller())
            return false;

        //check if required modules are given in matching versions
        $arrRequiredModules = $this->objMetadata->getArrRequiredModules();
        foreach($arrRequiredModules as $strOneModule => $strMinVersion) {

            if(trim($strOneModule) != "") {
                $objModule = class_module_system_module::getModuleByName(trim($strOneModule));
                if($objModule === null) {

                    $arrModules = class_resourceloader::getInstance()->getArrModules();
                    $objMetadata = null;
                    foreach($arrModules as $strOneFolder) {
                        if(uniStrpos($strOneFolder, $strOneModule) !== false) {
                            //TODO: ugly hack to get the list of packages available, only to avoid array-modified warnings when sorting the list of packages
                            $objMetadata = new class_module_packagemanager_metadata();
                            $objMetadata->autoInit("/core/".$strOneFolder);
                        }

                    }

                    if($objMetadata === null || version_compare($strMinVersion, $objMetadata->getStrVersion(), ">")) {
                        return false;
                    }

                }
                else{
                    if(version_compare($strMinVersion, $objModule->getStrVersion(), ">"))
                       return false;
                }
            }
        }


        //compare versions of installed elements
        $objModule = class_module_system_module::getModuleByName($this->getObjMetadata()->getStrTitle());
        if($objModule !== null) {
            if(version_compare($this->objMetadata->getStrVersion(), $objModule->getStrVersion(), ">"))
                return true;
            else
                return false;
        }
        else
            return true;


    }

    /**
     * Gets the version of the package currently installed.
     * If not installed, null should be returned instead.
     *
     * @return string|null
     */
    public function getVersionInstalled() {
        //version compare - depending on module or element
        $objModule = class_module_system_module::getModuleByName($this->getObjMetadata()->getStrTitle());
        if($objModule !== null)
            return $objModule->getStrVersion();
        else
            return null;

    }

    /**
     * Queries the packagemanager for the resolved target path, so the folder to package will be located at
     * after installation (or is already located at since it's already installed.
     *
     * @return mixed
     */
    public function getStrTargetPath() {
        return "/core/".$this->objMetadata->getStrTarget();
    }

}