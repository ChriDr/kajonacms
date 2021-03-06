<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                              *
********************************************************************************************************/


/**
 * This class provides the user and groupmanagement
 *
 * @package module_user
 * @author  sidler@mulchprod.de
 *
 * @module user
 * @moduleId _user_modul_id_
 */
class class_module_user_admin extends class_admin_simple implements interface_admin {

    private $STR_FILTER_SESSION_KEY = "USERLIST_FILTER_SESSION_KEY";

    //languages, the admin area could display (texts)
    protected $arrLanguages = array();

    /**
     * Constructor
     */
    public function __construct() {

        parent::__construct();
        $this->arrLanguages = explode(",", class_carrier::getInstance()->getObjConfig()->getConfig("adminlangs"));

        //backwards compatibility
        if($this->getAction() == "edit") {
            $this->setAction("editUser");
        }


    }

    /**
     * @return array
     */
    public function getOutputModuleNavi() {
        $arrReturn = array();
        $arrReturn[] = array("view", class_link::getLinkAdmin($this->getArrModule("modul"), "list", "", $this->getLang("user_liste"), "", "", true, "adminnavi"));
        $arrReturn[] = array("", "");
        $arrReturn[] = array("edit", class_link::getLinkAdmin($this->getArrModule("modul"), "groupList", "", $this->getLang("gruppen_liste"), "", "", true, "adminnavi"));
        $arrReturn[] = array("", "");
        $arrReturn[] = array("right1", class_link::getLinkAdmin($this->getArrModule("modul"), "loginLog", "", $this->getLang("loginlog"), "", "", true, "adminnavi"));
        $arrReturn[] = array("", "");
        $arrReturn[] = array("right", class_link::getLinkAdmin("right", "change", "&changemodule=" . $this->getArrModule("modul"), $this->getLang("commons_module_permissions"), "", "", true, "adminnavi"));
        return $arrReturn;
    }

