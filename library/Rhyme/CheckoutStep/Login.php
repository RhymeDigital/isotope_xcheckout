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

class Login extends CheckoutStep implements IsotopeCheckoutStep
{

    /**
     * Returns true to enable the module
     * @return  bool
     */
    public function isAvailable()
    {
        if($this->objModule->iso_checkout_method == 'guest' || \Haste\Input\Input::getAutoItem('step') != 'login_newuser_guest')
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
    	// Generate the login module
    	$this->preLoginGenerate();
        $strLogin = $this->getFrontendModule($this->objModule->iso_loginModule);
    	$this->postLoginGenerate();
    	
    	// Clean the output
        $strLogin = static::replaceSectionsOfString($strLogin, '<form', '>');
        $strLogin = static::replaceSectionsOfString($strLogin, '</form', '>');
        $strLogin = static::replaceSectionsOfString($strLogin, '<input type="hidden" name="FORM_SUBMIT"', '>');
        $strLogin = static::replaceSectionsOfString($strLogin, '<input type="hidden" name="REQUEST_TOKEN"', '>');
        
        if($strLogin === '')
        {
            return '';
        }
        
        $objTemplate = new \Isotope\Template('iso_checkout_login');
        $objTemplate->login = $strLogin;
        $objTemplate->headline = $GLOBALS['TL_LANG']['MSC']['login'];
        $objTemplate->message = $this->objModule->iso_checkout_method == 'member' ? $GLOBALS['TL_LANG']['MSC']['loginMessage'] : $GLOBALS['TL_LANG']['MSC']['bothMessage'];
        return $objTemplate->parse();
    }
    
    /**
     * Execute actions before module generation
     * @return  void
     */
    public function preLoginGenerate()
    {
    	if (\Input::post('FORM_SUBMIT') && !\Input::post('submit_guest') && !\Input::post('submit_newuser') && !\Input::post('previousStep'))
    	{
    		// Temporarily change the FORM_SUBMIT value so the login module handles the submission
	    	$GLOBALS['XCHECKOUT']['FORM_SUBMIT'] = \Input::post('FORM_SUBMIT');
	    	\Input::setPost('FORM_SUBMIT', 'tl_login');
    	}
    }
    
    /**
     * Execute actions after module generation
     * @return  void
     */
    public function postLoginGenerate()
    {
    	if (\Input::post('FORM_SUBMIT') && !\Input::post('submit_guest') && !\Input::post('submit_newuser') && !\Input::post('previousStep'))
    	{
    		// Put the FORM_SUBMIT value back
	    	\Input::setPost('FORM_SUBMIT', $GLOBALS['XCHECKOUT']['FORM_SUBMIT']);
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

	
	/**
	 * Remove sections of a string using a start and end (use "[caption" and "]" to remove any caption blocks)
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return string
	 */
	public static function replaceSectionsOfString($strSubject, $strStart, $strEnd, $strReplace='', $blnCaseSensitive=true, $blnRecursive=true)
	{
		// First index of start string
		$varStart = $blnCaseSensitive ? strpos($strSubject, $strStart) : stripos($strSubject, $strStart);
		
		if ($varStart === false)
			return $strSubject;
		
		// First index of end string
		$varEnd = $blnCaseSensitive ? strpos($strSubject, $strEnd, $varStart+1) : stripos($strSubject, $strEnd, $varStart+1);
		
		// The string including the start string, end string, and everything in between
		$strFound = $varEnd === false ? substr($strSubject, $varStart) : substr($strSubject, $varStart, ($varEnd + strlen($strEnd) - $varStart));
		
		// The string after the replacement has been made
		$strResult = $blnCaseSensitive ? str_replace($strFound, $strReplace, $strSubject) : str_ireplace($strFound, $strReplace, $strSubject);
		
		// Check for another occurence of the start string
		$varStart = $blnCaseSensitive ? strpos($strSubject, $strStart) : stripos($strSubject, $strStart);
		
		// If this is recursive and there's another occurence of the start string, keep going
		if ($blnRecursive && $varStart !== false)
		{
			$strResult = static::replaceSectionsofString($strResult, $strStart, $strEnd, $strReplace, $blnCaseSensitive, $blnRecursive);
		}
		
		return $strResult;
	}

}