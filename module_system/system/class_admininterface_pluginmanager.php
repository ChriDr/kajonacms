<?php
/*"******************************************************************************************************
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*    $Id$                                            *
********************************************************************************************************/

/**
 * Class holding a simple plugin manager for admin plugins implementing interface_admininterface_plugin.
 *
 * Usage:
 * $objPluginManager = new class_admininterface_pluginmanager();
 * $objPluginManager->loadPluginsFiltered("/admin/statsreports/", self::$STR_PLUGIN_EXTENSION_POINT);
 * $arrPlugins = $this->objPluginManager->getMatchingPluginObjects();
 *
 * @package module_system
 * @author tim.kiefer@kojikui.de
 */
class class_admininterface_pluginmanager {

    private $objDB;
    private $objToolkit;
    private $objLang;

    /**
     * @var interface_admininterface_plugin[]
     */
    private $arrPlugins = array();

    /**
     * @var string
     */
    private $strFilterExtensionPoints = "";

    /**
     * Sets the filter extension point
     *
     * @param string $strFilterExtensionPoints
     */
    public function setFilterExtensionPoints($strFilterExtensionPoints) {
        $this->strFilterExtensionPoints = $strFilterExtensionPoints;
    }

    /**
     * Returns the filter extension point
     *
     * @return string
     */
    public function getFilterExtensionPoints() {
        return $this->strFilterExtensionPoints;
    }

    /**
     * Resets the filter by extension point
     */
    public function resetFilterExtensionPoints() {
        $this->strFilterExtensionPoints = "";
    }

    public function __construct() {
        $this->objDB = class_carrier::getInstance()->getObjDB();
        $this->objToolkit = class_carrier::getInstance()->getObjToolkit("admin");
        $this->objLang = class_carrier::getInstance()->getObjLang();
    }

    private function addPlugin($objPlugin, $strType, $strName) {
        $this->arrPlugins[$strType][$strName] = $objPlugin;
    }

    /**
     * Register a plugin at the plugin manager

     *
*@param interface_admininterface_plugin $objPlugin
     */
    public function registerPlugin(interface_admininterface_plugin $objPlugin) {
        $objRf = new ReflectionClass($objPlugin);
        $arrInterface = $objRf->getInterfaceNames();
        $arrInterface = array_filter($arrInterface, function ($objFilter) {
                return strcmp($objFilter, "interface_admininterface_plugin");
        });
        $this->addPlugin($objPlugin, implode($arrInterface, ","), $objPlugin->getPluginCommand());
    }

    /**
     * Load all Plugins from a given folder.
     *
     * @param $strPath
     */
    public function loadPlugins($strPath) {
        $this->loadPluginsFiltered($strPath, null);
    }

    /**
     * Load Plugins from a given folder filtered by an interface
     *
     * @param $strPath
     * @param $strInterfaceExtensionPoint
     */
    public function loadPluginsFiltered($strPath, $strInterfaceExtensionPoint) {
        $arrPlugins = class_resourceloader::getInstance()->getFolderContent($strPath, array(".php"));

        // Register new Folder to Classloader
        class_classloader::getInstance()->addClassFolder($strPath);

        if($strInterfaceExtensionPoint != null) {
            $this->setFilterExtensionPoints($strInterfaceExtensionPoint);
        }

        foreach($arrPlugins as $strOnePlugin) {
            $strClassName = str_replace(".php", "", $strOnePlugin);
            /** @var $objPlugin interface_admininterface_plugin */
            $objPlugin = new $strClassName($this->objDB, $this->objToolkit, $this->objLang);

            if($objPlugin instanceof interface_admininterface_plugin && $this->matchFilter($objPlugin)) {
                $objPlugin->registerPlugin($this);
            }
        }
    }

    /**
     * Returns all loaded Plugins filtered.

     *
*@return  interface_admininterface_plugin[]
     */
    public function getMatchingPluginObjects() {
        return $this->getPluginObjects($this->getFilterExtensionPoints());
    }

    /**
     * Returns all loaded Plugins of an ExtensionPoint

     *
*@param $strExtensionPoint
     *
*@return interface_admininterface_plugin
     */
    public function getPluginObjects($strExtensionPoint) {
        $arrReturn = $this->arrPlugins[$strExtensionPoint];
        uasort($this->arrPlugins, function (interface_admininterface_plugin $objA, interface_admininterface_plugin $objB) {
            return strcmp($objA->getTitle(), $objB->getTitle());
        });

        return $arrReturn;
    }

    /**
     * Return a Plugin by its ExtensionPoint and execution command

     *
*@param $strExtensionPoint
     * @param $strName
     *
*@return \interface_admininterface_plugin|null
     */
    public function getPluginObject($strExtensionPoint, $strName) {
        $arrObjs = $this->getPluginObjects($strExtensionPoint);
        if(isset ($arrObjs[$strName]))
            return $arrObjs[$strName];
        else
            return null;
    }


    private function matchFilter(interface_admininterface_plugin $objPlugin) {
        if($this->getFilterExtensionPoints() == "")
            return true;
        else {
            $objRf = new ReflectionClass($objPlugin);
            $arrInterface = $objRf->getInterfaceNames();
            return in_array($this->getFilterExtensionPoints(), $arrInterface);
        }
    }

}