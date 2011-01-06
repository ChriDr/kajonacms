<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                        *
********************************************************************************************************/

/**
 * Does an absolute, linear rating based on the current rating-value
 * @package modul_rating
 */
class class_modul_rating_algo_absolute implements interface_modul_rating_algo {
	
	
	/**
     * Calculates the new rating
     * 
     * @param class_modul_rating_rate $objSourceRate The rating-record to update
     * @param float $floatNewRating The rating fired by the user
     * @return float the new rating
     */
    public function doRating($objSourceRate, $floatNewRating) {
    	//calc the new rating
        $floatNewRating = (($objSourceRate->getFloatRating() * $objSourceRate->getIntHits()) + $floatNewRating) / ($objSourceRate->getIntHits()+1);
        
        return $floatNewRating;
    }
	
	
}

?>