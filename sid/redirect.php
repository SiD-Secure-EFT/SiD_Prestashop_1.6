<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

include dirname( __FILE__ ) . '/../../config/config.inc.php';
include dirname( __FILE__ ) . '/../../init.php';
include dirname( __FILE__ ) . '/sid.php';

$SID  = new SID();
$cart = new Cart( intval( $cookie->id_cart ) );

$address = new Address( intval( $cart->id_address_invoice ) );
$country = new Country( intval( $address->id_country ) );
$state   = null;
if ( $address->id_state ) {
    $state = new State( intval( $address->id_state ) );
}

$customer = new Customer( intval( $cart->id_customer ) );

$SID_MERCHANT    = Configuration::get( 'SID_MERCHANT' ); //gets the merchant ID which is set in the sid.php page
$SID_PRIVATE_KEY = Configuration::get( 'SID_PRIVATE_KEY' ); //gets the private key which is set it the sid.php page
$currency_order  = new Currency( intval( $cart->id_currency ) );
$currency_module = $SID->getCurrency();

if ( !Validate::isLoadedObject( $address ) or !Validate::isLoadedObject( $customer ) or !Validate::isLoadedObject( $currency_module ) ) {
    die( $SID->getL( 'SID error: (invalid address or customer)' ) );
}

// check currency of payment
if ( $currency_order->id != $currency_module->id ) {
    $cookie->id_currency = $currency_module->id;
    $cart->id_currency   = $currency_module->id;
    $cart->update();
}

//merchant details
$productsInCart  = $cart->getProducts(); //gets products fromt the cart
$SID_MERCHANT    = $SID_MERCHANT; //gets the merchant ID
$SID_CURRENCY    = 'ZAR'; //the currency is set in Rand as SID is only for Soutth Africa
$SID_COUNTRY     = 'ZA'; //the country is set to Soutth Africa
$SID_REFERENCE   = $cart->id; //gets refrence form the cart
$SID_AMOUNT      = $cart->getOrderTotal( true, 3 );
$SID_PRIVATE_KEY = $SID_PRIVATE_KEY; //private key
//hashes the variables
$SID_CONSISTENT = strtoupper( hash( 'sha512', $SID_MERCHANT . $SID_CURRENCY . $SID_COUNTRY . $SID_REFERENCE . $SID_AMOUNT . $SID_PRIVATE_KEY ) );
//this passes the variables through
$smarty->assign( array(
    'redirect_text'       => strval( 'Please wait, redirecting to SID... Thanks.' ),
    'cancel_text'         => $SID->getL( 'Cancel' ),
    'cart_text'           => $SID->getL( 'My cart' ),
    'return_text'         => $SID->getL( 'Return to shop' ),
    'SID_URL'             => $SID->getSIDUrl(),

    'SID_MERCHANT'        => $SID_MERCHANT,
    'SID_CURRENCY'        => $SID_CURRENCY,
    'SID_COUNTRY'         => $SID_COUNTRY,
    'SID_REFERENCE'       => $SID_REFERENCE,
    'SID_AMOUNT'          => $SID_AMOUNT,
    'SID_PRIVATE_KEY'     => $SID_PRIVATE_KEY,
    'SID_CONSISTENT'      => $SID_CONSISTENT,
    'products'            => $cart->getProducts(),
    'total_cart_products' => $total_cart_products,
    'url'                 => Tools::getHttpHost( true, true ) . __PS_BASE_URI__,
) );

if ( is_file( _PS_THEME_DIR_ . 'modules/SID/redirect.tpl' ) ) //passes the information through to the redirect.tpl page
{
    $smarty->display( _PS_THEME_DIR_ . 'modules/' . $SID->name . '/redirect.tpl' );
} else {
    $smarty->display( _PS_MODULE_DIR_ . $SID->name . '/redirect.tpl' );
}
