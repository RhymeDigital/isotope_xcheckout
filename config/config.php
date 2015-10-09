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


/**
 * Frontend modules
 */
$GLOBALS['FE_MOD']['isotope']['iso_xcheckout'] = 'Rhyme\Module\XCheckout';


/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['ajaxRequest'][]		= array('\Rhyme\Hooks\AjaxRequest\LoadXCheckout', 'run');
$GLOBALS['ISO_HOOKS']['postCheckout'][]		= array('\Rhyme\Hooks\PostCheckout\CreateMember', 'run');


/**
 * Re-arrange step callbacks for checkout module to make 2 full steps
 */
$GLOBALS['ISO_CHECKOUTSTEP'] = array
(
	'login_newuser_guest'			=> array(
		'Rhyme\CheckoutStep\Login',
		'Rhyme\CheckoutStep\NewUser',
		'Rhyme\CheckoutStep\Guest',
	),
    'address_shipping'   	=> array( 
        'Rhyme\CheckoutStep\BillingAddress', 
        'Isotope\CheckoutStep\ShippingAddress', 
        'Isotope\CheckoutStep\ShippingMethod'
    ),
    'review_payment'   		=> array(
        'Rhyme\CheckoutStep\PaymentMethod', 
        'Isotope\CheckoutStep\OrderConditionsOnTop', 
        'Isotope\CheckoutStep\OrderInfo', 
        'Isotope\CheckoutStep\OrderConditionsBeforeProducts', 
        'Isotope\CheckoutStep\OrderProducts', 
        'Isotope\CheckoutStep\OrderConditionsAfterProducts'
    ),
);


/**
 * Scripts
 */
$GLOBALS['XCHECKOUT_JS']['json2']			= 'system/modules/isotope_xcheckout/assets/js/json2.js';
$GLOBALS['XCHECKOUT_JS']['xcheckout']		= 'system/modules/isotope_xcheckout/assets/js/xcheckout.js';
