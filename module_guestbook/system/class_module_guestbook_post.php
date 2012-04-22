<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_module_navigation_tree.php 4582 2012-04-11 18:27:04Z sidler $                              *
********************************************************************************************************/

/**
 * Class to represent a guestbook post
 *
 * @package module_guestbook
 * @author sidler@mulchprod.de
 *
 * @targetTable guestbook_post.guestbook_post_id
 */
class class_module_guestbook_post extends class_model implements interface_model, interface_admin_listable  {

    /**
     * @var string
     * @tableColumn guestbook_post.guestbook_post_name
     */
    private $strGuestbookPostName = "";

    /**
     * @var string
     * @tableColumn guestbook_post.guestbook_post_email
     */
    private $strGuestbookPostEmail = "";

    /**
     * @var string
     * @tableColumn guestbook_post.guestbook_post_page
     */
    private $strGuestbookPostPage = "";

    /**
     * @var string
     * @tableColumn guestbook_post.guestbook_post_text
     */
    private $strGuestbookPostText = "";

    /**
     * @var int
     * @tableColumn guestbook_post.guestbook_post_date
     */
    private $intGuestbookPostDate = 0;


    /**
     * Constructor to create a valid object
     *
     * @param string $strSystemid (use "" on new objects)
     */
    public function __construct($strSystemid = "") {
		$this->setArrModuleEntry("moduleId", _guestbook_module_id_);
		$this->setArrModuleEntry("modul", "guestbook");

		//base class
		parent::__construct($strSystemid);
    }


    /**
     * Returns the icon the be used in lists.
     * Please be aware, that only the filename should be returned, the wrapping by getImageAdmin() is
     * done afterwards.
     *
     * @return string the name of the icon, not yet wrapped by getImageAdmin()
     */
    public function getStrIcon() {
        return "icon_book.gif";
    }

    /**
     * In nearly all cases, the additional info is rendered left to the action-icons.
     * @return string
     */
    public function getStrAdditionalInfo() {
        return timeToString($this->getIntGuestbookPostDate(), false) . " ".$this->getStrGuestbookPostEmail();
    }

    /**
     * If not empty, the returned string is rendered below the common title.
     * @return string
     */
    public function getStrLongDescription() {
        return uniStrTrim($this->getStrGuestbookPostText(), 70);
    }

    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     * @return string
     */
    public function getStrDisplayName() {
        return $this->getStrGuestbookPostName();
    }





    /**
     * Disables new posts if the guestbook itself is moderated.
     *
     * @return bool
     */
    protected function onInsertToDb() {
        $objGuestbook = new class_module_guestbook_guestbook($this->getPrevId());
        if($objGuestbook->getIntGuestbookModerated() == "1")
            $this->setIntRecordStatus(0, false);

        return true;
    }


    /**
     * Loads all posts belonging to the given systemid (in most cases a guestbook)
     *
     * @param string $strPrevId
     * @param bool $bitJustActive
     * @param null $intStart
     * @param null $intEnd
     *
     * @return class_module_guestbook_post[]
     * @static
     */
	public static function getPosts($strPrevId = "", $bitJustActive = false, $intStart = null, $intEnd = null) {
	    $strQuery = "SELECT system_id
						FROM "._dbprefix_."guestbook_post, "._dbprefix_."system
						WHERE system_id = guestbook_post_id
						  AND system_prev_id=?
						  ".($bitJustActive ? " AND system_status = 1" : "" )."
						ORDER BY guestbook_post_date DESC";

	    $objDB = class_carrier::getInstance()->getObjDB();
	    $arrPosts = $objDB->getPArray($strQuery, array($strPrevId), $intStart, $intEnd);

	    $arrReturn = array();
	    //load all posts as objects
	    foreach($arrPosts as $arrOnePostID) {
            $arrReturn[] = new class_module_guestbook_post($arrOnePostID["system_id"]);
	    }
		return $arrReturn;
	}

    /**
     * Looks up the posts available
     *
     * @param string $strPrevID
     * @param bool $bitJustActive
     *
     * @return int
     * @static
     */
	public static function getPostsCount($strPrevID = "", $bitJustActive = false) {
	    $strQuery = "SELECT COUNT(*)
						FROM "._dbprefix_."guestbook_post, "._dbprefix_."system
						WHERE system_id = guestbook_post_id
						  AND system_prev_id=?
						  ".($bitJustActive ? " AND system_status = 1" : "" )."";

	    $objDB = class_carrier::getInstance()->getObjDB();
	    $arrRow = $objDB->getPRow($strQuery, array($strPrevID));
	    return $arrRow["COUNT(*)"];
	}



    public function setIntGuestbookPostDate($intGuestbookPostDate) {
        $this->intGuestbookPostDate = $intGuestbookPostDate;
    }

    public function getIntGuestbookPostDate() {
        return $this->intGuestbookPostDate;
    }

    public function setStrGuestbookPostEmail($strGuestbookPostEmail) {
        $this->strGuestbookPostEmail = $strGuestbookPostEmail;
    }

    /**
     * @return string
     * @fieldType text
     * @fieldValidator email
     */
    public function getStrGuestbookPostEmail() {
        return $this->strGuestbookPostEmail;
    }

    public function setStrGuestbookPostName($strGuestbookPostName) {
        $this->strGuestbookPostName = $strGuestbookPostName;
    }

    /**
     * @return string
     * @fieldType text
     */
    public function getStrGuestbookPostName() {
        return $this->strGuestbookPostName;
    }

    public function setStrGuestbookPostPage($strGuestbookPostPage) {
        //Remove protocol-prefixes
        $strGuestbookPostPage = str_replace("http://", "", $strGuestbookPostPage);
        $strGuestbookPostPage = str_replace("https://", "", $strGuestbookPostPage);
        $this->strGuestbookPostPage = $strGuestbookPostPage;
    }

    /**
     * @return string
     * @fieldType text
     */
    public function getStrGuestbookPostPage() {
        return $this->strGuestbookPostPage;
    }

    public function setStrGuestbookPostText($strGuestbookPostText) {
        $this->strGuestbookPostText = $strGuestbookPostText;
    }

    /**
     * @return string
     * @fieldType textarea
     */
    public function getStrGuestbookPostText() {
        return $this->strGuestbookPostText;
    }

}