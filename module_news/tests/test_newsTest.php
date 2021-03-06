<?php

require_once (__DIR__ . "/../../module_system/system/class_testbase.php");

class class_test_news extends class_testbase  {



    public function testCreateDelete() {
        echo "creating a news..\n";
        
        $objNews = new class_module_news_news();
        $objNews->setStrTitle("autotest");
        $objNews->setStrIntro("autotest");
        $objNews->setStrText("autotest");
        
        $this->assertTrue($objNews->updateObjectToDb(), __FILE__." save news");
        
        echo "creating category...\n";
        $objCat = new class_module_news_category();
        $objCat->setStrTitle("autotest");
        $this->assertTrue($objCat->updateObjectToDb(), __FILE__." save cat");
        
        $this->flushDBCache();
        $this->assertEquals(0, count(class_module_news_category::getNewsMember($objNews->getSystemid())), __FILE__." check cats for news");
        $this->assertEquals(0, count(class_module_news_news::getObjectList($objCat->getSystemid())), __FILE__." check news for cat");
        
        
        
        echo "adding news to category..\n";
        $objNews->setArrCats(array($objCat->getSystemid() => "ss"));
        $objNews->setBitUpdateMemberships(true);
        $this->assertTrue($objNews->updateObjectToDb(), __FILE__." update news");
        
        $strNewsId = $objNews->getSystemid();
        $strCatId = $objCat->getSystemid();
        
        $this->flushDBCache();
        
        $objNews = new class_module_news_news($strNewsId);
        $objCat = new class_module_news_category($strCatId);
        
        $this->assertEquals(1, count(class_module_news_category::getNewsMember($objNews->getSystemid())), __FILE__." check cats for news");
        $this->assertEquals(1, count(class_module_news_news::getObjectList($objCat->getSystemid())), __FILE__." check news for cat");

        echo "deleting news...\n";
        $this->assertTrue($objNews->deleteObject(), __FILE__." delete news");
        
        $this->flushDBCache();
        $this->assertEquals(0, count(class_module_news_news::getObjectList($objCat->getSystemid())), __FILE__." check news for cat");
        
        $this->assertTrue($objCat->deleteObject(), __FILE__." delete cat");
    }
    
    
    
    
    public function testRssFeed() {
        echo "creating news & category..\n";
        
        $objNews = new class_module_news_news();
        $objNews->setStrTitle("autotest");
        $objNews->setStrIntro("autotest");
        $objNews->setStrText("autotest");
        $this->assertTrue($objNews->updateObjectToDb(), __FILE__." save news");
        
        
        $objNews2 = new class_module_news_news();
        $objNews2->setStrTitle("autotest2");
        $objNews2->setStrIntro("autotest2");
        $objNews2->setStrText("autotest2");
        $this->assertTrue($objNews2->updateObjectToDb(), __FILE__." save news");
        
        echo "creating category...\n";
        $objCat = new class_module_news_category();
        $objCat->setStrTitle("autotest");
        $this->assertTrue($objCat->updateObjectToDb(), __FILE__." save cat");
        $this->flushDBCache();
        
        echo "adding news to category..\n";
        $objNews->setArrCats(array($objCat->getSystemid() => "ss"));
        $objNews->setBitUpdateMemberships(true);
        $this->assertTrue($objNews->updateObjectToDb(), __FILE__." update news");
        $this->flushDBCache();
        
        
        echo "creating feed...\n";
        $objFeed = new class_module_news_feed();
        $objFeed->setStrTitle("testfeed");
        $objFeed->setStrCat($objCat->getSystemid());
        $objFeed->setStrUrlTitle("autotest");
        $objFeed->setStrPage("autotest");
        $objFeed->updateObjectToDb();
        
        $this->flushDBCache();
        
        $this->assertEquals(1, count(class_module_news_feed::getNewsList($objFeed->getStrCat())), __FILE__." check news for feed");
        $this->assertEquals(1, count(class_module_news_feed::getNewsList($objFeed->getStrCat(), 1)), __FILE__." check news for feed");
        
        
        
        echo "generating feed by creating a fake request...\n";
        
        $objNewsPortalXML = new class_module_news_portal_xml();
        $objNewsPortalXML->setParam("feedTitle", "autotest");
        $strFeed = $objNewsPortalXML->action("newsFeed");
        $this->assertTrue(uniStrpos($strFeed, "<title>autotest</title>") !== false, __FILE__." check rss feed");
        
        echo "parsing feed with xml parser...\n";
        $objXmlParser = new class_xml_parser();
        $objXmlParser->loadString($strFeed);
        $arrFeed = $objXmlParser->xmlToArray();
        $intNrOfNews = count($arrFeed["rss"][0]["channel"][0]["item"]);
        $this->assertEquals(1, $intNrOfNews, __FILE__." check items for feed");
        $strTitle = $arrFeed["rss"][0]["channel"][0]["item"][0]["title"][0]["value"];
        $this->assertEquals("autotest", $strTitle, __FILE__." check items-title for feed");
        
        
        echo "adding news to category..\n";
        $objNews2->setArrCats(array($objCat->getSystemid() => "ss2"));
        $objNews2->setBitUpdateMemberships(true);
        $this->assertTrue($objNews2->updateObjectToDb(), __FILE__." update news");
        $this->flushDBCache();
        
        
        
        $objNewsPortalXML = new class_module_news_portal_xml();
        $objNewsPortalXML->setParam("feedTitle", "autotest");
        $strFeed = $objNewsPortalXML->action("newsFeed");
        $this->assertTrue(uniStrpos($strFeed, "<title>autotest</title>") !== false, __FILE__." check rss feed");
        
        echo "parsing feed with xml parser...\n";
        $objXmlParser = new class_xml_parser();
        $objXmlParser->loadString($strFeed);
        $arrFeed = $objXmlParser->xmlToArray();
        //var_dump($arrFeed["rss"][0]["channel"][0]["item"]);
        $intNrOfNews = count($arrFeed["rss"][0]["channel"][0]["item"]);
        $this->assertEquals(2, $intNrOfNews, __FILE__." check items for feed");
        
        
        
        echo "deleting news & category...\n";
        $this->assertTrue($objNews->deleteObject(), __FILE__." delete news");
        $this->assertTrue($objNews2->deleteObject(), __FILE__." delete news");
        $this->assertTrue($objCat->deleteObject(), __FILE__." delete cat");
        $this->assertTrue($objFeed->deleteObject(), __FILE__." delete feed");
    }
    

}



