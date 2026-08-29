<?php
/**
 * Alfa Payment Gateway - WooCommerce Extension
 * Registers payment icon.
 */
function woocommerce_bank_alfalah_icon(){
    return BAF_WOO_IMG .'bank_alfalah_logo.png';
}
//add_filter( 'woocommerce_bank_alfalah_icon', 'woocommerce_bank_alfalah_icon' );

/**
 * Alfa Payment Gateway - WooCommerce Extension
 * Admin Test Connection HTML Output
 */
function bank_alfalah_admin_page_now_test(){
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    // Use $_GET['section'] directly rather than parsing REQUEST_URI by
    // splitting on "=" and taking the last piece — that approach broke
    // whenever WordPress/WooCommerce appended extra query params after
    // section=bank_alfalah_gateway (e.g. "&from=WCADMIN_PAYMENT_SETTINGS",
    // which WooCommerce's own Payments overview page adds automatically),
    // since the "last value after =" was no longer "bank_alfalah_gateway".
    $bank_alfalah_gateway_page = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
    $bank_alfalah_option = get_option( 'woocommerce_bank_alfalah_gateway_settings' );
    if( $bank_alfalah_gateway_page == 'bank_alfalah_gateway' ){ ?>
        <div id="bank_alfalah_testing" class="bank_alfalah_testing">
            <button id="bank_alfalah_testing_button" class="button-primary bank_alfalah_testing_button"><?php _e( 'Click Here For Test Connection', BAF_WOO_TEXT_DOMAIN ); ?></button>
            <div id="bank_alfalah_testing_responce_wait"><img src="<?php echo admin_url( 'images/loading.gif' ); ?>" /><?php _e( ' Please Wait...', BAF_WOO_TEXT_DOMAIN ); ?></div>
            <div id="bank_alfalah_testing_responce_success"><?php _e( 'Connection Successful', BAF_WOO_TEXT_DOMAIN ); ?></div>
            <div id="bank_alfalah_testing_responce_error"><?php _e( 'Connection Unsuccessful', BAF_WOO_TEXT_DOMAIN ); ?></div>
        </div>
    <?php
    }
}
add_action( 'admin_footer', 'bank_alfalah_admin_page_now_test' );
/**
 * Redirect Success / Faild
 *
 * @param int $order_id
 * @return array
 */
function bank_alfalah_redirect_thankyou_page(){

    global $wp;
    if ( ! is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }

    $order_id = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
    if ( ! $order_id ) {
        return;
    }

    // Use wc_get_order() (HPOS-safe) instead of get_post_meta() directly.
    $order = wc_get_order( $order_id );
    if ( ! $order || $order->get_payment_method() !== BAF_WOO_GATEWAY_ID ) {
        return;
    }

    if ( ! isset( $_GET['key'] ) || ! $_GET['key'] ) {
        wp_safe_redirect( home_url( '/checkout/order-received/' . $order_id . '/?key=' . $order->get_order_key() ) );
        exit;
    }

    // SECURITY: Verify the key in the URL actually belongs to this order
    // before doing anything. Without this check, any visitor who knows or
    // guesses an order ID could trigger a live IPN lookup and status write
    // for someone else's order.
    $submitted_key = wc_clean( wp_unslash( $_GET['key'] ) );
    if ( ! hash_equals( $order->get_order_key(), $submitted_key ) ) {
        bank_alfalah_log( 'Order key mismatch on thank-you redirect for order ' . $order_id );
        return;
    }

    // Idempotency guard: don't re-check / re-write status for orders that
    // have already reached a final state.
    if ( $order->has_status( array( 'processing', 'completed', 'refunded', 'cancelled' ) ) ) {
        return;
    }

    $payment_gateways_instance = WC_Payment_Gateways::instance();
    $payment_gateways          = $payment_gateways_instance->payment_gateways();

    if ( ! isset( $payment_gateways[ BAF_WOO_GATEWAY_ID ] ) ) {
        return;
    }

    $payment_gateway = $payment_gateways[ BAF_WOO_GATEWAY_ID ];
    $response         = $payment_gateway->get_ipn_response( $order_id );

    if ( is_array( $response ) && isset( $response['TransactionStatus'] ) ) {
        if ( $response['TransactionStatus'] == BAF_WOO_TX_SUCCESS ) {
            $order->update_status( 'processing', __( 'Payment successful', BAF_WOO_TEXT_DOMAIN ) );
        } else {
            $order->update_status( 'failed', __( 'Payment failed', BAF_WOO_TEXT_DOMAIN ) );
        }
    } else {
        bank_alfalah_log( 'Could not confirm IPN status for order ' . $order_id . ' on thank-you redirect.' );
    }
}
add_action( 'template_redirect', 'bank_alfalah_redirect_thankyou_page', 5 );

/**
 * Alfa Payment Gateway - WooCommerce Extension
 * Admin Test Connection Request Handler
 */