    /**
     * Renders the form to create a new entry
     *
     * @return string
     * @permissions edit
     */
    protected function actionNew() {
        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "newUser"));
    }

    /**
     * Renders the form to edit an existing entry
     *
     * @return string
     * @permissions edit
     */
    protected function actionEdit() {
        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "editUser", "&systemid=" . $this->getSystemid()));
    }


    /**
     * Returns a list of current users
     *
     * @return string
     * @autoTestable
     * @permissions view
     */
    protected function actionList() {

        if($this->getParam("doFilter") != "") {
            $this->objSession->setSession($this->STR_FILTER_SESSION_KEY, $this->getParam("userlist_filter"));
            $this->setParam("pv", 1);
            $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul")));
            return "";
        }

        $strReturn = "";

        //add a filter-form
        $strReturn .= $this->objToolkit->formHeader(class_link::getLinkAdminHref($this->getArrModule("modul"), "list"));
        $strReturn .= $this->objToolkit->formInputText("userlist_filter", $this->getLang("user_username"),  $this->objSession->getSession($this->STR_FILTER_SESSION_KEY));
        $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("userlist_filter"));
        $strReturn .= $this->objToolkit->formInputHidden("doFilter", "1");
        $strReturn .= $this->objToolkit->formClose();

        $objIterator = new class_array_section_iterator(class_module_user_user::getObjectCount($this->objSession->getSession($this->STR_FILTER_SESSION_KEY)));
        $objIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objIterator->setArraySection(
            class_module_user_user::getObjectList(
                $this->objSession->getSession($this->STR_FILTER_SESSION_KEY),
                $objIterator->calculateStartPos(),
                $objIterator->calculateEndPos()
            )
        );

        $strReturn .= $this->renderList($objIterator, false, "userList");
        return $strReturn;
    }

    /**
     * @param class_model $objListEntry
     *
     * @return string
     */
    protected function renderStatusAction(class_model $objListEntry) {
        if($objListEntry instanceof class_module_user_user && $objListEntry->rightEdit()) {
            if($objListEntry->getIntActive() == 1) {
                return $this->objToolkit->listButton(class_link::getLinkAdmin("user", "setUserStatus", "&systemid=" . $objListEntry->getSystemid()."&pv=".$this->getParam("pv"), "", $this->getLang("user_active"), "icon_enabled"));
            }
            else {
                return $this->objToolkit->listButton(class_link::getLinkAdmin("user", "setUserStatus", "&systemid=" . $objListEntry->getSystemid()."&pv=".$this->getParam("pv"), "", $this->getLang("user_inactive"), "icon_disabled"));
            }
        }
        return "";
    }

    /**
     * @param interface_model $objListEntry
     *
     * @return string
     */
    protected function renderDeleteAction(interface_model $objListEntry) {
        if($objListEntry instanceof class_module_user_user && $objListEntry->rightDelete()) {

            if($objListEntry->getSystemid() == class_carrier::getInstance()->getObjSession()->getUserID()) {
                return $this->objToolkit->listButton(class_adminskin_helper::getAdminImage("icon_deleteDisabled", $this->getLang("user_loeschen_x")));
            }
            else {
                return $this->objToolkit->listDeleteButton($objListEntry->getStrDisplayName(), $this->getLang("user_loeschen_frage"), class_link::getLinkAdminHref($this->getArrModule("modul"), "deleteUser", "&systemid=" . $objListEntry->getSystemid()));
            }

        }

        if($objListEntry instanceof class_module_user_group) {
            if($objListEntry->getSystemid() != _guests_group_id_ && $objListEntry->getSystemid() != _admins_group_id_ && $this->isGroupEditable($objListEntry)) {
                if($objListEntry->rightDelete()) {
                    return $this->objToolkit->listDeleteButton(
                        $objListEntry->getStrDisplayName(), $this->getLang("gruppe_loeschen_frage"), class_link::getLinkAdminHref($this->getArrModule("modul"), "groupDelete", "&systemid=" . $objListEntry->getSystemid())
                    );
                }
            }
            else {
                return $this->objToolkit->listButton(class_adminskin_helper::getAdminImage("icon_deleteDisabled", $this->getLang("gruppe_loeschen_x")));
            }
        }
        return "";
    }

    /**
     * @param string $strListIdentifier
     * @param bool $bitDialog
     *
     * @return array|string
     */
    protected function getNewEntryAction($strListIdentifier, $bitDialog = false) {
        if($strListIdentifier == "userList" && $this->getObjModule()->rightEdit()) {
            return $this->objToolkit->listButton(class_link::getLinkAdmin($this->getArrModule("modul"), "newUser", "", $this->getLang("action_new_user"), $this->getLang("action_new_user"), "icon_new"));
        }

        if($strListIdentifier == "groupList" && $this->getObjModule()->rightEdit()) {
            return $this->objToolkit->listButton(class_link::getLinkAdmin($this->getArrModule("modul"), "groupNew", "", $this->getLang("action_group_new"), $this->getLang("action_group_new"), "icon_new"));
        }

        return "";
    }

    /**
     * @param class_model $objListEntry
     *
     * @return string
     */
    protected function renderTagAction(class_model $objListEntry) {
        return "";
    }

    /**
     * @param class_model $objListEntry
     *
     * @return string
     */
    protected function renderCopyAction(class_model $objListEntry) {
        return "";
    }


    /**
     * @param class_model|class_module_user_user $objListEntry
     *
     * @return array
     */
    protected function renderAdditionalActions(class_model $objListEntry) {
        $objUsersources = new class_module_user_sourcefactory();

        $arrReturn = array();
        if($objListEntry instanceof class_module_user_user && $objListEntry->rightEdit() && $objUsersources->getUsersource($objListEntry->getStrSubsystem())->getMembersEditable()) {
            $arrReturn[] = $this->objToolkit->listButton(
                class_link::getLinkAdminDialog("user", "editMemberships", "&systemid=" . $objListEntry->getSystemid(), "", $this->getLang("user_zugehoerigkeit"), "icon_group", $objListEntry->getStrUsername())
            );
        }

        $objValidator = new class_email_validator();

        if($objListEntry instanceof class_module_user_user
            && $objListEntry->getObjSourceUser()->isEditable()
            && $objListEntry->getIntActive() == 1
            && $objListEntry->getObjSourceUser()->isPasswordResettable()
            && $objListEntry->rightEdit()
            && $objValidator->validate($objListEntry->getStrEmail())
        ) {
            $arrReturn[] = $this->objToolkit->listButton(class_link::getLinkAdmin("user", "sendPassword", "&systemid=" . $objListEntry->getSystemid(), "", $this->getLang("user_password_resend"), "icon_mailNew"));
        }

        if($objListEntry instanceof class_module_user_user && $objListEntry->getIntActive() == 1) {
            $arrReturn[] = $this->objToolkit->listButton(class_link::getLinkAdminDialog("messaging", "new", "&messaging_user_id=" . $objListEntry->getSystemid(), "", $this->getLang("user_send_message"), "icon_mail", $this->getLang("user_send_message")));
        }

        if($objListEntry instanceof class_module_user_user && $objListEntry->getIntActive() == 1 && in_array(_admins_group_id_, $this->objSession->getGroupIdsAsArray())) {
            $arrReturn[] = $this->objToolkit->listButton(class_link::getLinkAdmin("user", "switchToUser", "&systemid=" . $objListEntry->getSystemid(), "", $this->getLang("user_switch_to"), "icon_userswitch"));
        }

        if($objListEntry instanceof class_module_user_group && $objListEntry->rightEdit()) {
            $arrReturn[] = $this->objToolkit->listButton(class_link::getLinkAdmin("user", "groupMember", "&systemid=" . $objListEntry->getSystemid(), "", $this->getLang("action_group_member"), "icon_group"));
        }

        return $arrReturn;
    }

    /**
     * @param class_model $objListEntry
     * @param bool $bitDialog
     *
     * @return string
     */
    protected function renderEditAction(class_model $objListEntry, $bitDialog = false) {
        if($objListEntry instanceof class_module_user_group) {
            if($objListEntry->getSystemid() != _guests_group_id_ && $objListEntry->getSystemid() != _admins_group_id_ && $this->isGroupEditable($objListEntry)) {
                if($objListEntry->rightEdit()) {
                    return $this->objToolkit->listButton(class_link::getLinkAdmin("user", "groupEdit", "&systemid=" . $objListEntry->getSystemid(), "", $this->getLang("action_group_edit"), "icon_edit"));
                }
            }
            else {
                return $this->objToolkit->listButton(class_adminskin_helper::getAdminImage("icon_editDisabled", $this->getLang("gruppe_bearbeiten_x")));
            }
        }
        return parent::renderEditAction($objListEntry);
    }


    /**
     * Shows a form in order to start the process of resetting a users password.
     * The step wil be completed by an email, containing a temporary password and a confirmation link.
     *
     * @return string
     * @permissions edit
     */
    protected function actionSendPassword() {
        $strReturn = "";
        $objUser = new class_module_user_user($this->getSystemid());

        $strReturn .= $this->objToolkit->formHeader(class_link::getLinkAdminHref($this->getArrModule("modul"), "sendPasswordFinal"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("user_resend_password_hint"));
        $strReturn .= $this->objToolkit->formTextRow($this->getLang("user_username") . " " . $objUser->getStrUsername());
        $strReturn .= $this->objToolkit->formTextRow($this->getLang("form_user_email") . " " . $objUser->getStrEmail());
        $strReturn .= $this->objToolkit->formInputHidden("systemid", $this->getSystemid());
        $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
        $strReturn .= $this->objToolkit->formClose();
        return $strReturn;
    }

    /**
     * @return string
     * @permissions edit
     */
    protected function actionSendPasswordFinal() {
        $strReturn = "";
        $objUser = new class_module_user_user($this->getSystemid());

        //add a one-time token and reset the password
        $strToken = generateSystemid();
        $objUser->setStrAuthcode($strToken);
        $objUser->updateObjectToDb();

        $strActivationLink = class_link::getLinkAdminHref("login", "pwdReset", "&systemid=" . $objUser->getSystemid() . "&authcode=" . $strToken, false);

        $objMail = new class_mail();
        $objMail->addTo($objUser->getStrEmail());
        $objMail->setSubject($this->getLang("user_password_resend_subj"));
        $objMail->setText($this->getLang("user_password_resend_body") . $strActivationLink);

        $objMail->sendMail();

        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul")));
        return $strReturn;
    }

    /**
     * Negates the status of an existing user
     *
     * @throws class_exception
     * @return string "" in case of success
     * @permissions edit
     */
    protected function actionSetUserStatus() {
        $strReturn = "";
        $objUser = new class_module_user_user($this->getSystemid());
        if($objUser->getIntActive() == 1) {
            $objUser->setIntActive(0);
        }
        else {
            $objUser->setIntActive(1);
        }

        if($objUser->updateObjectToDb()) {
            $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "list", "&pv=".$this->getParam("pv")));
        }
        else {
            throw new class_exception("Error updating user " . $this->getSystemid(), class_exception::$level_ERROR);
        }

        return $strReturn;
    }

    /**
     * @return string
     */
    protected function actionEditUser() {
        return $this->actionNewUser("edit");
    }

    /**
     * Creates a new user or edits an already existing one
     *
     * @param string $strAction
     * @param class_admin_formgenerator|null $objForm
     *
     * @return string
     * @autoTestable
     */
    protected function actionNewUser($strAction = "new", class_admin_formgenerator $objForm = null) {
        $strReturn = "";

        //parse userid-param to remain backwards compatible
        if($this->getParam("systemid") == "" && validateSystemid($this->getParam("userid"))) {
            $this->setSystemid($this->getParam("userid"));
        }

        //load a few default values
        //languages
        $arrLang = array();
        foreach($this->arrLanguages as $strLanguage) {
            $arrLang[$strLanguage] = $this->getLang("lang_" . $strLanguage);
        }

        //skins
        $arrSkinsTemp = class_adminskin_helper::getListOfAdminskinsAvailable();
        $arrSkins = array();
        foreach($arrSkinsTemp as $strSkin) {
            $arrSkins[$strSkin] = $strSkin;
        }

        //access to usersources
        $objUsersources = new class_module_user_sourcefactory();

        if($strAction == "new") {
            //easy one - provide the form to create a new user. validate if there are multiple user-sources available
            //for creating new users
            if(!$this->getObjModule()->rightEdit()) {
                return $this->getLang("commons_error_permissions");
            }

            if($this->getParam("usersource") == "" || $objUsersources->getUsersource($this->getParam("usersource")) == null) {
                $arrSubsystems = $objUsersources->getArrUsersources();

                $arrDD = array();
                foreach($arrSubsystems as $strOneName) {
                    $objConcreteSubsystem = $objUsersources->getUsersource($strOneName);
                    if($objConcreteSubsystem->getCreationOfUsersAllowed()) {
                        $arrDD[$strOneName] = $strOneName;
                    }
                }

                if(count($arrDD) > 1) {
                    $strReturn = $this->objToolkit->formHeader(class_link::getLinkAdminHref($this->getArrModule("modul"), "newUser"));
                    $strReturn .= $this->objToolkit->formInputDropdown("usersource", $arrDD, $this->getLang("user_usersource"));
                    $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
                    $strReturn .= $this->objToolkit->formClose();

                    return $strReturn;
                }
                else {
                    $this->setParam("usersource", array_pop($arrDD));
                }

            }

            //here we go, the source is set up, create the form
            $objSubsystem = $objUsersources->getUsersource($this->getParam("usersource"));
            $objBlankUser = $objSubsystem->getNewUser();
            if($objBlankUser != null) {

                if($objForm == null) {
                    $objForm = $this->getUserForm($objBlankUser, false, "new");
                }

                $objForm->addField(new class_formentry_hidden("", "usersource"))->setStrValue($this->getParam("usersource"));
                
                return $objForm->renderForm(class_link::getLinkAdminHref($this->getArrModule("modul"), "saveUser"));
            }
        }
        else {
            //editing a user. this could be in two modes - globally, or in selfedit mode
            $bitSelfedit = false;
            if(!$this->getObjModule()->rightEdit()) {

                if($this->getSystemid() == $this->objSession->getUserID() && _user_selfedit_ == "true") {
                    $bitSelfedit = true;
                }
                else {
                    return $this->getLang("commons_error_permissions");
                }
            }

            //get user and userForm
            $objUser = new class_module_user_user($this->getSystemid());
            $objSourceUser = $objUsersources->getSourceUser($objUser);

            if($objForm == null) {
                $objForm = $this->getUserForm($objSourceUser, $bitSelfedit, "edit");
            }


            //set user name
            $strUserName  = $this->getParam("user_username") != "" ? $this->getParam("user_username"):$objUser->getStrUsername();
            $objForm->getField("user_username")->setStrValue($strUserName);
            if($bitSelfedit) {
                $objForm->getField("user_username")->setBitReadonly(true)->setStrValue($objUser->getStrUsername());
            }

            if($objUser->getStrAdminskin() != "" && $objForm->getField("user_skin") != null)
                $objForm->getField("user_skin")->setStrValue($objUser->getStrAdminskin());

            if($objUser->getStrAdminModule() != "" && $objForm->getField("user_startmodule") != null)
                $objForm->getField("user_startmodule")->setStrValue($objUser->getStrAdminModule());

            $objForm->getField("user_language")->setStrValue($objUser->getStrAdminlanguage());

            if(!$bitSelfedit) {
                if($objForm->getField("user_adminlogin") != null)
                    $objForm->getField("user_adminlogin")->setStrValue($objUser->getIntAdmin());

                if($objForm->getField("user_portal") != null)
                    $objForm->getField("user_portal")->setStrValue($objUser->getIntPortal());

                if($objForm->getField("user_active") != null)
                    $objForm->getField("user_active")->setStrValue($objUser->getIntActive());
            }

            $objForm->addField(new class_formentry_hidden("", "usersource"))->setStrValue($this->getParam("usersource"));
            
            return $objForm->renderForm(class_link::getLinkAdminHref($this->getArrModule("modul"), "saveUser"));
        }


        return $strReturn;

    }

    /**
     * @param interface_usersources_user $objUser
     * @param bool $bitSelfedit
     * @param string $strMode
     *
     * @return class_admin_formgenerator|class_model
     */
    protected function getUserForm(interface_usersources_user $objUser, $bitSelfedit, $strMode) {

        //load a few default values
        //languages
        $arrLang = array();
        foreach($this->arrLanguages as $strLanguage) {
            $arrLang[$strLanguage] = $this->getLang("lang_" . $strLanguage);
        }

        //skins
        $arrSkinsTemp = class_adminskin_helper::getListOfAdminskinsAvailable();
        $arrSkins = array();
        foreach($arrSkinsTemp as $strSkin) {
            $arrSkins[$strSkin] = $strSkin;
        }

        //possible start-modules
        $arrModules = array();
        foreach(class_module_system_module::getModulesInNaviAsArray() as $arrOneModule) {
            $objOneModule = class_module_system_module::getModuleByName($arrOneModule["module_name"]);
            if(!$objOneModule->rightView())
                continue;

            $arrModules[$objOneModule->getStrName()] = $objOneModule->getAdminInstanceOfConcreteModule()->getLang("modul_titel");
        }


        $objForm = new class_admin_formgenerator("user", $objUser);
        $objForm->addField(new class_formentry_headline())->setStrValue($this->getLang("user_personaldata"));

        //globals
        $objName = $objForm->addField(new class_formentry_text("user", "username"))->setBitMandatory(true)->setStrLabel($this->getLang("user_username"))->setStrValue($this->getParam("user_username"));
        if($bitSelfedit) {
            $objName->setBitReadonly(true);
        }

        //generic
        //adding elements is more generic right here - load all methods
        if($objUser->isEditable()) {
            $objAnnotations = new class_reflection($objUser);
            $arrProperties = $objAnnotations->getPropertiesWithAnnotation("@fieldType");

            foreach($arrProperties as $strProperty => $strValue) {
                $objField = $objForm->addDynamicField($strProperty);
                if($objField->getStrEntryName() == "user_pass" && $strMode == "new") {
                    $objField->setBitMandatory(true);
                }
            }
        }

        //system-settings
        $objForm->addField(new class_formentry_headline())->setStrValue($this->getLang("user_system"));

        $objForm->addField(new class_formentry_dropdown("user", "skin"))
            ->setArrKeyValues($arrSkins)
            ->setStrValue(($this->getParam("user_skin") != "" ? $this->getParam("user_skin") : _admin_skin_default_))
            ->setStrLabel($this->getLang("user_skin"));

        $objForm->addField(new class_formentry_dropdown("user", "language"))
            ->setArrKeyValues($arrLang)
            ->setStrValue(($this->getParam("user_language") != "" ? $this->getParam("user_language") : ""))
            ->setStrLabel($this->getLang("user_language"));


        $objForm->addField(new class_formentry_dropdown("user", "startmodule"))
            ->setArrKeyValues($arrModules)
            ->setStrValue(($this->getParam("user_startmodule") != "" ? $this->getParam("user_startmodule") : ""))
            ->setStrLabel($this->getLang("user_startmodule"));


        if(!$bitSelfedit) {
            $objForm->addField(new class_formentry_checkbox("user", "adminlogin"))->setStrLabel($this->getLang("user_admin"));
            $objForm->addField(new class_formentry_checkbox("user", "portal"))->setStrLabel($this->getLang("user_portal"));
            $objForm->addField(new class_formentry_checkbox("user", "active"))->setStrLabel($this->getLang("user_aktiv"));
        }

        if(count($objUser->getGroupIdsForUser()) == 0) {
            $objForm->addField(new class_formentry_plaintext("group_hint"))->setStrValue($this->objToolkit->warningBox($this->getLang("form_user_hint_groups")));
            $objForm->setFieldToPosition("group_hint", 1);
        }

        $objForm->addField(new class_formentry_hidden("", "mode"))->setStrValue($strMode);

        return $objForm;
    }

    /**
     * Stores the submitted data to the backend / the loginprovider
     *
     * @return string
     */
    protected function actionSaveUser() {
        $strReturn = "";
        $bitSelfedit = false;

        $objUsersources = new class_module_user_sourcefactory();
        if($this->getParam("mode") == "new") {
            if(!$this->getObjModule()->rightEdit()) {
                return $this->getLang("commons_error_permissions");
            }

            $objSubsystem = $objUsersources->getUsersource($this->getParam("usersource"));
            $objBlankUser = $objSubsystem->getNewUser();
            $objForm = $this->getUserForm($objBlankUser, false, "new");
        }
        else {
            if(!$this->getObjModule()->rightEdit()) {
                if($this->getSystemid() == $this->objSession->getUserID() && _user_selfedit_ == "true") {
                    $bitSelfedit = true;
                }
                else {
                    return $this->getLang("commons_error_permissions");
                }
            }

            $objUser = new class_module_user_user($this->getSystemid());
            $objSourceUser = $objUsersources->getSourceUser($objUser);
            $objForm = $this->getUserForm($objSourceUser, $bitSelfedit, "edit");
        }


        if(($this->getParam("mode") == "new" && !$this->checkAdditionalNewData($objForm))
            | ($this->getParam("mode") == "edit" && !$this->checkAdditionalEditData($objForm))
            | !$objForm->validateForm()
        ) {
            return $this->actionNewUser($this->getParam("mode"), $objForm);
        }

        $objUser = null;
        if($this->getParam("mode") == "new") {

            //create a new user and pass all relevant data
            $objUser = new class_module_user_user();
            $objUser->setStrSubsystem($this->getParam("usersource"));

            $objUser->setStrUsername($this->getParam("user_username"));
            $objUser->setIntActive(($this->getParam("user_active") != "" && $this->getParam("user_active") == "checked") ? 1 : 0);
            $objUser->setIntAdmin(($this->getParam("user_adminlogin") != "" && $this->getParam("user_adminlogin") == "checked") ? 1 : 0);
            $objUser->setIntPortal(($this->getParam("user_portal") != "" && $this->getParam("user_portal") == "checked") ? 1 : 0);

        }
        else if($this->getParam("mode") == "edit") {

            //create a new user and pass all relevant data
            $objUser = new class_module_user_user($this->getSystemid());

            if(!$bitSelfedit) {
                $objUser->setStrUsername($this->getParam("user_username"));
                $objUser->setIntActive(($this->getParam("user_active") != "" && $this->getParam("user_active") == "checked") ? 1 : 0);
                $objUser->setIntAdmin(($this->getParam("user_adminlogin") != "" && $this->getParam("user_adminlogin") == "checked") ? 1 : 0);
                $objUser->setIntPortal(($this->getParam("user_portal") != "" && $this->getParam("user_portal") == "checked") ? 1 : 0);
            }
        }

        $objUser->setStrAdminskin($this->getParam("user_skin"));
        $objUser->setStrAdminlanguage($this->getParam("user_language"));
        $objUser->setStrAdminModule($this->getParam("user_startmodule"));

        $objUser->updateObjectToDb();
        $objSourceUser = $objUser->getObjSourceUser();
        $objForm = $this->getUserForm($objSourceUser, $bitSelfedit, $this->getParam("mode"));
        $objForm->updateSourceObject();
        $objSourceUser->updateObjectToDb();


        if($this->getParam("mode") == "edit") {
            //Reset the admin-skin cookie to force the new skin
            $objCookie = new class_cookie();
            //flush the db-cache
            class_carrier::getInstance()->getObjDB()->flushQueryCache();
            $this->objSession->resetUser();
            //and update the cookie
            $objCookie->setCookie("adminskin", $this->objSession->getAdminSkin(false, true));
            //update language set before
            $objCookie->setCookie("adminlanguage", $this->objSession->getAdminLanguage(false, true));
        }

        //flush the navigation cache in order to get new items for a possible updated list
        class_admin_helper::flushActionNavigationCache();
        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "list"));


        return $strReturn;
    }

    /**
     * Deletes a user from the database
     *
     * @return string
     * @permissions delete
     */
    protected function actionDeleteUser() {
        //The user itself
        $objUser = new class_module_user_user($this->getSystemid());
        $objUser->deleteObject();
        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "list"));
    }

    //--group-management----------------------------------------------------------------------------------

    /**
     * Returns the list of all current groups
     *
     * @return string
     * @autoTestable
     * @permissions view
     */
    protected function actionGroupList() {
        $objArraySectionIterator = new class_array_section_iterator(class_module_user_group::getObjectCount());
        $objArraySectionIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(class_module_user_group::getObjectList("", $objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        return $this->renderList($objArraySectionIterator, false, "groupList");
    }

    /**
     * @return string
     */
    protected function actionGroupEdit() {
        return $this->actionGroupNew("edit");
    }

    /**
     * Edits or creates a group (displays form)
     *
     * @param string $strMode
     * @param \class_admin_formgenerator|null $objForm
     *
     * @return string
     * @permissions edit
     * @autoTestable
     */
    protected function actionGroupNew($strMode = "new", class_admin_formgenerator $objForm = null) {

        $objUsersources = new class_module_user_sourcefactory();

        if($strMode == "new") {

            if($this->getParam("usersource") == "" || $objUsersources->getUsersource($this->getParam("usersource")) == null) {
                $arrSubsystems = $objUsersources->getArrUsersources();

                $arrDD = array();
                foreach($arrSubsystems as $strOneName) {
                    $objConcreteSubsystem = $objUsersources->getUsersource($strOneName);
                    if($objConcreteSubsystem->getCreationOfGroupsAllowed()) {
                        $arrDD[$strOneName] = $strOneName;
                    }
                }

                if(count($arrDD) > 1) {
                    $strReturn = $this->objToolkit->formHeader(class_link::getLinkAdminHref($this->getArrModule("modul"), "groupNew"));
                    $strReturn .= $this->objToolkit->formInputDropdown("usersource", $arrDD, $this->getLang("group_usersource"));
                    $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
                    $strReturn .= $this->objToolkit->formClose();

                    return $strReturn;
                }
                else {
                    $this->setParam("usersource", array_pop($arrDD));
                }
            }

            $objSource = $objUsersources->getUsersource($this->getParam("usersource"));
            $objNewGroup = $objSource->getNewGroup();

            if($objForm == null) {
                $objForm = $this->getGroupForm($objNewGroup);
            }
            $objForm->addField(new class_formentry_hidden("", "usersource"))->setStrValue($this->getParam("usersource"));
            $objForm->addField(new class_formentry_hidden("", "mode"))->setStrValue("new");

            return $objForm->renderForm(class_link::getLinkAdminHref($this->getArrModule("modul"), "groupSave"));
        }

        else {

            $objNewGroup = new class_module_user_group($this->getSystemid());
            $this->setParam("usersource", $objNewGroup->getStrSubsystem());

            if($objForm == null) {
                $objForm = $this->getGroupForm($objNewGroup->getObjSourceGroup());
            }
            $objForm->getField("group_name")->setStrValue($objNewGroup->getStrName());
            $objForm->addField(new class_formentry_hidden("", "usersource"))->setStrValue($this->getParam("usersource"));
            $objForm->addField(new class_formentry_hidden("", "mode"))->setStrValue("edit");
            return $objForm->renderForm(class_link::getLinkAdminHref($this->getArrModule("modul"), "groupSave"));
        }

    }


    /**
     * @param interface_usersources_group|class_model $objGroup
     *
     * @return class_admin_formgenerator
     */
    private function getGroupForm(interface_usersources_group $objGroup) {

        $objForm = new class_admin_formgenerator("group", $objGroup);

        //add the global group-name

        $objForm->addField(new class_formentry_text("group", "name"))->setBitMandatory(true)->setStrLabel($this->getLang("group_name"))->setStrValue($this->getParam("group_name"));

        if($objGroup->isEditable()) {
            //adding elements is more generic right here - load all methods
            $objAnnotations = new class_reflection($objGroup);

            $arrProperties = $objAnnotations->getPropertiesWithAnnotation("@fieldType");

            foreach($arrProperties as $strProperty => $strValue) {
                $objForm->addDynamicField($strProperty);
            }

        }

        return $objForm;
    }

    /**
     * Saves a new group to database
     *
     * @return string "" in case of success
     * @permissions edit
     */
    protected function actionGroupSave() {

        if(!$this->getObjModule()->rightEdit()) {
            return $this->getLang("commons_error_permissions");
        }

        if($this->getParam("mode") == "new") {
            $objUsersources = new class_module_user_sourcefactory();
            $objSource = $objUsersources->getUsersource($this->getParam("usersource"));
            $objNewGroup = $objSource->getNewGroup();
            $objForm = $this->getGroupForm($objNewGroup);
        }
        else {
            $objNewGroup = new class_module_user_group($this->getSystemid());
            $objForm = $this->getGroupForm($objNewGroup->getObjSourceGroup());
        }

        if(!$objForm->validateForm()) {
            return $this->actionGroupNew($this->getParam("mode"), $objForm);
        }

        if($this->getParam("mode") == "new") {
            $objGroup = new class_module_user_group();
            $objGroup->setStrSubsystem($this->getParam("usersource"));
        }
        else {
            $objGroup = new class_module_user_group($this->getSystemid());
        }

        $objGroup->setStrName($this->getParam("group_name"));
        $objGroup->updateObjectToDb();

        $objSourceGroup = $objGroup->getObjSourceGroup();

        $objForm = $this->getGroupForm($objSourceGroup);
        $objForm->updateSourceObject();

        $objSourceGroup->updateObjectToDb();

        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "groupList"));
        return "";

    }

    private function isGroupEditable(class_module_user_group $objGroup) {
        //validate possible blocked groups
        $objConfig = class_config::getInstance("blockedgroups.php");
        $arrBlockedGroups = explode(",", $objConfig->getConfig("blockedgroups"));

        $bitIsSuperAdmin = in_array(_admins_group_id_, $this->objSession->getGroupIdsAsArray());
        $bitRenderEdit = $bitIsSuperAdmin || ($objGroup->rightEdit() && !in_array($objGroup->getSystemid(), $arrBlockedGroups));

        return $bitRenderEdit;
    }



    /**
     * Returns a list of users belonging to a specified group
     *
     * @param class_admin_formgenerator $objForm
     *
     * @return string
     * @permissions edit
     */
    protected function actionGroupMember(class_admin_formgenerator $objForm = null) {
        $strReturn = "";
        if($this->getSystemid() != "") {

            $objGroup = new class_module_user_group($this->getSystemid());

            //validate possible blocked groups
            $bitRenderEdit = $this->isGroupEditable($objGroup);

            $objSourceGroup = $objGroup->getObjSourceGroup();
            $strReturn .= $this->objToolkit->formHeadline($this->getLang("group_memberlist") . "\"" . $objGroup->getStrName() . "\"");

            $objUsersources = new class_module_user_sourcefactory();

            if($objUsersources->getUsersource($objGroup->getStrSubsystem())->getMembersEditable() && $bitRenderEdit) {
                if($objForm == null)
                    $objForm = $this->getGroupMemberForm($objGroup);

                $arrFolder = $this->objToolkit->getLayoutFolder($objForm->renderForm(getLinkAdminHref($this->getArrModule("modul"), "addUserToGroup")), $this->getLang("group_add_user"));
                $strReturn .= $this->objToolkit->getFieldset($arrFolder[1], $arrFolder[0]);

            }


            $objIterator = new class_array_section_iterator($objSourceGroup->getNumberOfMembers());
            $objIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
            $objIterator->setArraySection($objSourceGroup->getUserIdsForGroup($objIterator->calculateStartPos(), $objIterator->calculateEndPos()));

            $strReturn .= $this->objToolkit->listHeader();
            $intI = 0;
            foreach($objIterator as $strSingleMemberId) {
                $objSingleMember = new class_module_user_user($strSingleMemberId);

                $strAction = "";
                if($objUsersources->getUsersource($objGroup->getStrSubsystem())->getMembersEditable() && $bitRenderEdit) {
                    $strAction .= $this->objToolkit->listDeleteButton(
                        $objSingleMember->getStrUsername() . " (" . $objSingleMember->getStrForename() . " " . $objSingleMember->getStrName() . ")",
                        $this->getLang("mitglied_loeschen_frage"),
                        class_link::getLinkAdminHref($this->getArrModule("modul"), "groupMemberDelete", "&groupid=" . $objGroup->getSystemid() . "&userid=" . $objSingleMember->getSystemid())
                    );
                }
                $strReturn .= $this->objToolkit->genericAdminList($objSingleMember->getSystemid(), $objSingleMember->getStrDisplayName(), getImageAdmin("icon_user"), $strAction, $intI++);
            }
            $strReturn .= $this->objToolkit->listFooter() . $this->objToolkit->getSimplePageview($objIterator, "user", "groupMember", "systemid=" . $this->getSystemid());
        }
        return $strReturn;
    }

    /**
     * Adds a single user to a group
     *
     * @return string
     * @permissions edit
     */
    protected function actionAddUserToGroup() {
        $objGroup = new class_module_user_group($this->getSystemid());
        //validate possible blocked groups
        if(!$this->isGroupEditable($objGroup))
            return $this->getLang("commons_error_permissions");

        $objForm = $this->getGroupMemberForm($objGroup);
        if(!$objForm->validateForm())
            return $this->actionGroupMember($objForm);


        $objUser = new class_module_user_user($objForm->getField("addusertogroup_user")->getStrValue());
        $objSourceGroup = $objGroup->getObjSourceGroup();

        $objSourceGroup->addMember($objUser->getObjSourceUser());
        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "groupMember", "&systemid=".$objGroup->getSystemid()));
        return "";
    }

    /**
     * @param class_module_user_group $objGroup
     *
     * @return class_admin_formgenerator
     */
    private function getGroupMemberForm(class_module_user_group $objGroup) {
        $objForm = new class_admin_formgenerator("addUserToGroup", $objGroup);
        $objForm->addField(new class_formentry_user("addUserToGroup", "user", null))->setStrValue($this->getParam("addusertogroup_user_id"))->setBitMandatory(true)->setStrLabel($this->getLang("user_username"));
        return $objForm;
    }

    /**
     * Deletes a membership
     *
     * @throws class_exception
     * @return string "" in case of success
     * @permissions delete
     */
    protected function actionGroupMemberDelete() {
        $strReturn = "";
        $objGroup = new class_module_user_group($this->getParam("groupid"));
        //validate possible blocked groups
        if(!$this->isGroupEditable($objGroup))
            return $this->getLang("commons_error_permissions");

        $objUser = new class_module_user_user($this->getParam("userid"));
        if($objGroup->getObjSourceGroup()->removeMember($objUser->getObjSourceUser())) {
            $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "groupMember", "systemid=" . $this->getParam("groupid")));
        }
        else {
            throw new class_exception($this->getLang("member_delete_error"), class_exception::$level_ERROR);
        }

        return $strReturn;
    }


    /**
     * Deletes a group and all memberships
     *
     * @throws class_exception
     * @return void
     * @permissions delete
     */
    protected function actionGroupDelete() {
        //Delete memberships
        $objGroup = new class_module_user_group($this->getSystemid());

        //validate possible blocked groups
        if(!$this->isGroupEditable($objGroup))
            throw new class_exception($this->getLang("gruppe_loeschen_fehler"), class_exception::$level_ERROR);

        //delete group
        if($objGroup->deleteObject()) {
            $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "groupList"));
        }
        else {
            throw new class_exception($this->getLang("gruppe_loeschen_fehler"), class_exception::$level_ERROR);
        }
    }

    /**
     * Shows a form to manage memberships of a user in groups
     *
     * @return string
     * @permissions edit
     */
    protected function actionEditMemberships() {
        $strReturn = "";
        $this->setArrModuleEntry("template", "/folderview.tpl");
        //open the form
        $strReturn .= $this->objToolkit->formHeader(class_link::getLinkAdminHref($this->getArrModule("modul"), "saveMembership"));
        //Create a list of checkboxes
        $objUser = new class_module_user_user($this->getSystemid());

        $strReturn .= $this->objToolkit->formHeadline($this->getLang("user_memberships") . "\"" . $objUser->getStrUsername() . "\"");

        //Collect groups from the same source
        $objUsersources = new class_module_user_sourcefactory();
        $objSourcesytem = $objUsersources->getUsersource($objUser->getStrSubsystem());

        $arrGroups = $objSourcesytem->getAllGroupIds();
        $arrUserGroups = $objUser->getArrGroupIds();

        //to avoid privilege escalation, the admin-group has to be treated in a special manner
        //only render the group, if the current user is member of this group
        $bitShowAdmin = false;
        if(in_array(_admins_group_id_, $this->objSession->getGroupIdsAsArray())) {
            $bitShowAdmin = true;
        }

        $objConfig = class_config::getInstance("blockedgroups.php");
        $arrBlockedGroups = explode(",", $objConfig->getConfig("blockedgroups"));

        foreach($arrGroups as $strSingleGroup) {

            $objSingleGroup = new class_module_user_group($strSingleGroup);
            if(!$this->isGroupEditable($objSingleGroup))
                continue;

            if(in_array($strSingleGroup, $arrUserGroups)) {
                //user in group, checkbox checked
                $strReturn .= $this->objToolkit->formInputCheckbox($objSingleGroup->getSystemid(), $objSingleGroup->getStrName(), true);
            }
            else {
                //User not yet in group, checkbox unchecked
                $strReturn .= $this->objToolkit->formInputCheckbox($objSingleGroup->getSystemid(), $objSingleGroup->getStrName());
            }
        }

        $strReturn .= $this->objToolkit->formInputHidden("systemid", $this->getSystemid());
        $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
        $strReturn .= $this->objToolkit->formClose();
        return $strReturn;
    }


    /**
     * Saves the memberships passed by param
     *
     * @return string "" in case of success
     * @permissions edit
     */
    protected function actionSaveMembership() {

        $objUser = new class_module_user_user($this->getSystemid());
        $objUsersources = new class_module_user_sourcefactory();
        $objSourcesytem = $objUsersources->getUsersource($objUser->getStrSubsystem());

        $arrGroups = $objSourcesytem->getAllGroupIds();
        $arrUserGroups = $objUser->getArrGroupIds();

        //validate possible blocked groups
        $objConfig = class_config::getInstance("blockedgroups.php");
        $arrBlockedGroups = explode(",", $objConfig->getConfig("blockedgroups"));

        $bitIsSuperAdmin = in_array(_admins_group_id_, $this->objSession->getGroupIdsAsArray());

        //Searching for groups to enter
        foreach($arrGroups as $strSingleGroup) {

            $objGroup = new class_module_user_group($strSingleGroup);
            //skipped for blocked groups, those won't be updated
            if(!$this->isGroupEditable($objGroup))
                continue;


            if($this->getParam($strSingleGroup) != "") {

                //add the user to this group
                if(!in_array($strSingleGroup, $arrUserGroups)) {
                    $objGroup->getObjSourceGroup()->addMember($objUser->getObjSourceUser());
                }
                else {
                    //user is already in the group, remove the marker
                    foreach($arrUserGroups as $strKey => $strValue) {
                        if($strValue == $strSingleGroup) {
                            $arrUserGroups[$strKey] = null;
                        }
                    }
                }

            }
        }

        //check, if the current user is member of the admin-group.
        //if not, remain the admin-group as-is
        if(!$bitIsSuperAdmin) {
            $intKey = array_search(_admins_group_id_, $arrUserGroups);
            if($intKey !== false) {
                $arrUserGroups[$intKey] = null;
            }

            foreach($arrBlockedGroups as $strOneGroup) {
                $intKey = array_search($strOneGroup, $arrUserGroups);
                if($intKey !== false) {
                    $arrUserGroups[$intKey] = null;
                }
            }
        }

        //loop the users' list in order to remove unwanted relations
        foreach($arrUserGroups as $strValue) {
            if(validateSystemid($strValue)) {
                $objGroup = new class_module_user_group($strValue);
                $objGroup->getObjSourceGroup()->removeMember($objUser->getObjSourceUser());
            }
        }

        $this->adminReload(class_link::getLinkAdminHref($this->getArrModule("modul"), "list", "&peClose=1&blockAction=1"));
    }


    /**
     * returns a list of the last logins
     *
     * @return string
     * @autoTestable
     * @permissions right1
     */
    protected function actionLoginLog() {
        $strReturn = "";
        //fetch log-rows
        $objLogbook = new class_module_user_log();
        $objIterator = new class_array_section_iterator($objLogbook->getLoginLogsCount());
        $objIterator->setIntElementsPerPage(_user_log_nrofrecords_);
        $objIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objIterator->setArraySection(class_module_user_log::getLoginLogs($objIterator->calculateStartPos(), $objIterator->calculateEndPos()));

        $arrRows = array();
        foreach($objIterator as $arrLogRow) {
            $arrSingleRow = array();
            $arrSingleRow[] = $arrLogRow["user_log_sessid"];
            $arrSingleRow[] = ($arrLogRow["user_username"] != "" ? $arrLogRow["user_username"] : $arrLogRow["user_log_userid"]);
            $arrSingleRow[] = dateToString(new class_date($arrLogRow["user_log_date"]));
            $arrSingleRow[] = $arrLogRow["user_log_enddate"] != "" ? dateToString(new class_date($arrLogRow["user_log_enddate"])) : "";
            $arrSingleRow[] = ($arrLogRow["user_log_status"] == 0 ? $this->getLang("login_status_0") : $this->getLang("login_status_1"));
            $arrSingleRow[] = $arrLogRow["user_log_ip"];

            $strUtraceLinkMap = "href=\"http://www.utrace.de/ip-adresse/".$arrLogRow["user_log_ip"]."\" target=\"_blank\"";
            $strUtraceLinkText = "href=\"http://www.utrace.de/whois/".$arrLogRow["user_log_ip"]."\" target=\"_blank\"";

            if($arrLogRow["user_log_ip"] != "127.0.0.1" && $arrLogRow["user_log_ip"] != "::1") {
                $arrSingleRow[] = $this->objToolkit->listButton(class_link::getLinkAdminManual($strUtraceLinkMap, "", $this->getLang("login_utrace_showmap"), "icon_earth"))
                    ." ".$this->objToolkit->listButton(class_link::getLinkAdminManual($strUtraceLinkText, "", $this->getLang("login_utrace_showtext"), "icon_text"));
            }
            else {
                $arrSingleRow[] = $this->objToolkit->listButton(class_adminskin_helper::getAdminImage("icon_earthDisabled", $this->getLang("login_utrace_noinfo")))." ".
                    $this->objToolkit->listButton(class_adminskin_helper::getAdminImage("icon_textDisabled", $this->getLang("login_utrace_noinfo")));
            }

            $arrRows[] = $arrSingleRow;
        }

        //Building the surrounding table
        $arrHeader = array();
        $arrHeader[] = $this->getLang("login_sessid");
        $arrHeader[] = $this->getLang("login_user");
        $arrHeader[] = $this->getLang("login_logindate");
        $arrHeader[] = $this->getLang("login_logoutdate");
        $arrHeader[] = $this->getLang("login_status");
        $arrHeader[] = $this->getLang("login_ip");
        $arrHeader[] = $this->getLang("login_utrace");
        //and fetch the table
        $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrRows);
        $strReturn .= $this->objToolkit->getSimplePageview($objIterator, "user", "loginlog");

        return $strReturn;
    }


    /**
     * Creates a browser-like view of the users available
     *
     * @return string
     * @permissions view
     */
    protected function actionUserBrowser() {
        $this->setArrModuleEntry("template", "/folderview.tpl");
        $strReturn = "";
        $strFormElement = $this->getParam("form_element");
        if($this->getSystemid() == "") {
            //show groups
            $arrUsers = class_module_user_group::getObjectList();
            $strReturn .= $this->objToolkit->listHeader();
            $intI = 0;
            foreach($arrUsers as $objSingleGroup) {
                $strAction = "";
                $strAction .= $this->objToolkit->listButton(
                    class_link::getLinkAdmin(
                        "user",
                        "userBrowser",
                        "&form_element=".$this->getParam("form_element")."&systemid=".$objSingleGroup->getSystemid()."&filter=".$this->getParam("filter"),
                        $this->getLang("user_browser_show"),
                        $this->getLang("user_browser_show"),
                        "icon_folderActionOpen"
                    )
                );

                if($this->getParam("allowGroup") == "1") {
                    $strAction .= $this->objToolkit->listButton(
                        "<a href=\"#\" title=\"".$this->getLang("group_accept")."\" rel=\"tooltip\" onclick=\"KAJONA.admin.folderview.selectCallback([['". strFormElement . "', '".addslashes($objSingleGroup->getStrName())."'], ['".$strFormElement."_id', '".$objSingleGroup->getSystemid()."']]);\">".getImageAdmin("icon_accept")
                    );
                }

                $strReturn .= $this->objToolkit->simpleAdminList($objSingleGroup, $strAction, $intI++);

            }
        }
        else {
            //show members of group
            $objGroup = new class_module_user_group($this->getSystemid());
            $arrUsers = $objGroup->getObjSourceGroup()->getUserIdsForGroup();
            $strReturn .= $this->objToolkit->listHeader();
            $intI = 0;

            $strReturn .= $this->objToolkit->genericAdminList(
                generateSystemid(),
                "",
                "",
                $this->objToolkit->listButton(
                    class_link::getLinkAdmin(
                        $this->getArrModule("modul"),
                        "userBrowser",
                        "&form_element=" . $this->getParam("form_element") . "&filter=" . $this->getParam("filter") . "&allowGroup=" . $this->getParam("allowGroup"),
                        $this->getLang("user_list_parent"),
                        $this->getLang("user_list_parent"),
                        "icon_folderActionLevelup"
                    )
                ),
                $intI++
            );
            foreach($arrUsers as $strSingleUser) {
                $objSingleUser = new class_module_user_user($strSingleUser);

                $strAction = "";
                if($objSingleUser->getIntActive() == 0 || ($this->getParam("filter") == "current" && $objSingleUser->getSystemid() == $this->objSession->getUserID())) {
                    $strAction .= $this->objToolkit->listButton(getImageAdmin("icon_acceptDisabled"));
                }
                else {
                    $strAction .= $this->objToolkit->listButton(
                        "<a href=\"#\" title=\"" . $this->getLang("user_accept") . "\" rel=\"tooltip\" onclick=\"KAJONA.admin.folderview.selectCallback([['" . $strFormElement . "', '" . addslashes($objSingleUser->getStrUsername()) . "'], ['" . $strFormElement . "_id', '" . $objSingleUser->getSystemid() . "']]);\">" . getImageAdmin("icon_accept")
                    );
                }
                $strReturn .= $this->objToolkit->simpleAdminList($objSingleUser, $strAction, $intI++);

            }
        }

        return $strReturn;

    }

    /**
     * @return string
     * @throws class_exception
     * @permissions edit
     */
    protected function actionSwitchToUser() {
        $strReturn = "";
        if(class_module_system_module::getModuleByName("system")->rightEdit() && in_array(_admins_group_id_, $this->objSession->getGroupIdsAsArray())) {

            //reset the aspect
            $strAddon = "";
            $objDefaultAspect = class_module_system_aspect::getDefaultAspect();

            if($objDefaultAspect !== null) {
                $strAddon = "&aspect=" . $objDefaultAspect->getSystemid();
            }

            $objNewUser = new class_module_user_user($this->getSystemid());
            if($this->objSession->switchSessionToUser($objNewUser)) {
                class_admin_helper::flushActionNavigationCache();
                $this->adminReload(class_link::getLinkAdminHref("dashboard", "", $strAddon));
                return "";
            }
            else {
                throw new class_exception("session switch failed", class_exception::$level_ERROR);
            }
        }
        else {
            $strReturn .= $this->getLang("commons_error_permissions");
        }

        return $strReturn;
    }


    /**
     * Checks, if two passwords are equal
     *
     * @param string $strPass1
     * @param string $strPass2
     *
     * @return bool
     */
    protected function checkPasswords($strPass1, $strPass2) {
        return ($strPass1 == $strPass2);
    }

    /**
     * Checks, if a username is existing or not
     *
     * @param string $strName
     *
     * @return bool
     */
    protected function checkUsernameNotExisting($strName) {
        $arrUsers = class_module_user_user::getAllUsersByName($strName);
        return (count($arrUsers) == 0);
    }

    /**
     * @param class_admin_formgenerator $objForm
     *
     * @return bool
     */
    protected function checkAdditionalNewData(class_admin_formgenerator $objForm) {

        $arrParams = class_carrier::getAllParams();
        $bitPass = true;
        if(isset($arrParams["user_pass"])) {
            $bitPass = $this->checkPasswords($this->getParam("user_pass"), $this->getParam("user_pass2"));
            if(!$bitPass) {
                $objForm->addValidationError("user_password", $this->getLang("required_password_equal"));
            }
        }

        $bitUsername = $this->checkUsernameNotExisting($this->getParam("user_username"));
        if(!$bitUsername) {
            $objForm->addValidationError("user_username", $this->getLang("required_user_existing"));
        }

        return $bitPass && $bitUsername;
    }

    /**
     * @param class_admin_formgenerator $objForm
     *
     * @return bool
     */
    protected function checkAdditionalEditData(class_admin_formgenerator $objForm) {

        $arrParams = class_carrier::getAllParams();
        $bitPass = true;
        if(isset($arrParams["user_pass"])) {
            $bitPass = $this->checkPasswords($this->getParam("user_pass"), $this->getParam("user_pass2"));
            if(!$bitPass) {
                $objForm->addValidationError("password", $this->getLang("required_password_equal"));
            }
        }

        $arrUsers = class_module_user_user::getAllUsersByName($this->getParam("user_username"));
        if(count($arrUsers) > 0) {
            $objUser = $arrUsers[0];
            if($objUser->getSystemid() != $this->getSystemid()) {
                $objForm->addValidationError("user_username", $this->getLang("required_user_existing"));
                $bitPass = false;
            }
        }

        return $bitPass;
    }


    /**
     * Returns a list of users and/or groups matching the passed query.
     *
     * @return string
     * @xml
     * @permissions view
     */
    protected function actionGetUserByFilter() {
        $strFilter = $this->getParam("filter");

        $arrElements = array();
        $objSource = new class_module_user_sourcefactory();

        if($this->getParam("user") == "true") {
            $arrElements = $objSource->getUserlistByUserquery($strFilter);
        }

        if($this->getParam("group") == "true") {
            $arrElements = array_merge($arrElements, $objSource->getGrouplistByQuery($strFilter));
        }

        usort($arrElements, function($objA, $objB) {
            if($objA instanceof class_module_user_user) {
                $strA = $objA->getStrUsername();
            }
            else {
                $strA = $objA->getStrName();
            }

            if($objB instanceof class_module_user_user) {
                $strB = $objB->getStrUsername();
            }
            else {
                $strB = $objB->getStrName();
            }

            return strcmp(strtolower($strA), strtolower($strB));
        });


        $arrReturn = array();
        foreach($arrElements as $objOneElement) {

            if($this->getParam("block") == "current" && $objOneElement->getSystemid() == $this->objSession->getUserID()) {
                continue;
            }

            $arrEntry = array();

            if($objOneElement instanceof class_module_user_user) {
                $arrEntry["title"] = $objOneElement->getStrUsername() . " (" . $objOneElement->getStrName() . ", " . $objOneElement->getStrForename() . " )";
                $arrEntry["label"] = $objOneElement->getStrUsername() . " (" . $objOneElement->getStrName() . ", " . $objOneElement->getStrForename() . " )";
                $arrEntry["value"] = $objOneElement->getStrUsername() . " (" . $objOneElement->getStrName() . ", " . $objOneElement->getStrForename() . " )";
                $arrEntry["systemid"] = $objOneElement->getSystemid();
                $arrEntry["icon"] = class_adminskin_helper::getAdminImage("icon_user");
            }
            else if($objOneElement instanceof class_module_user_group) {
                $arrEntry["title"] = $objOneElement->getStrName();
                $arrEntry["value"] = $objOneElement->getStrName();
                $arrEntry["label"] = $objOneElement->getStrName();
                $arrEntry["systemid"] = $objOneElement->getSystemid();
                $arrEntry["icon"] = class_adminskin_helper::getAdminImage("icon_group");
            }

            $arrReturn[] = $arrEntry;
        }

        class_response_object::getInstance()->setStResponseType(class_http_responsetypes::STR_TYPE_JSON);
        return json_encode($arrReturn);
    }

}
