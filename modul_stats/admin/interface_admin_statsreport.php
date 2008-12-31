<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2009 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                              *
********************************************************************************************************/

/**
 * Interface for the admin-plugins of the stats-reports
 *
 * @package modul_stats
 */
interface interface_admin_statsreports {

    /**
     * Contructor, used to init the plugin
     *
     * @param obj $objDB
     * @param obj $objToolkit
     * @param obj $objTexts
     */
    public function __construct($objDB, $objToolkit, $objTexts);

    /**
     * Method used to fetch the title of the report.
     * This title will be used for the admin-navigation
     *
     */
    public function getReportTitle();

    /**
     * This method is being called, if the report should be displayed.
     * Use the passed $objToolkit to generate HTML-Output
     *
     */
    public function getReport();

    /**
     * This one returns the command that should be used to load THIS plugin.
     * Make sure NOT to conflict with other plugins!
     *
     */
    public function getReportCommand();

    /**
     * Setter for the startdate of the interval
     *
     * @param int $intStartDate
     */
    public function setStartDate($intStartDate);

    /**
     * Setter for the enddate of the interval
     *
     * @param int $intEndDate
     */
    public function setEndDate($intEndDate);

	/**
	 * Returns the url to a graph generated by the plugin.
	 * If the reports generates more then one graph, an array of url can be returned
	 * If the plugin can't create a graph, an empty string should be returned instead.
	 *
	 */
    public function getReportGraph();

    /**
     * This method returns, whether the report is able to report in intervals, or not.
     * If so, a interval-choose is added to the dateSelector 
     *
     */
    public function isIntervalable();
    
    /**
     * Used to set the interval. Just to be used, if isIntervalable() returns true
     *
     * @param int $intInterval
     */
    public function setInterval($intInterval);
    
}
?>