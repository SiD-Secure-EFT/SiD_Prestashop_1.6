<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

if ( !defined( '_CAN_LOAD_FILES_' ) ) {
    exit;
}

class SID extends PaymentModule
{
    private $_html       = '';
    private $_postErrors = array();

    public function __construct()
    {
        $this->name    = 'sid'; //name of module
        $this->tab     = 'Methods'; //were module will be found
        $this->version = '1.5.3.1'; //verison that its compatible with

        $this->currencies      = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->page             = basename( __FILE__, '.php' );
        $this->displayName      = $this->l( 'SID Instant EFT' );
        $this->description      = $this->l( 'Accept payments by SID Instant EFT' );
        $this->confirmUninstall = $this->l( 'Are you sure you want to delete your details ?' );
        if ( $_SERVER['SERVER_NAME'] == 'localhost' ) {
            $this->warning = $this->l( 'You are running under localhost, we cannot validate order.' );
        }

        $id_status = (int) self::MysqlGetValue( "SELECT id_order_state FROM `" . _DB_PREFIX_ . "order_state_lang` WHERE name like 'Awaiting SID Instant EFT payment' and id_lang=1" );
        if ( $id_status == "" || $id_status == 0 ) {
            $max_id_status = (int) self::MysqlGetValue( "SELECT max(id_order_state) FROM `" . _DB_PREFIX_ . "order_state_lang`" );
            $max_id_status = $max_id_status + 1;

            (int) self::MysqlSetValue( "insert into `" . _DB_PREFIX_ . "order_state_lang` (id_order_state,id_lang,name) values ($max_id_status,1,'Awaiting SID payment') " );

            (int) self::MysqlSetValue( "insert into `" . _DB_PREFIX_ . "order_state_lang` (id_order_state,id_lang,name) values ($max_id_status,2,'Awaiting SID payment') " );

            (int) self::MysqlSetValue( "insert into `" . _DB_PREFIX_ . "order_state_lang` (id_order_state,id_lang,name) values ($max_id_status,3,'Awaiting SID payment') " );

            (int) self::MysqlSetValue( "insert into `" . _DB_PREFIX_ . "order_state` (id_order_state,color) values ($max_id_status,'#5DC7B9') " );
        }
    }

    // Retro compatibility with 1.2.5
    private static function MysqlSetValue( $query )
    {
        $row = Db::getInstance()->Execute( $query );
        return $row;
    }

    // Retro compatibility with 1.2.5
    private static function MysqlGetValue( $query )
    {
        $row = Db::getInstance()->getRow( $query );
        return array_shift( $row );
    }

    public function getSIDUrl()
    {
        return Configuration::get( 'SID_URL' ) ? 'https://www.sidpayment.com/paySID/' : 'https://www.sidpayment.com/paySID/';
    }

