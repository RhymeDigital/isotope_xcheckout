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
 
namespace Rhyme\Hooks\AjaxRequest;

use Rhyme\AjaxInput;

use Isotope\Isotope;

use Haste\Util\InsertTag as Haste_InsertTag;

/**
 * Load a AJAX requested checkout module
 *
 * @copyright  Rhyme digital, LLC. 2015
 * @author     Blair Winans <blair@rhyme.digital>
 * @author     Adam Fisher <adam@rhyme.digital>
 * @package    IsotopeXCheckout
 */
class LoadXCheckout extends \Frontend
{

    /**
	 * Check if the request is specifically for XCheckout
	 */
	public function run()
	{
	    if(AjaxInput::get('action')=='sendxcheckout' && intval(AjaxInput::get('id') > 0))
	    {
	        $this->setAjaxGetAndPostVals();
	            	    
    	    $varValue = \Controller::getFrontendModule(AjaxInput::get('id'));
    	    
    	    $varValue = json_encode(array
			(
				'token'		=> REQUEST_TOKEN,
				'scripts'	=> $this->getScripts(),
				'content'	=> Haste_InsertTag::replaceRecursively($varValue),
			));
			
			echo $varValue;
			exit;
	    }
	}
	
	/**
	 * Set all get and post vals so that xCheckout can proces them
	 */
	protected function setAjaxGetAndPostVals()
	{
	    //Set basic GET and POST vals for XCheckout
	    $arrGetVals = array('step');
	    $arrPostVals = array();
	    
	    //Get Billing/Shipping Address Fields
	    $arrBilling = Isotope::getConfig()->getBillingFields();
	    $arrShipping = Isotope::getConfig()->getShippingFields();
	    foreach($arrBilling as $strField)
	    {
    	   $arrPostVals[] = 'BillingAddress_' . $strField; 
	    }
	    foreach($arrShipping as $strField)
	    {
    	   $arrPostVals[] = 'ShippingAddress_' . $strField; 
	    }
	    
	    //Shipping and Payment Methods
	    $arrPostVals[] = 'ShippingAddress';
	    $arrPostVals[] = 'ShippingMethod';
	    $arrPostVals[] = 'PaymentMethod';
	    
	    //Username/Password
	    $arrPostVals[] = 'username';
	    $arrPostVals[] = 'password';
	    $arrPostVals[] = 'password_confirm';
	    
	    //Form submits and RT
	    $arrPostVals[] = 'previousStep';
	    $arrPostVals[] = 'nextStep';
	    $arrPostVals[] = 'FORM_SUBMIT';
	    $arrPostVals[] = 'REQUEST_TOKEN';
        
        // HOOK: Add custom fields
		if (isset($GLOBALS['TL_HOOKS']['setXCheckoutAjaxGetAndPostVals']) && is_array($GLOBALS['TL_HOOKS']['setXCheckoutAjaxGetAndPostVals']))
		{
			foreach ($GLOBALS['TL_HOOKS']['setXCheckoutAjaxGetAndPostVals'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($arrGetVals, $arrPostVals);
			}
		}
		
		foreach($arrGetVals as $strValue)
		{
    		if(AjaxInput::get($strValue) !== null)
    		{
        		\Input::setGet($strValue, AjaxInput::get($strValue));
    		}
		}
		foreach($arrPostVals as $strValue)
		{
    		if(AjaxInput::post($strValue) !== null)
    		{
        		\Input::setPost($strValue, AjaxInput::post($strValue));
    		}
		}
		
	}
	
	/**
	 * Add all AJAX scripts to be executed on return
	 */
	protected function getScripts()
	{
		$strBuffer = $this->getConditonalSelectScripts();
		
        // HOOK: Add custom scripts
		if (isset($GLOBALS['TL_HOOKS']['setXCheckoutAjaxScripts']) && is_array($GLOBALS['TL_HOOKS']['setXCheckoutAjaxScripts']))
		{
			foreach ($GLOBALS['TL_HOOKS']['setXCheckoutAjaxScripts'] as $callback)
			{
				$this->import($callback[0]);
				$strBuffer = $this->$callback[0]->$callback[1]($strBuffer);
			}
		}
    	
    	return $strBuffer;
	}
	
	/**
	 * Add conditional select menu scripts to update the countries/states
	 */
	protected function getConditonalSelectScripts()
	{
		$strBuffer = '';
		
    	if (is_array($GLOBALS['TL_BODY']) && count($GLOBALS['TL_BODY']))
    	{
	    	foreach ($GLOBALS['TL_BODY'] as $body)
	    	{
		    	if (stripos($body, 'new ConditionalSelect') !== false)
		    	{
			    	$strBuffer .= str_replace(array('<script>', '</script>', "\n"), '', $body);
		    	}
	    	}
    	}
    	
    	return $strBuffer;
	}

	/**
	 * Use output buffer to var dump to a string
	 * 
	 * @param	string
	 * @return	string 
	 */
	public static function varDumpToString($var)
	{
		ob_start();
		var_dump($var);
		$result = ob_get_clean();
		return $result;
	}
}