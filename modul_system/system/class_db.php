<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007 by Kajona, www.kajona.de                                                                   *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
* 																										*
* 	class_db.php																						*
* 	Class handling all access to the database															*
*																										*
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                            *
********************************************************************************************************/

/**
 * This class handles all traffic from and to the database and takes care of a correct tx-handling
 * CHANGE WITH CARE!
 *
 * @package modul_system
 */
class class_db {
    private $arrModule;
	private $objConfig = NULL;				    //Config-Objekt
	private $arrQueryCache = array();		    //Array to cache queries
	private $intNumber = 0;					    //Number of queries send to database
	private $intNumberCache = 0;			    //Number of queries returned from cache

	/**
	 * Instance of the db-driver defined in the configs
	 *
	 * @var interface_db_driver
	 */
	private $objDbDriver = null;                //An object of the db-driver defined in the configs
	private static $objDB = null;               //An object of this class

	/**
	 * The number of tranactions currently opened
	 *
	 * @var int
	 */
	private $intNumberOfOpenTransactions = 0;    //The number of tranactions opened

	/**
	 * Set to true, if a rollback is requested, but there are still open tx.
	 * In this case, the tx is rolled back, when the enclosing tx is finished
	 *
	 * @var bool
	 */
	private $bitCurrentTxIsDirty = false;


	/**
	 * Constructor
	 *
	 */
	private function __construct() 	{

	    $this->arrModule["moduleId"]     = _system_modul_id_;
	    $this->arrModule["author"]       = "sidler@mulchprod.de";


		$objCarrier = class_carrier::getInstance();
		$this->objConfig = $objCarrier->getObjConfig();

		$this->bitMagicQuotesGPC = (get_magic_quotes_gpc() == 0);

		//Load the defined db-driver
		$strDriver = $this->objConfig->getConfig("dbdriver");
		if($strDriver != "%%defaultdriver%%") {
    		//build a class-name & include the driver
    		$strFilename = "class_db_".$strDriver.".php";
    		$strClassname = "class_db_".$strDriver;

    		include_once(_systempath_."/db/".$strFilename);
    		$this->objDbDriver = new $strClassname();
		}
		else {
		    //Do not throw any exception here, otherwise an endless loop will exit with an overloaded stack frame
		    //throw new class_exception("No db-driver defined!", class_exception::$level_FATALERROR);
		}
	}


	/**
	 * Destructor.
	 * Handles the closing of remaining tx and closes the db-connection
	 */
	public function __destruct() {
	    if($this->intNumberOfOpenTransactions != 0) {
	        //something bad happend. rollback, plz
	        $this->objDbDriver->transactionRollback();
	        class_logger::getInstance()->addLogRow("Rolled back open transactions on deletion of current instance of class_db!", class_logger::$levelWarning);
	    }
	    if($this->objDbDriver !== null)
	        $this->objDbDriver->dbclose();
	}

	/**
	 * Method to get an instance of the db-class
	 *
	 * @return class_db
	 */
	public static function getInstance() {
		if(self::$objDB == null) {
			self::$objDB = new class_db();
		}

		return self::$objDB;
	}


	/**
	 * This method connects with the databse
	 */
	public function dbconnect() {
	    if($this->objDbDriver !== null) {
	        try {
		        $this->objDbDriver->dbconnect($this->objConfig->getConfig("dbhost"), $this->objConfig->getConfig("dbusername"), $this->objConfig->getConfig("dbpassword"), $this->objConfig->getConfig("dbname"), $this->objConfig->getConfig("dbport"));
	        }
	        catch (class_exception $objException) {
                $objException->processException();
	        }
	    }
	}

	/**
	 * Sending a query to the database
	 *
	 * @param string $strQuery
	 * @return bool
	 */
	public function _query($strQuery) {
		$bitReturn = false;

		if(_dblog_)
			$this->writeDbLog($strQuery);

		//Increasing the counter
		$this->intNumber++;

		if($this->objDbDriver != null) {
		  $bitReturn = $this->objDbDriver->_query($strQuery);
		}

		if(!$bitReturn)
		    $this->getError($strQuery);

		return $bitReturn;
	}


	/**
	 * Returns one row from a resultset
	 *
	 * @param string $strQuery
	 * @param int $intNr
	 * @param bool $bitCache
	 * @return array
	 */
	public function getRow($strQuery, $intNr = 0, $bitCache = true) {
			$arrTemp = $this->getArray($strQuery, $bitCache);
			if(count($arrTemp) > 0)
				return $arrTemp[$intNr];
			else
				return array();
	}


