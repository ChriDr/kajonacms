<?php

require_once (__DIR__."/../../module_system/system/class_testbase.php");

class class_test_sortTest extends class_testbase  {


    function testSortOnDelete() {

        $objRootAspect = new class_module_system_aspect();
        $objRootAspect->setStrName("testroot");
        $objRootAspect->updateObjectToDb();

        /** @var class_module_system_aspect[] $arrAspects */
        $arrAspects = array();
        for($intI = 0; $intI < 100; $intI++) {
            $objAspect = new class_module_system_aspect();
            $objAspect->setStrName("autotest_".$intI);
            $objAspect->updateObjectToDb($objRootAspect->getSystemid());
            $arrAspects[] = $objAspect;
        }

        //delete the 5th element - massive queries required
        $intQueriesPre = class_db::getInstance()->getNumber();
        echo " Setting new position\n";
        $arrAspects[5]->deleteObject();

        $intQueriesPost = class_db::getInstance()->getNumber();
        echo "Queries: ".($intQueriesPost-$intQueriesPre)." \n";

        $objOrm = new class_orm_objectlist();
        $arrChilds = $objOrm->getObjectList("class_module_system_aspect", $objRootAspect->getSystemid());
        $this->assertEquals(count($arrChilds), 99);
        for($intI = 1; $intI <= 99; $intI++) {
            $this->assertEquals($arrChilds[$intI-1]->getIntSort(), $intI);
        }


        $objRootAspect->deleteObject();
    }



    function testTreeSortBehaviour() {

        $objDB = class_carrier::getInstance()->getObjDB();

        //test the setToPos
        echo "\tposition handling...\n";
        //create 10 test records
        $objSystemCommon = new class_module_system_common();
        //new base-node
        $strBaseNodeId = $objSystemCommon->createSystemRecord(0, "positionShiftTest");
        $arrNodes = array();
        for($intI = 1; $intI <= 10; $intI++) {
            $arrNodes[] = $objSystemCommon->createSystemRecord($strBaseNodeId, "positionShiftTest_".$intI);
        }

        //initial movings
        $objSystemCommon = new class_module_system_common($arrNodes[1]);
        $objSystemCommon->setPosition("upwards");
        $arrNodes = $objDB->getPArray("SELECT system_id FROM "._dbprefix_."system WHERE system_prev_id = ? ORDER BY system_sort ASC", array($strBaseNodeId));
        echo "\trelative shiftings...\n";
        //move the third to the first pos
        $objSystemCommon = new class_module_system_common($arrNodes[2]["system_id"]);
        $objSystemCommon->setPosition("upwards");
        $objSystemCommon->setPosition("upwards");
        $objSystemCommon->setPosition("upwards");
        //next one should be with no effect
        $objSystemCommon->setPosition("upwards");
        $objDB->flushQueryCache();
        $arrNodesAfter = $objDB->getPArray("SELECT system_id FROM "._dbprefix_."system WHERE system_prev_id = ? ORDER BY system_sort ASC", array($strBaseNodeId));

        $this->assertEquals($arrNodesAfter[0]["system_id"], $arrNodes[2]["system_id"], __FILE__." checkPositionShitftingByRelativeShift");
        $this->assertEquals($arrNodesAfter[1]["system_id"], $arrNodes[0]["system_id"], __FILE__." checkPositionShitftingByRelativeShift");
        $this->assertEquals($arrNodesAfter[2]["system_id"], $arrNodes[1]["system_id"], __FILE__." checkPositionShitftingByRelativeShift");

        //moving by set pos
        echo "\tabsolute shifting..\n";
        $objDB->flushQueryCache();
        $arrNodes = $objDB->getPArray("SELECT system_id FROM "._dbprefix_."system WHERE system_prev_id = ? ORDER BY system_sort ASC", array($strBaseNodeId));
        $objDB->flushQueryCache();
        $objSystemCommon = new class_module_system_common($arrNodes[2]["system_id"]);
        $objSystemCommon->setAbsolutePosition(1);
        $arrNodesAfter = $objDB->getPArray("SELECT system_id FROM "._dbprefix_."system WHERE system_prev_id = ? ORDER BY system_sort ASC", array($strBaseNodeId));
        $this->assertEquals($arrNodesAfter[0]["system_id"], $arrNodes[2]["system_id"], __FILE__." checkPositionShitftingByAbsoluteShift");
        $this->assertEquals($arrNodesAfter[1]["system_id"], $arrNodes[0]["system_id"], __FILE__." checkPositionShitftingByAbsoluteShift");
        $this->assertEquals($arrNodesAfter[2]["system_id"], $arrNodes[1]["system_id"], __FILE__." checkPositionShitftingByAbsoluteShift");
        //and back...
        $objDB->flushQueryCache();
        $objSystemCommon = new class_module_system_common($arrNodes[2]["system_id"]);
        $objSystemCommon->setAbsolutePosition(3);
        $objDB->flushQueryCache();
        $arrNodesAfter = $objDB->getPArray("SELECT system_id FROM "._dbprefix_."system WHERE system_prev_id = ? ORDER BY system_sort ASC", array($strBaseNodeId));
        $this->assertEquals($arrNodesAfter[0]["system_id"], $arrNodes[0]["system_id"], __FILE__." checkPositionShitftingByAbsoluteShift");
        $this->assertEquals($arrNodesAfter[1]["system_id"], $arrNodes[1]["system_id"], __FILE__." checkPositionShitftingByAbsoluteShift");
        $this->assertEquals($arrNodesAfter[2]["system_id"], $arrNodes[2]["system_id"], __FILE__." checkPositionShitftingByAbsoluteShift");

        //deleting all records created
        foreach ($arrNodes as $arrOneNode)
            $objSystemCommon->deleteSystemRecord($arrOneNode["system_id"]);
        $objSystemCommon->deleteSystemRecord($strBaseNodeId);
    }




}

