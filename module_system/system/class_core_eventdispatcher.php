<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id: class_filesystem.php 4325 2011-12-02 13:22:25Z sidler $                                         *
********************************************************************************************************/

/**
 * The core eventmanager is used to trigger and fire internal events such as status-changed or record-deleted-events.
 * Therefore the corresponding interface-implementers are called and notified.
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.0
 */
class class_core_eventdispatcher {

    /**
     * @var interface_statuschanged_listener
     */
    private static $arrStatusChangedListener = null;

    /**
     * @var interface_recorddeleted_listener
     */
    private static $arrRecordDeletedListener = null;

    /**
     * Triggers all model-classes implementing the interface interface_statuschanged_listener and notifies
     * about a new status set.
     *
     * @static
     * @param $strSystemid
     * @param $intNewStatus
     * @return bool
     *
     * @see interface_statuschanged_listener
     */
    public static function notifyStatusChangedListeners($strSystemid, $intNewStatus) {
        $bitReturn = true;
        $arrListener = self::getStatusChangedListeners();
        /** @var interface_statuschanged_listener $objOneListener */
        foreach($arrListener as $objOneListener) {
            //FIXME: move to event-log
            class_logger::getInstance()->addLogRow("propagating statusChangedEvent to ".get_class($objOneListener)." sysid: ".$strSystemid." status: ".$intNewStatus, class_logger::$levelInfo);
            $bitReturn = $bitReturn && $objOneListener->handleStatusChangedEvent($strSystemid, $intNewStatus);
        }

        return $bitReturn;
    }

    /**
     * Triggers all model-classes implementing the interface interface_recorddeleted_listener and notifies them about a
     * deleted record.
     *
     * @static
     * @param $strSystemid
     * @return bool
     *
     * @see interface_recorddeleted_listener
     */
    public static function notifyRecordDeletedListeners($strSystemid) {
        $bitReturn = true;
        $arrListener = self::getRecordDeletedListeners();
        /** @var interface_recorddeleted_listener $objOneListener */
        foreach($arrListener as $objOneListener) {
            //FIXME: move to event-log
            class_logger::getInstance()->addLogRow("propagating recordDeletedEvent to ".get_class($objOneListener)." sysid: ".$strSystemid, class_logger::$levelInfo);
            $bitReturn = $bitReturn && $objOneListener->handleRecordDeletedEvent($strSystemid);
        }

        return $bitReturn;
    }


    /**
     * Loads all objects registered to ne notified in case of status-changes
     * @static
     * @return interface_recorddeleted_listener
     */
    private static function getRecordDeletedListeners() {
        if(self::$arrRecordDeletedListener == null) {
            self::$arrRecordDeletedListener = self::loadInterfaceImplementers("interface_recorddeleted_listener");
        }

        return self::$arrRecordDeletedListener;
    }

    /**
     * Loads all objects registered to ne notified in case of status-changes
     * @static
     * @return interface_statuschanged_listener
     */
    private static function getStatusChangedListeners() {
        if(self::$arrStatusChangedListener == null) {
            self::$arrStatusChangedListener = self::loadInterfaceImplementers("interface_statuschanged_listener");
        }

        return self::$arrStatusChangedListener;
    }


    /**
     * Loads all business-objects implementing the passed interface
     * @static
     * @param $strTargetInterface
     * @return array
     */
    private static function loadInterfaceImplementers($strTargetInterface) {
        $arrReturn = array();
        //load classes in system-folders
        $arrFiles = class_resourceloader::getInstance()->getFolderContent("/system", array(".php"));

        foreach($arrFiles as $strOneFile) {
            if(uniStripos($strOneFile, "class_module_") !== false) {
                $objClass = new ReflectionClass(uniSubstr($strOneFile, 0, -4));
                if(!$objClass->isAbstract() && $objClass->implementsInterface($strTargetInterface)) {
                    $arrReturn[] = $objClass->newInstance();
                }
            }
        }
        return $arrReturn;
    }
}