	/**
	 * Method to get an array of rows for a given query from the database
	 *
	 * @param string $strQuery
	 * @param bool $bitCache
	 * @return array
	 */
	public function getArray($strQuery, $bitCache = true) {
		$strQuery = $this->processQuery($strQuery);
		//Increasing global counter
		$this->intNumber++;

		if(defined("_system_use_dbcache_")) {
		    if(_system_use_dbcache_ == "false") {
		        $bitCache = false;
		    }
		}

		if($bitCache) {
			$strQueryMd5 = md5($strQuery);
			if(isset($this->arrQueryCache[$strQueryMd5])) {
				//Increasing Cache counter
				$this->intNumberCache++;
				return $this->arrQueryCache[$strQueryMd5];
			}
		}

		$intCounter = 0;
		$arrReturn = array();

		if(_dblog_)
			$this->writeDbLog($strQuery);

		if($this->objDbDriver != null) {
    		$arrReturn = $this->objDbDriver->getArray($strQuery);
    		if($arrReturn === false) {
    		    $this->getError($strQuery);
    		    return array();
    		}
    		if($bitCache)
    			$this->arrQueryCache[$strQueryMd5] = $arrReturn;
		}
		return $arrReturn;
	}

	/**
     * Returns just a part of a recordset, defined by the start- and the end-rows,
     * defined by the params.
     * <b>Note:</b> Use array-like counters, so the first row is startRow 0 whereas
     * the n-th row is the (n-1)th key!!!
     *
     * @param string $strQuery
     * @param int $intStart
     * @param int $intEnd
     * @return array
     */
    public function getArraySection($strQuery, $intStart, $intEnd, $bitCache = true) {
        //process query
        $strQuery = $this->processQuery($strQuery);
        //generate a hash-value
        $strQueryMd5 = md5($strQuery.$intStart."-".$intEnd);
        //Increasing global counter
		$this->intNumber++;

        if(defined("_system_use_dbcache_")) {
		    if(_system_use_dbcache_ == "false") {
		        $bitCache = false;
		    }
		}

		if($bitCache) {
			if(isset($this->arrQueryCache[$strQueryMd5])) {
				//Increasing Cache counter
				$this->intNumberCache++;
				return $this->arrQueryCache[$strQueryMd5];
			}
		}

		if(_dblog_)
			$this->writeDbLog($strQuery);

		if($this->objDbDriver != null) {
    		$arrReturn = $this->objDbDriver->getArraySection($strQuery, $intStart, $intEnd);
    		if($arrReturn === false) {
    		    $this->getError($strQuery);
    		    return array();
    		}
    		if($bitCache)
    			$this->arrQueryCache[$strQueryMd5] = $arrReturn;
		}

		return $arrReturn;
    }


	/**
	 * Writes a message to the dblog
	 *
	 * @param sring $strText
	 */
	private function writeDbLog($strText) {
	    $arrStack = debug_backtrace();
		$strText = date("G:i:s, d-m-y"). "\t\t".
		                  $arrStack[2]["file"]."\t Row ".$arrStack[2]["line"].", function ".$arrStack[2]["function"]."".
		                 "\r\n"
		              . $strText . "\r\n\r\n\r\n";
		$handle = fopen(_systempath_."/debug/dblog.log", "a");
		fwrite($handle, $strText);
		fclose($handle);
	}

	/**
	 * Writes the last DB-Error to the screen
	 *
	 * @param string $strQuery
	 */
	private function getError($strQuery) {
	    if($this->objDbDriver != null) {
	       $strError = $this->objDbDriver->getError();
	    }
		if($this->objConfig->getDebug("debuglevel") > 0) {

		    //reprocess query
		    $strQuery = str_ireplace(array(" from " , " where ", " and ", " group by ", " order by " ),
		                             array("\nFROM " , "\nWHERE ", "\n\tAND ", "\nGROUP BY ", "\nORDER BY " ),
		                             $strQuery);

		    $strErrorcode = "";
			$strErrorcode .= "<pre>Error in query\n\n";
			$strErrorcode .= "Error:\n";
			$strErrorcode .= $strError . "\n\n";
			$strErrorcode .= "Query:\n";
			$strErrorcode .= $strQuery ."\n";
			$strErrorcode .= "\n";
			$strErrorcode .= "Callstack:\n";
			if (function_exists("debug_backtrace")) {
				$arrStack = debug_backtrace();

				foreach ($arrStack as $intPos => $arrValue)
					$strErrorcode .= $arrValue["file"]."\n\t Row ".$arrValue["line"].", function ".$arrStack[$intPos]["function"]."\n";
			}
			$strErrorcode .= "</pre>";
			throw new class_exception($strErrorcode, class_exception::$level_ERROR);
		}
		else {
		    //send a warning to the logger
		    class_logger::getInstance()->addLogRow("Error in Query: ".$strQuery, class_logger::$levelWarning);
		}

	}


