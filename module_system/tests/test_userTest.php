<?php

require_once (__DIR__."/../../module_system/system/class_testbase.php");

class class_test_user extends class_testbase  {



    public function test() {
        $objDB = class_carrier::getInstance()->getObjDB();

        echo "\tmodul_user...\n";

        //blank system - one user should have been created

        echo "\tcheck number of users installed...\n";
        $arrUserInstalled = class_module_user_user::getObjectList();
        $intStartUsers = count($arrUserInstalled);
        echo "\t ...found ".$intStartUsers." users.\n";

        echo "\tcheck number of groups installed...\n";
        $arrGroupsInstalled = class_module_user_group::getObjectList();
        $intStartGroups = count($arrGroupsInstalled);
        echo "\t ...found ".$intStartUsers." users.\n";


        echo "\tcreate 10 users using the model...\n";
        $arrUsersCreated = array();
        for($intI =0; $intI < 10; $intI++) {
            $objUser = new class_module_user_user();
            //$objUser->setStrEmail(generateSystemid()."@".generateSystemid()."de");
            $strUsername = "user_".generateSystemid();
            $objUser->setStrUsername($strUsername);
            $objUser->updateObjectToDb();
            $arrUsersCreated[] = $objUser->getSystemid();
            $strID = $objUser->getSystemid();
            $objDB->flushQueryCache();
            $objUser = new class_module_user_user($strID);
            $this->assertEquals($objUser->getStrUsername(), $strUsername, __FILE__." checkNameOfUserCreated");
        }
        $arrUserInstalled = class_module_user_user::getObjectList();
        $this->assertEquals(count($arrUserInstalled), (10+$intStartUsers), __FILE__." checkNrOfUsersCreatedByModel");



        echo "\tcreate 10 groups using the model...\n";
        $arrGroupsCreated = array();
        for($intI =0; $intI < 10; $intI++) {
            $objGroup = new class_module_user_group();
            $strName = "name_".generateSystemid();
            $objGroup->setStrName($strName);
            $objGroup->updateObjectToDb();
            $strID = $objGroup->getSystemid();
            $arrGroupsCreated[] = $objGroup->getSystemid();
            $objDB->flushQueryCache();
            $objGroup = new class_module_user_group($strID);
            $this->assertEquals($objGroup->getStrName(), $strName, __FILE__." checkNameOfGroupCreated");
        }
        $arrGroupsInstalled = class_module_user_group::getObjectList();
        $this->assertEquals(count($arrGroupsInstalled), (10+$intStartGroups), __FILE__." checkNrOfGroupsByModel");



        echo "\tdeleting users created...\n";
        foreach($arrUsersCreated as $strOneUser) {
            echo "\t\tdeleting user ".$strOneUser."...\n";
            $objUser = new class_module_user_user($strOneUser);
            $objUser->deleteObject();
        }
        $objDB->flushQueryCache();


        echo "\tcheck number of users installed...\n";
        $arrUserInstalled = class_module_user_user::getObjectList();
        $this->assertEquals(count($arrUserInstalled), $intStartUsers, __FILE__." checkNrOfUsers");



        echo "\tdeleting groups created...\n";
        foreach($arrGroupsCreated as $strOneGroup) {
            $objOneGroup = new class_module_user_group($strOneGroup);
            $objOneGroup->deleteObject();
        }
        $objDB->flushQueryCache();

        echo "\tcheck number of groups installed...\n";
        $arrGroupsInstalled = class_module_user_group::getObjectList();
        $this->assertEquals(count($arrGroupsInstalled), $intStartGroups, __FILE__." checkNrOfGroups");

        echo "\ttest group membership handling...\n";
        $objGroup = new class_module_user_group();
        $objGroup->setStrName("AUTOTESTGROUP");
        $objGroup->updateObjectToDb();

        echo "\tadding 10 members to group...\n";
        for ($intI = 0; $intI <= 10; $intI++) {
            $objUser = new class_module_user_user();
            $objUser->setStrUsername("AUTOTESTUSER_".$intI);
            //$objUser->setStrEmail("autotest_".$intI."@kajona.de");
            $objUser->updateObjectToDb();
            //add user to group
            $objGroup->getObjSourceGroup()->addMember($objUser->getObjSourceUser());
            $arrUsersInGroup = $objGroup->getObjSourceGroup()->getUserIdsForGroup();
            $this->assertTrue(in_array($objUser->getSystemid(), $arrUsersInGroup), __FILE__." checkUserInGroup");
            $this->assertEquals(count($arrUsersInGroup), 1+$intI, __FILE__." checkNrOfUsersInGroup");
            $objDB->flushQueryCache();
        }

        echo "\tdeleting groups & users\n";
        foreach($objGroup->getObjSourceGroup()->getUserIdsForGroup() as $strOneUser) {
            $objOneUser = new class_module_user_user($strOneUser);
            $objOneUser->deleteObject();
        }
        $objGroup->deleteObject();


        $objDB->flushQueryCache();
        echo "\tcheck number of users installed is same as at beginning...\n";
        $arrUserInstalled = class_module_user_user::getObjectList();
        $this->assertEquals(count($arrUserInstalled), $intStartUsers, __FILE__." checkNrOfUsersAtEnd");

        echo "\tcheck number of groups installed is same as at beginning...\n";
        $arrGroupsInstalled = class_module_user_group::getObjectList();
        $this->assertEquals(count($arrGroupsInstalled), $intStartGroups, __FILE__." checkNrOfGrpupsAtEnd");

    }

}

