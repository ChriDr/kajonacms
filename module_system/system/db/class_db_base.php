<?php
/*"******************************************************************************************************
*   (c) 2014 by Kajona, www.kajona.de                                                                   *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

/**
 * Base class for all database-drivers, holds methods to be used by all drivers
 *
 * @package module_system
 * @since 4.5
 * @author sidler@mulchprod.de
 */
abstract class class_db_base implements interface_db_driver {

    protected $arrStatementsCache = array();

    /**
     * Creates a single query in order to insert multiple rows at one time.
     * For most databases, this will create s.th. like
     * INSERT INTO $strTable ($arrColumns) VALUES (?, ?), (?, ?)...
     *
     * Please note that this method is used to create the query itself, based on the Kajona-internal syntax.
     * The query is fired to the database by class_db
     *
     * @param string $strTable
     * @param string[] $arrColumns
     * @param array $arrValueSets
     * @param string &$strQuery
     * @param array &$arrParams
     *
     * @return void
     */
    public function convertMultiInsert($strTable, $arrColumns, $arrValueSets, &$strQuery, &$arrParams) {

        $arrPlaceholder = array();
        $arrSafeColumns = array();

        foreach($arrColumns as $strOneColumn) {
            $arrSafeColumns[] = $this->encloseColumnName($strOneColumn);
            $arrPlaceholder[] = "?";
        }
        $strPlaceholder = "(".implode(",", $arrPlaceholder).")";

        $arrPlaceholderSets = array();
        $arrParams = array();

        foreach($arrValueSets as $arrOneSet) {
            $arrPlaceholderSets[] = $strPlaceholder;
            $arrParams = array_merge($arrParams, $arrOneSet);
        }

        $strQuery = "INSERT INTO ".$this->encloseTableName($strTable)." (".implode(",", $arrSafeColumns).") VALUES ".implode(",", $arrPlaceholderSets);
    }

    /**
     * Returns just a part of a recodset, defined by the start- and the end-rows,
     * defined by the params
     *
     * @param string $strQuery
     * @param int $intStart
     * @param int $intEnd
     *
     * @return array
     */
    public function getArraySection($strQuery, $intStart, $intEnd) {
        //calculate the end-value: mysql limit: start, nr of records, so:
        $intEnd = $intEnd - $intStart + 1;
        //add the limits to the query
        $strQuery .= " LIMIT " . $intStart . ", " . $intEnd;
        //and load the array
        return $this->getArray($strQuery);
    }

    /**
     * Returns just a part of a recodset, defined by the start- and the end-rows,
     * defined by the params. Makes use of prepared statements.
     * <b>Note:</b> Use array-like counters, so the first row is startRow 0 whereas
     * the n-th row is the (n-1)th key!!!
     *
     * @param string $strQuery
     * @param array $arrParams
     * @param int $intStart
     * @param int $intEnd
     *
     * @return array
     * @since 3.4
     */
    public function getPArraySection($strQuery, $arrParams, $intStart, $intEnd) {
        //calculate the end-value: mysql limit: start, nr of records, so:
        $intEnd = $intEnd - $intStart + 1;
        //add the limits to the query
        $strQuery .= " LIMIT " . $intStart . ", " . $intEnd;
        //and load the array
        return $this->getPArray($strQuery, $arrParams);
    }

    /**
     * Allows the db-driver to add database-specific surrounding to column-names.
     * E.g. needed by the mysql-drivers
     *
     * @param string $strColumn
     *
     * @return string
     */
    public function encloseColumnName($strColumn) {
        return $strColumn;
    }

    /**
     * Allows the db-driver to add database-specific surrounding to table-names.
     *
     * @param string $strTable
     *
     * @return string
     */
    public function encloseTableName($strTable) {
        return $strTable;
    }

    /**
     * A method triggered in special cases in order to
     * have even the caches stored at the db-driver being flushed.
     * This could get important in case of schema updates since precompiled queries may get invalid due
     * to updated table definitions.
     *
     * @return void
     */
    public function flushQueryCache() {
        $this->arrStatementsCache = array();
    }
}