	/**
	 * Starts a trancaction
	 *
	 */
	public function transactionBegin() {
	    if($this->objDbDriver != null) {
	        //just start a new tx, if no other tx is open
	        if($this->intNumberOfOpenTransactions == 0)
		        $this->objDbDriver->transactionBegin();

	        //increase tx-counter
	        $this->intNumberOfOpenTransactions++;

	    }
	}

	/**
	 * Ends a tx successfully
	 *
	 */
	public function transactionCommit() {
	    if($this->objDbDriver != null) {

	        //check, if the current tx is allowed to be commited
	        if($this->intNumberOfOpenTransactions == 1) {
	            //so, this is the last remaining tx. Commit or rollback?
	            if(!$this->bitCurrentTxIsDirty) {
		            $this->objDbDriver->transactionCommit();
	            }
		        else {
		            $this->objDbDriver->transactionRollback();
		            $this->bitCurrentTxIsDirty = false;
		        }

		        //decrement counter
		        $this->intNumberOfOpenTransactions--;
	        }
	        else {
	            $this->intNumberOfOpenTransactions--;
	        }

	    }
	}

	/**
	 * Rollback of the current tx
	 *
	 */
	public function transactionRollback() {
	    if($this->objDbDriver != null) {

		    if($this->intNumberOfOpenTransactions == 1) {
	            //so, this is the last remaining tx. rollback anyway
	            $this->objDbDriver->transactionRollback();
	            $this->bitCurrentTxIsDirty = false;
		        //decrement counter
		        $this->intNumberOfOpenTransactions--;
	        }
	        else {
	            //mark the current tx session a dirty
	            $this->bitCurrentTxIsDirty = true;
	            //decrement the number of open tx
	            $this->intNumberOfOpenTransactions--;
	        }

	    }
	}



	/**
	 * Returns all tables used by the project
	 *
	 * @param bool $bitAll just the name or with additional informations?
	 * @return array
	 */
	public function getTables($bitAll = false) {
		$arrReturn = array();
		if($this->objDbDriver != null) {
    		$arrTemp = $this->objDbDriver->getTables();

    		//Filtering tables not used by this project, if dbprefix was given
    		if(_dbprefix_ != "") {
        		foreach($arrTemp as $arrTable) {
        			if(uniStrpos($arrTable["name"], _dbprefix_) !== false) {
        				if($bitAll)
        					$arrReturn[] =  $arrTable;
        				else
        					$arrReturn[] =  $arrTable[0];
        			}
        		}
    		}
    		else {
    		    foreach($arrTemp as $arrTable) {
    		        if($bitAll)
    					$arrReturn[] =  $arrTable;
    				else
    					$arrReturn[] =  $arrTable[0];
    		    }
    		}
		}
		return $arrReturn;
	}

	/**
     * Looks up the columns of the given table.
     * Should return an array for each row consting of:
     * array ("columnName", "columnType")
     *
     * @param string $strTableName
     * @return array
     */
    public function getColumnsOfTable($strTableName) {
        return $this->objDbDriver->getColumnsOfTable($strTableName);
    }

	/**
     * Used to send a create table statement to the database
     * By passing the query through this method, the driver can
     * add db-specific commands.
     * The array of fields should have the following structure
     * $array[string columnName] = array(string datatype, boolean isNull [, default (only if not null)])
     * whereas datatype is one of the following:
     * 		int
     * 		double
     * 		char10
     * 		char20
     * 		char100
     * 		char254
     * 		text
     *
     * @param string $strName
     * @param array $arrFields array of fields / columns
     * @param array $arrKeys array of primary keys
     * @param array $arrIndices array of additional indices
     * @param bool $bitTxSafe Should the table support transactions?
     * @return bool
     */
    public function createTable($strName, $arrFields, $arrKeys, $arrIndices = array(), $bitTxSafe = true) {
        $bitReturn = $this->objDbDriver->createTable($strName, $arrFields, $arrKeys, $arrIndices, $bitTxSafe);
        if(!$bitReturn)
        	$this->getError($strQuery);
        	
        return $bitReturn;	
    }

