<?php

/**
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009-2014 terminal42 gmbh & Isotope eCommerce Workgroup
 *
 * @package    Isotope
 * @link       http://isotopeecommerce.org
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

namespace Rhyme\CheckoutStep;

use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Interfaces\IsotopeCheckoutStep;
use Isotope\CheckoutStep\CheckoutStep;
use Isotope\Isotope;

class Guest extends CheckoutStep implements IsotopeCheckoutStep
{

    /**
     * The name of the submit button
     * @var  string
     */
    protected $strSubmit = 'submit_guest';

    /**
     * Returns true to enable the module
     * @return  bool
     */
    public function isAvailable()
    {
        if($this->objModule->iso_checkout_method == 'member')
        {
            return false;
        }
        
        return FE_USER_LOGGED_IN ? false: true;
    }
    
    /**
     * Generate the checkout step
     * Override parent by also generating for the payment form
     * @return  string
     */
    public function generate()
    {
    	$this->handleSubmit();
    	
        $objTemplate = new \Isotope\Template('iso_checkout_newuser');
        $objTemplate->headline 		= $GLOBALS['TL_LANG']['MSC']['guestHeadline'] ?: '';
        $objTemplate->message 		= $GLOBALS['TL_LANG']['MSC']['guestMessage'] ?: '';
        $objTemplate->btnValue 		= $GLOBALS['TL_LANG']['MSC']['guestBtnLabel'] ?: 'Continue as a guest';
        $objTemplate->btnName 		= $this->strSubmit;
        return $objTemplate->parse();
    }
    
    /**
     * Handle submissions
     * @return  void
     */
    public function handleSubmit()
    {
    	if (\Input::post('FORM_SUBMIT') && \Input::post('submit_guest'))
    	{
    		$_SESSION['XCHECKOUT']['USER'] = 'guest';
    	}
    }
    
    /**
     * Get review information about this step
     * @return  array
     */
    public function review()
    {
        return '';
    }
    
    /**
     * Return array of tokens for notification
     * @param   IsotopeProductCollection
     * @return  array
     */
    public function getNotificationTokens(IsotopeProductCollection $objCollection)
    {
        return array();
    }

}