<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                *
********************************************************************************************************/

/**
 * Class representing the search element on the admin side
 *
 * @package module_search
 * @author sidler@mulchprod.de
 * @targetTable element_search.content_id
 *
 */
class class_element_search_admin extends class_element_admin implements interface_admin_element {

    /**
     * @var string
     * @tableColumn element_search.search_template
     *
     * @fieldType template
     * @fieldLabel template
     *
     * @fieldTemplateDir /module_search
     */
    private $strTemplate;

    /**
     * @var int
     * @tableColumn element_search.search_amount
     *
     * @fieldType text
     * @fieldLabel search_amount
     */
    private $intAmount;

    /**
     * @var string
     * @tableColumn element_search.search_page
     *
     * @fieldType page
     * @fieldLabel commons_result_page
     */
    private $strPage;

    /**
     * @param string $strTemplate
     */
    public function setStrTemplate($strTemplate) {
        $this->strTemplate = $strTemplate;
    }

    /**
     * @return string
     */
    public function getStrTemplate() {
        return $this->strTemplate;
    }

    /**
     * @param string $strPage
     */
    public function setStrPage($strPage) {
        $this->strPage = $strPage;
    }

    /**
     * @return string
     */
    public function getStrPage() {
        return $this->strPage;
    }

    /**
     * @param int $intAmount
     */
    public function setIntAmount($intAmount) {
        $this->intAmount = $intAmount;
    }

    /**
     * @return int
     */
    public function getIntAmount() {
        return $this->intAmount;
    }




}