	/**
	 * Dumps the current db
	 * Takes care of holding just the defined number of dumps in the filesystem, defined by _system_dbdump_amount_
	 *
	 * @return bool
	 */
	public function dumpDb() {
	    // Check, how many dumps to keep
	    include_once(_systempath_."/class_filesystem.php");
	    $objFilesystem = new class_filesystem();
	    $arrFiles = $objFilesystem->getFilelist("/system/debug/", ".sql");

	    while(count($arrFiles) >= _system_dbdump_amount_) {
	        $strFile = current($arrFiles);
	        if(!$objFilesystem->fileDelete("/system/dbdumps/".$strFile)) {
	           class_logger::getInstance()->addLogRow("Error deleting old db-dumps", class_logger::$levelWarning);
	           return false;
	        }
	        $arrFiles = $objFilesystem->getFilelist("/system/debug/", ".sql");
	    }

        $strTargetFilename = "/system/dbdumps/dbdump_".time().".sql";
	    $bitDump = $this->objDbDriver->dbExport($strTargetFilename, $this->getTables());
	    if($bitDump == true) {
	        include_once(_systempath_."/class_gzip.php");
	        $objGzip = new class_gzip();
	        try {
    	        $objGzip->compressFile($strTargetFilename, true);
	        }
	        catch(class_exception $objExc) {
	            $objExc->processException();
	        }
	    }
        if($bitDump)
            class_logger::getInstance()->addLogRow("DB-Dump ".basename($strTargetFilename)." created", class_logger::$levelInfo);
        else
            class_logger::getInstance()->addLogRow("Error creating ".basename($strTargetFilename), class_logger::$levelError);
	    return $bitDump;
	}

	/**
	 * Imports the given dump
	 *
	 * @param string $strFilename
	 * @return bool
	 */
	public function importDb($strFilename) {
	    //gz file?
	    $bitGzip = false;
	    if(substr($strFilename, -3) == ".gz") {
	        $bitGzip = true;
	        //try to decompress
	        include_once(_systempath_."/class_gzip.php");
	        $objGzip = new class_gzip();
	        try {
	        if($objGzip->decompressFile("/system/dbdumps/".$strFilename))
	           $strFilename = substr($strFilename, 0, strlen($strFilename)-3);
	        else
	           return false;
	        }
	        catch (class_exception $objExc) {
	            $objExc->processException();
	            return false;
	        }
	    }

	    $bitImport = $this->objDbDriver->dbImport("/system/dbdumps/".$strFilename);
        //Delete source unzipped file?
        if($bitGzip) {
            include_once(_systempath_."/class_filesystem.php");
            $objFilesystem = new class_filesystem();
            $objFilesystem->fileDelete("/system/dbdumps/".$strFilename);
        }
        if($bitImport)
            class_logger::getInstance()->addLogRow("DB-DUMP ".$strFilename." was restored", class_logger::$levelWarning);
        else
            class_logger::getInstance()->addLogRow("Error restoring DB-DUMP ".$strFilename, class_logger::$levelError);
	    return $bitImport;
	}

	/**
	 * Parses a query to eliminate unecessary characters suchs as whitespaces
	 *
	 * @param string $strQuery
	 * @return string
	 */
	private function processQuery($strQuery) {
		$strQuery = trim($strQuery);
    	$arrSearch = array(		    "\r\n",
    								"\n",
    								"\r",
    								"\t",
    								"    ",
    								"   ",
    								"  ");
		$arrReplace = array(		""	,
									"",
									"",
									" ",
									" ",
									" ",
									" ");

		$strQuery = str_replace($arrSearch, $arrReplace, $strQuery);

		return $strQuery;
	}

	public function getDbInfo() {
	    if($this->objDbDriver != null) {
            return $this->objDbDriver->getDbInfo();
	    }
	}


	/**
	 * Returns the number of queries sent to the database
	 * including those solved by the cache
	 *
	 * @return int
	 */
	public function getNumber() {
		return $this->intNumber;
	}

	/**
	 * Returns the nnumber of queries solved by the cache
	 *
	 * @return int
	 */
	public function getNumberCache() {
		return $this->intNumberCache;
	}

	/**
	 * Makes a string db-save
	 *
	 * @param string $strString
	 * @param bool $bitHtmlEntitites
	 * @return string
	 */
	public function dbsafeString($strString, $bitHtmlEntitites = true) {
	    //already escaped by php?
	    if(get_magic_quotes_gpc() == 1) {
	       $strString = stripslashes($strString);
	    }
	    $strString = addslashes($strString);

	    if($bitHtmlEntitites) {
	        $strString = htmlentities($strString, ENT_COMPAT, "UTF-8");
	    }

	    return $strString;
	}

	/**
	 * Method to flush the query-cache
	 *
	 */
	public function flushQueryCache() {
	    $this->arrQueryCache = array();
	}
	
	
	/**
     * Allows the db-driver to add database-specific surrounding to column-names.
     * E.g. needed by the mysql-drivers
     *
     * @param string $strColumn
     * @return string
     */
    public function encloseColumnName($strColumn) {
    	return $this->objDbDriver->encloseColumnName($strColumn);
    }

} //class_db
?>