function bank_alfalah_AuthToken(){

    // SECURITY: This endpoint sends the merchant's live bank credentials to
    // Bank Alfalah's handshake API. It is an admin "test connection" tool
    // and must only be callable by a logged-in admin with a valid nonce —
    // never by logged-out visitors (see wp_ajax_nopriv_* hook removed below).
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
    }
    check_ajax_referer( 'bank_alfalah_test_connection', 'nonce' );

    $bank_alfalah_option = get_option( 'woocommerce_bank_alfalah_gateway_settings' );
    $currency = get_woocommerce_currency();
    
    $HS_ChannelId = "1001";
    $HS_ReturnURL = home_url( '/' );
    $HS_IsRedirectionRequest = 0;
    
    $Sandbox = $bank_alfalah_option['sandbox_enabled'];
    $url = $Sandbox == "yes" ? "https://sandbox.bankalfalah.com/HS/HS/HS" : "https://payments.bankalfalah.com/HS/HS/HS";
    $KeyOne = $bank_alfalah_option['key_one'];
    $KeyTwo = $bank_alfalah_option['key_two'];

    $AuthToken              = "";
    $HS_MerchantId          = $bank_alfalah_option['merchant_id'];
    $HS_StoreId             = $bank_alfalah_option['store_id'];
    $HS_MerchantHash        = $bank_alfalah_option['merchant_hash'];
    $HS_MerchantUsername    = $bank_alfalah_option['merchant_username'];
    $HS_MerchantPassword    = $bank_alfalah_option['merchant_password'];
    
    // Build handshake payload in EXACTLY the same field order as the
    // Bank Alfalah sandbox reference page (DOM order).
    $post = [];
    $post['HS_RequestHash']                = '';
    $post['HS_IsRedirectionRequest']        = $HS_IsRedirectionRequest;
    $post['HS_ChannelId']                   = $HS_ChannelId;
    $post['HS_ReturnURL']                   = $HS_ReturnURL;
    $post['HS_MerchantId']                  = $HS_MerchantId;
    $post['HS_StoreId']                     = $HS_StoreId;
    $post['HS_MerchantHash']                = $HS_MerchantHash;
    $post['HS_MerchantUsername']            = $HS_MerchantUsername;
    $post['HS_MerchantPassword']            = $HS_MerchantPassword;
    $post['HS_TransactionReferenceNumber']  = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : random_int( 100000, 999999 );
    
    $data = [];
    foreach($post as $k => $v) {
        $data[] = implode("=", [$k, $v]);
    }

    $mapString = implode('&', $data);
    
    $cipher="aes-128-cbc";
    $cipher_text = openssl_encrypt( $mapString, $cipher, $KeyOne, OPENSSL_RAW_DATA, $KeyTwo );
    $cipher_text64 =  base64_encode($cipher_text);
    
    $post['HS_RequestHash'] = $cipher_text64 ;
    
    // NOTE: timeout kept below common shared-hosting gateway timeouts
    // (Hostinger and similar hosts often cut connections around 30s at the
    // proxy/webserver level, regardless of PHP's max_execution_time). A
    // 45s wp_remote_post timeout let the outer webserver kill the request
    // first, which produces a bare 503 with no PHP error and nothing for
    // bank_alfalah_log() to record — exactly what was happening here.
    $args = array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.1',
        'body' => $post,
        'blocking' => true,
        'user-agent' => 'AlfaPaymentGateway/' . BAF_WOO_VERSION . ' (WooCommerce)',
        'headers' => array('Content-type: application/x-www-form-urlencoded'),
        'cookies' => array()
    );
    
    // Force Streams transport to bypass the strict OpenSSL 3 cURL error 56 EOF bug
    $force_streams = function() { return array( 'streams' ); };
    add_filter( 'http_api_transports', $force_streams, 999 );
    
    // NOTE: retries once on cURL error 56 ("SSL_read: unexpected eof while
    // reading") — a known OpenSSL 3.x / curl interoperability bug seen on
    // some hosts, not specific to this plugin. See the matching note in
    // admin/admin.php's bank_alfalah_generate_authorize_form().
    $response = wp_remote_post( $url, $args );
    if ( is_wp_error( $response ) && false !== strpos( $response->get_error_message(), 'error:0A000126' ) ) {
        bank_alfalah_log( 'Test connection hit transient SSL error, retrying once: ' . $response->get_error_message() );
        $response = wp_remote_post( $url, $args );
    }

    remove_filter( 'http_api_transports', $force_streams, 999 );

    if ( is_wp_error( $response ) ) {
        bank_alfalah_log( 'Test connection failed: ' . $url . ' - ' . $response->get_error_message() );
        wp_send_json_error( array(
            'message' => __( 'Connection failed. Please check WooCommerce logs for details.', BAF_WOO_TEXT_DOMAIN ),
        ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $json = json_decode( $body );

    if ( ! is_object( $json ) || ! isset( $json->AuthToken, $json->ReturnURL ) ) {
        bank_alfalah_log( 'Test connection returned an unexpected response: ' . $url . ' - ' . $body );
        wp_send_json_error( array(
            'message' => __( 'Unexpected response from Alfa Payment Gateway. Please check WooCommerce logs for details.', BAF_WOO_TEXT_DOMAIN ),
        ) );
    }

    $Success    = isset( $json->success ) ? $json->success : '';
    $AuthToken  = $json->AuthToken;
    $ReturnURL  = $json->ReturnURL;

    wp_send_json_success( array(
        'url'       => $url,
        'status'    => $Success,
        'AuthToken' => $AuthToken,
        'ReturnURL' => $ReturnURL,
    ) );
}
// SECURITY: no wp_ajax_nopriv_* hook — this is an admin-only "test
// connection" tool that sends the merchant's live bank credentials to
// Bank Alfalah. Logged-out visitors have no legitimate reason to call it,
// and allowing them to could be used to spam requests against the
// merchant's account at the bank.
add_action( 'wp_ajax_bank_alfalah_AuthToken', 'bank_alfalah_AuthToken' );
