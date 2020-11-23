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

// Verify the order exists
if ( !isset( $_POST["SID_REFERENCE"] ) || empty( $_POST["SID_REFERENCE"] ) ) {
    echo "Order ID is not set or emtpy!";

} else {

    // Retrieve the variables posted back from SID
    $_RESULT = $_POST['SID_STATUS'];

    /*
     * The result of the transaction. Possible returned values are:
     * COMPLETED
     * CANCELLED
     * CREATED
     * READY
     */
    
    // SID_REFERENCE
    $_ERROR_CODE    = $_POST['_ERROR_CODE'];
    $_ERROR_SOURCE  = $_POST['_ERROR_SOURCE'];
    $_ERROR_MESSAGE = $_POST['_ERROR_MESSAGE'];
    $_ERROR_DETAIL  = $_POST['_ERROR_DETAIL'];

    $order_id = $_POST['SID_REFERENCE'];

    if ( strtolower( $_RESULT ) == "completed" ) {
        global $cookie, $cart;

        $SID = new SID();

        $url = $SID->validateOrder( intval( $cart->id ), _PS_OS_PAYMENT_, floatval( $cart->getOrderTotal( true, 3 ) ), $SID->displayName, null );

        Tools::redirectLink( $url );
    } else if ( strtolower( $_RESULT ) == "created" || strtolower( $_RESULT ) == "ready" ) {
        global $cookie, $cart;

        $SID        = new SID();
        $pending_id = $SID->getSIDPendingID();
        $url        = $SID->validateOrder( intval( $cart->id ), $pending_id, floatval( $cart->getOrderTotal( true, 3 ) ), $SID->displayName, null );
        Tools::redirectLink( $url );
    } else {
        $SID = new SID();
        $url = $SID->returnerrorurl();

        Tools::redirectLink( $url );
    }
}