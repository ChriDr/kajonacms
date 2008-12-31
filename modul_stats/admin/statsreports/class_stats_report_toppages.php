<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2009 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                              *
********************************************************************************************************/

//Interface
include_once(_adminpath_."/interface_admin_statsreport.php");

/**
 * This plugin creates a view common numbers, such as "user online" oder "total pagehits"
 *
 * @package modul_stats
 */
class class_stats_report_toppages implements interface_admin_statsreports {

	//class vars
	private $intDateStart;
	private $intDateEnd;
	private $intInterval;

	private $objTexts;
	private $objToolkit;
	private $objDB;

	private $arrModule;

	/**
	 * Constructor
	 *
	 */
	public function __construct($objDB, $objToolkit, $objTexts) {
		$this->arrModule["name"] 			= "modul_stats_reports_toppages";
		$this->arrModule["author"] 			= "sidler@mulchprod.de";
		$this->arrModule["moduleId"] 		= _stats_modul_id_;
		$this->arrModule["table"] 		    = _dbprefix_."stats_data";
		$this->arrModule["modul"]			= "stats";

		$this->objTexts = $objTexts;
		$this->objToolkit = $objToolkit;
		$this->objDB = $objDB;
	}

	public function setEndDate($intEndDate) {
	    $this->intDateEnd = $intEndDate;
	}

	public function setStartDate($intStartDate) {
	    $this->intDateStart = $intStartDate;
	}

	public function getReportTitle() {
	    return  $this->objTexts->getText("topseiten", "stats", "admin");
	}

	public function getReportCommand() {
	    return "statsTopPages";
	}

	public function isIntervalable() {
	    return true;
	}

	public function setInterval($intInterval) {
	    $this->intInterval = $intInterval;
	}

	public function getReport() {
	    $strReturn = "";
        //Create Data-table
        $arrHeader = array();
        $arrValues = array();
        //Fetch data
		$arrPages = $this->getTopPages();

		//calc a few values
		$intSum = 0;
		foreach($arrPages as $arrOnePage)
			$intSum += $arrOnePage["anzahl"];

		$intI =0;
		foreach($arrPages as $arrOnePage) {
			//Escape?
			if($intI >= _stats_anzahl_liste_)
				break;
            $arrValues[$intI] = array();
			$arrValues[$intI][] = $intI+1;
			$arrValues[$intI][] = $arrOnePage["name"];
			$arrValues[$intI][] = $arrOnePage["language"];
			$arrValues[$intI][] = $arrOnePage["anzahl"];
			$arrValues[$intI][] = $this->objToolkit->percentBeam($arrOnePage["anzahl"] / $intSum*100);
			$intI++;
		}

		//HeaderRow
		$arrHeader[] = "#";
		$arrHeader[] = $this->objTexts->getText("top_seiten_titel", "stats", "admin");
		$arrHeader[] = $this->objTexts->getText("top_seiten_language", "stats", "admin");
		$arrHeader[] = $this->objTexts->getText("top_seiten_gewicht", "stats", "admin");
		$arrHeader[] = $this->objTexts->getText("anteil", "stats", "admin");

		$strReturn .= $this->objToolkit->dataTable($arrHeader, $arrValues);

		return $strReturn;
	}

	/**
	 * Returns the pages and their hits
	 *
	 * @return mixed
	 */
	public function getTopPages() {
		$strQuery = "SELECT stats_page as name, count(*) as anzahl, stats_language as language
						FROM ".$this->arrModule["table"]."
						WHERE stats_date >= ".(int)$this->intDateStart."
								AND stats_date <= ".(int)$this->intDateEnd."
						GROUP BY stats_page, stats_language
							ORDER BY anzahl desc";

		return $this->objDB->getArraySection($strQuery, 0, _stats_anzahl_liste_ -1);
	}

	public function getReportGraph() {
	    $arrReturn = array();
        //collect data
        $arrPages = $this->getTopPages();

		$arrGraphData = array();
		$arrPlots = array();
		$intCount = 1;
		foreach ($arrPages as $strName => $arrOnePage) {
		    $arrGraphData[$intCount] = $arrOnePage["anzahl"];
		    if($intCount <= 6)
		      $arrPlots[$arrOnePage["name"]] = array();

		    if($intCount++ >= 9)
		      break;
		}

        if(count($arrGraphData) > 1) {
    	    //generate a bar-chart
    	    include_once(_systempath_."/class_graph.php");
    	    $objGraph = new class_graph();
    	    $objGraph->createBarChart($arrGraphData, 715, 200, false);
    	    $objGraph->setXAxisLabelAngle(0);
    	    $objGraph->setStrXAxisTitle($this->objTexts->getText("top_seiten_titel", "stats", "admin"));
    	    $objGraph->setStrYAxisTitle($this->objTexts->getText("top_seiten_gewicht", "stats", "admin"));
    	    //$objGraph->setMargin(40, 5, 5, 10);
    	    $strFilename = "/portal/pics/cache/stats_toppages.png";
    	    $objGraph->saveGraph($strFilename);
    		$arrReturn[] =  _webpath_.$strFilename;

    		//--- XY-Plot -----------------------------------------------------------------------------------
    		//calc number of plots

    		$arrTickLabels = array();

    		$intGlobalEnd = $this->intDateEnd;
    		$intGlobalStart = $this->intDateStart;

    		$this->intDateEnd = $this->intDateStart + 60*60*24*$this->intInterval;

    		$intCount = 0;
    		while($this->intDateStart <= $intGlobalEnd) {
    		    $arrPagesData = $this->getTopPages();
    		    //init plot array for this period
    		    $arrTickLabels[$intCount] = date("d.m.", $this->intDateStart);
    		    foreach($arrPlots as $strPage => &$arrOnePlot) {
    		        $arrOnePlot[$intCount] = 0;
    		        foreach ($arrPagesData as $intKey => $arrOnePage) {
    		            if($arrOnePage["name"] == $strPage) {
    		                $arrOnePlot[$intCount] += $arrOnePage["anzahl"];
    		            }
    		        }
    		    }
    		    //increase start & end-date
    		    $this->intDateStart = $this->intDateEnd;
    		    $this->intDateEnd = $this->intDateStart + 60*60*24*$this->intInterval;
    		    $intCount++;
    		}
    		//create graph
    		if($intCount > 1) {
        		$arrColors = array("", "red", "blue", "green", "yellow", "purple", "black");
        		$objGraph = new class_graph();
        		$objGraph->createLinePlotChart(715, 160);
        		$objGraph->setXAxisTickLabels($arrTickLabels, ceil($intCount / 12));
        		foreach($arrPlots as $arrPlotName => $arrPlotData) {
        		    $objGraph->addLinePlot($arrPlotData, next($arrColors), $arrPlotName);
        		}
        		$strFilename = "/portal/pics/cache/stats_toppages_plot.png";
                $objGraph->saveGraph($strFilename);
        		$arrReturn[] = _webpath_.$strFilename;
    		}
    		//reset global dates
    		$this->intDateEnd = $intGlobalEnd;
    		$this->intDateStart = $intGlobalStart;

    		return $arrReturn;
        }
        else
            return "";
	}


}
?>