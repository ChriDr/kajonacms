<?php
/*"******************************************************************************************************
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                  *
********************************************************************************************************/

/**
 * A simple content-provider used to upload archives from the kajonabase-repo.
 * Provides both, a search and a download-part.
 *
 * @module module_packagemanager
 * @author sidler@mulchprod.de
 * @author flo@mediaskills.org
 * @since 4.0
 */
class class_module_packagemanager_contentprovider_kajonabase extends class_module_packagemanager_contentprovider_remote_base {

    function __construct() {
        parent::__construct("provider_kajonabase", "v4.kajonabase.net",
            "/xml.php?module=packageserver&action=list",
            "/xml.php?module=packageserver&action=searchPackages&title=",
            "/download.php");
    }
}