    public function install()
    {
        if ( !parent::install()
            or !Configuration::updateValue( 'SID_URL', 'https://www.sidpayment.com/paySID/' ) //SID redirect url
             or !Configuration::updateValue( 'SID_MERCHANT', 'PHONE7' ) //default merchant
             or !Configuration::updateValue( 'SID_PRIVATE_KEY', '539B47C2B8D6C4CCFA5CC820A22D9529RldHSDU2NjIKRldHSDU2NjI1MzlCNDdDMkI4RDZDNENDRkE1Q0M4MjBBMjJEOTUyOpDc' )
            or !$this->registerHook( 'payment' )
            or !$this->registerHook( 'paymentReturn' ) ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if ( !Configuration::deleteByName( 'SID_ACTION' )
            or !Configuration::deleteByName( 'SID_URL' )
            or !Configuration::deleteByName( 'SID_MERCHANT' )
            or !Configuration::updateValue( 'SID_PRIVATE_KEY', '539B47C2B8D6C4CCFA5CC820A22D9529RldHSDU2NjIKRldHSDU2NjI1MzlCNDdDMkI4RDZDNENDRkE1Q0M4MjBBMjJEOTUyOpDc' )
            or !parent::uninstall() ) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
//        $this->_html = '<h2>SID</h2>';

        if ( isset( $_POST['submitSID'] ) ) {
            if ( empty( $_POST['SID_MERCHANT'] ) ) {
                $this->_postErrors[] = $this->l( 'SID Identifier is required.' );
            }

            if ( empty( $_POST['SID_URL'] ) ) {
                $this->_postErrors[] = $this->l( 'SID Url is required.' );
            }

            if ( empty( $_POST['SID_PRIVATE_KEY'] ) ) {
                $this->_postErrors[] = $this->l( 'SID Private Key is required.' );
            }

            if ( !sizeof( $this->_postErrors ) ) {
                Configuration::updateValue( 'SID_MERCHANT', strval( $_POST['SID_MERCHANT'] ) );
                Configuration::updateValue( 'SID_URL', strval( $_POST['SID_URL'] ) );
                Configuration::updateValue( 'SID_PRIVATE_KEY', strval( $_POST['SID_PRIVATE_KEY'] ) );
                $this->displayConf();
            } else {
                $this->displayErrors();
            }

        }

        $this->displaySID();
        $this->displayFormSettings();
        return $this->_html;
    }

    public function displayConf()
    {
        $this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="' . $this->l( 'Confirmation' ) . '" />
			' . $this->l( 'Settings updated' ) . '
		</div>';
    }

    public function displayErrors()
    {
        $nbErrors = sizeof( $this->_postErrors );
        $this->_html .= '
		<div class="alert error">
			<h3>' . ( $nbErrors > 1 ? $this->l( 'There are' ) : $this->l( 'There is' ) ) . ' ' . $nbErrors . ' ' . ( $nbErrors > 1 ? $this->l( 'errors' ) : $this->l( 'error' ) ) . '</h3>
			<ol>';
        foreach ( $this->_postErrors as $error ) {
            $this->_html .= '<li>' . $error . '</li>';
        }

        $this->_html .= '
			</ol>
		</div>';
    }

    public function displaySID()
    {
        $this->_html .= '
		<img src="../modules/sid/sid_logo.jpg" style="float:left; margin-right:15px;" />
		<b>' . $this->l( 'This module allows you to accept SID Instant EFT payments.' ) . '</b><br /><br />
		' . $this->l( 'If the client chooses this payment mode, your account with SID Instant EFT will be automatically credited.' ) . '<br />
		' . $this->l( 'You need to configure your SID account first before using this module.' ) . '
		<div style="clear:both;">&nbsp;</div>';
    }

    public function displayFormSettings()
    {
        $conf                = Configuration::getMultiple( array( 'SID_URL', 'SID_ACTION', 'SID_MERCHANT', 'SID_PRIVATE_KEY' ) );
        $SID_URL             = array_key_exists( 'SID_URL', $_POST ) ? $_POST['SID_URL'] : ( array_key_exists( 'SID_URL', $conf ) ? $conf['SID_URL'] : '' );
        $ButtonAction        = array_key_exists( 'ButtonAction', $_POST ) ? $_POST['ButtonAction'] : ( array_key_exists( 'SID_ACTION', $conf ) ? $conf['SID_ACTION'] : '' );
        $SID_MERCHANT        = array_key_exists( 'SID_MERCHANT', $_POST ) ? $_POST['SID_MERCHANT'] : ( array_key_exists( 'SID_MERCHANT', $conf ) ? $conf['SID_MERCHANT'] : '' );
        $SID_PRIVATE_KEY     = array_key_exists( 'SID_PRIVATE_KEY', $_POST ) ? $_POST['SID_PRIVATE_KEY'] : ( array_key_exists( 'SID_PRIVATE_KEY', $conf ) ? $conf['SID_PRIVATE_KEY'] : '' );
        $SID_seller_password = array_key_exists( 'SID_seller_password', $_POST ) ? $_POST['SID_seller_password'] : ( array_key_exists( 'SID_SELLER_PASSWORD', $conf ) ? $conf['SID_SELLER_PASSWORD'] : '' );

        $this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" style="clear: both;">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />' . $this->l( 'Settings' ) . '</legend>
			<label>' . $this->l( 'Merchant Code' ) . '</label>
			<div class="margin-form"><input type="text" size="33" name="SID_MERCHANT" value="' . htmlentities( $SID_MERCHANT, ENT_COMPAT, 'UTF-8' ) . '" /></div>

			<label>' . $this->l( 'SID URL' ) . '</label>
			<div class="margin-form"><input type="text" size="33" name="SID_URL" value="' . htmlentities( $SID_URL, ENT_COMPAT, 'UTF-8' ) . '" /></div>

			<label>' . $this->l( 'Private Key' ) . '</label>
			<div class="margin-form"><input type="text" size="33" name="SID_PRIVATE_KEY" value="' . htmlentities( $SID_PRIVATE_KEY, ENT_COMPAT, 'UTF-8' ) . '" /></div>

			</div>
			<br />
			<br /><center><input type="submit" name="submitSID" value="' . $this->l( 'Update settings' ) . '" class="button" /></center>
		</fieldset>
		</form>';
    }

    public function hookPayment( $params )
    {
        if ( !$this->active ) {
            return;
        }

        return $this->display( __FILE__, 'sid.tpl' );
    }

    public function hookPaymentReturn( $params )
    {
        if ( !$this->active ) {
            return;
        }

        return $this->display( __FILE__, 'confirmation.tpl' );
    }

    public function getL( $key )
    {

        $translations = array(
            'Cancel'         => $this->l( 'Cancel' ),
            'My cart'        => $this->l( 'My cart' ),
            'Return to shop' => $this->l( 'Return to shop' ),
        );
        return $translations[$key];

    }

    private static function getHttpHost( $http = false, $entities = false )
    {
        $host = ( isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'] );
        if ( $entities ) {
            $host = htmlspecialchars( $host, ENT_COMPAT, 'UTF-8' );
        }

        if ( $http ) {
            $host = ( Configuration::get( 'PS_SSL_ENABLED' ) ? 'https://' : 'http://' ) . $host;
        }

        return $host;
    }

    public function validateOrder( $id_cart, $id_order_state, $amountPaid, $paymentMethod = 'Unknown', $message = null, $extraVars = array(), $currency_special = null, $dont_touch_amount = false )
    {
        global $cookie, $cart;
        $customer = new Customer( intval( $cart->id_customer ) );

        parent::validateOrder( $id_cart, $id_order_state, $amountPaid, $paymentMethod, $message, $extraVars );
        return self::getHttpHost( true, true ) . __PS_BASE_URI__ . "order-confirmation.php?key=" . $customer->secure_key . "&id_cart=" . $cart->id . "&id_module=" . $this->id;

    }

    public function returnerrorurl()
    {
        return self::getHttpHost( true, true ) . __PS_BASE_URI__ . 'order.php?step=3';
    }

    public function getSIDPendingID()
    {
        $id_status = (int) self::MysqlGetValue( "SELECT id_order_state FROM `" . _DB_PREFIX_ . "order_state_lang` WHERE name like 'Awaiting SID payment' and id_lang=1" );

        return $id_status;
    }

}
