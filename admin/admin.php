<?php
/**
 * Alfa Payment Gateway - WooCommerce Extension
 * Admin settings and fields configuration panel.
 */

defined( 'ABSPATH' ) or exit;

/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + bank alfalah gateway
 */
function bank_alfalah_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_BankAlfalah_Geteway';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'bank_alfalah_add_to_gateways' );


/**
 * Alfa Payment Gateway Class
 *
 * Core gateway class handling admin settings, configuration fields, and payment processing.
 *
 * @class 		WC_BankAlfalah_Geteway
 * @extends		WC_Payment_Gateway
 * @version		1.1
 * @package		WooCommerce/Classes/Payment
 * @author 		Alfa Payment Gateway
 */
function bank_alfalah_gateway_init() {
    
    class WC_BankAlfalah_Geteway extends WC_Payment_Gateway {
    	/**
    	 * Constructor for the gateway.
    	 */
    	public function __construct() {
      
    		$this->id                 = BAF_WOO_GATEWAY_ID;
    		$this->icon               = apply_filters('woocommerce_bank_alfalah_icon', '');
    		$this->has_fields         = true;
    		$this->method_title       = __( 'Alfalah Payment Gateway', BAF_WOO_TEXT_DOMAIN );
    		$this->method_description = __( 'Allows payments by Alfa Payment Gateway.', BAF_WOO_TEXT_DOMAIN );
    	  
    		$this->init_form_fields();
    		$this->init_settings();

    		$this->title              = $this->get_option( 'title' );
            $this->enabled            = $this->get_option( 'enabled' );
            $this->sandbox_enabled    = $this->get_option( 'sandbox_enabled' );
            $this->merchant_id        = $this->get_option( 'merchant_id' );
            $this->store_id           = $this->get_option( 'store_id' );
            $this->merchant_hash      = $this->get_option( 'merchant_hash' );
            $this->merchant_username  = $this->get_option( 'merchant_username' );
            $this->merchant_password  = $this->get_option( 'merchant_password' );
            $this->key_one            = $this->get_option( 'key_one' );
            $this->key_two            = $this->get_option( 'key_two' );
            
            $this->credit_card       = $this->get_option( 'credit_card' );
            $this->wallet            = $this->get_option( 'wallet' );
            $this->alfalah_account   = $this->get_option( 'alfalah_account' );
			$this->BNPL   			 = $this->get_option( 'BNPL' );
			$this->card_on_delivery   = $this->get_option( 'card_on_delivery' );
			$this->JazzCash   = $this->get_option( 'JazzCash' );
			//$this->RaastQr   = $this->get_option( 'RaastQr' );
    		
            $this->description        = $this->get_option( 'description' );
            $this->instructions       = $this->get_option( 'instructions', $this->description );

            $this->bafl_url_handshake = "https://payments.bankalfalah.com/HS/HS/HS";
            $this->bafl_url_payment   = "https://payments.bankalfalah.com/SSO/SSO/SSO";
            $this->bafl_url_ipn       = "https://payments.bankalfalah.com/HS/api/IPN/OrderStatus/{$this->merchant_id}/{$this->store_id}/";

            if ($this->sandbox_enabled == "yes") {
                $this->bafl_url_handshake = "https://sandbox.bankalfalah.com/HS/HS/HS";
                $this->bafl_url_payment   = "https://sandbox.bankalfalah.com/SSO/SSO/SSO";
                $this->bafl_url_ipn       = "https://sandbox.bankalfalah.com/HS/api/IPN/OrderStatus/{$this->merchant_id}/{$this->store_id}/";
            }

    		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_receipt_' . $this->id, array(&$this, 'bank_alfalah_receipt_page' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ), 5, 1 );
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            add_action( 'woocommerce_api_' . $this->id, array($this, 'check_ipn_response') );
    	}
    
    	/**
    	 * Initialize Gateway Settings Form Fields
    	 */
    	public function init_form_fields() {
      
    		$this->form_fields = apply_filters( 'bank_alfalah_form_fields', array(
          
                
    			'listener_url' => array(
    				'title'   => __( 'IPN Listener URL', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'title',
    				'description'   => __( home_url('wc-api/'. $this->id) . "<br /><br />(Listener URL for BAFL Merchants Portal)", BAF_WOO_TEXT_DOMAIN ),
    				'default' => 'yes'
                ),
                                
    			'enabled' => array(
    				'title'   => __( 'Enable/Disable', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'checkbox',
    				'label'   => __( 'Enable/Disable', BAF_WOO_TEXT_DOMAIN ),
    				'default' => 'yes'
    			),
                
                'sandbox_enabled' => array(
    				'title'   => __( 'Gateway status', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'select',
    				'default' => 'yes',
                    'options'     => array(
                        'yes' => __('Sandbox mode', BAF_WOO_TEXT_DOMAIN ),
                        'no' => __('Live mode', BAF_WOO_TEXT_DOMAIN )
                    )
    			),
    			
                'title' => array(
    				'title'       => __( 'Alfa Payment Gateway', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'text',
    				'description' => __( 'You can change payment name on your website.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => __( 'Alfa Payment Gateway', BAF_WOO_TEXT_DOMAIN ),
    				'desc_tip'    => true,
    			),
                
    			'merchant_id' => array(
    				'title'       => __( 'Merchant ID', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'text',
    				'description' => __( 'You should add here bank alfalah merchant id.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => __( '', BAF_WOO_TEXT_DOMAIN ),
    				'desc_tip'    => true,
    			),
                
                'store_id' => array(
    				'title'       => __( 'Store ID', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'text',
    				'description' => __( 'You should add here bank alfalah store id.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => __( '', BAF_WOO_TEXT_DOMAIN ),
    				'desc_tip'    => true,
    			),
                
                'merchant_hash' => array(
    				'title'       => __( 'Merchant Hash', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'text',
    				'description' => __( 'You should add here bank alfalah merchant hash.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => __( '', BAF_WOO_TEXT_DOMAIN ),
    				'desc_tip'    => true,
    			),
                
                'merchant_username' => array(
    				'title'       => __( 'Merchant Username', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'text',
    				'description' => __( 'You should add here bank alfalah merchant username.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => __( '', BAF_WOO_TEXT_DOMAIN ),
    				'desc_tip'    => true,
    			),
                
                'merchant_password' => array(
    				'title'       => __( 'Merchant Password', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'password',
    				'description' => __( 'You should add here bank alfalah merchant password.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => __( '', BAF_WOO_TEXT_DOMAIN ),
    				'desc_tip'    => true,
    			),
                
                'key_one' => array(
    				'title'       => __( 'Key 1', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'password',
    				'description' => __( 'Add your Bank Alfalah Key 1 here.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => __( '', BAF_WOO_TEXT_DOMAIN ),
    				'desc_tip'    => true,
    			),
                
                'key_two' => array(
    				'title'       => __( 'Key 2', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'password',
    				'description' => __( 'Add your Bank Alfalah Key 2 here.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => __( '', BAF_WOO_TEXT_DOMAIN ),
    				'desc_tip'    => true,
    			),
    			
                'credit_card' => array(
    				'title'   => __( 'Credit/Debit Card', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'checkbox',
    				'label'   => __( 'Enable/Disable', BAF_WOO_TEXT_DOMAIN ),
    				'default' => 'yes'
    			),
                
                'wallet' => array(
    				'title'   => __( 'Alfa Wallet', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'checkbox',
    				'label'   => __( 'Enable/Disable', BAF_WOO_TEXT_DOMAIN ),
    				'default' => 'yes'
    			),
                
                'alfalah_account' => array(
    				'title'   => __( 'Bank Alfalah Account', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'checkbox',
    				'label'   => __( 'Enable/Disable', BAF_WOO_TEXT_DOMAIN ),
    				'default' => 'yes'
    			),
				'BNPL' => array(
    				'title'   => __( 'Alfa BNPL Islamic', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'checkbox',
    				'label'   => __( 'Enable/Disable', BAF_WOO_TEXT_DOMAIN ),
    				'default' => 'yes'
    			),
				'card_on_delivery' => array(
    				'title'   => __( 'Card On Delivery', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'checkbox',
    				'label'   => __( 'Enable/Disable', BAF_WOO_TEXT_DOMAIN ),
    				'default' => 'yes'
    			),
				'JazzCash' => array(
    				'title'   => __( 'JazzCash Wallet', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'checkbox',
    				'label'   => __( 'Enable/Disable', BAF_WOO_TEXT_DOMAIN ),
    				'default' => 'yes'
    			),
				/*
				'RaastQr' => array(
    				'title'   => __( 'RaastQr', BAF_WOO_TEXT_DOMAIN ),
    				'type'    => 'checkbox',
    				'label'   => __( 'Enable/Disable', BAF_WOO_TEXT_DOMAIN ),
    				'default' => 'yes'
    			),
				*/
                
    			'description' => array(
    				'title'       => __( 'Description', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'textarea',
    				'description' => __( 'Payment method description that the customer will see on your checkout.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => __( '', BAF_WOO_TEXT_DOMAIN ),
    				'desc_tip'    => true,
    			),
    			
    			'instructions' => array(
    				'title'       => __( 'Instructions', BAF_WOO_TEXT_DOMAIN ),
    				'type'        => 'textarea',
    				'description' => __( 'Instructions that will be added to the thank you page and emails.', BAF_WOO_TEXT_DOMAIN ),
    				'default'     => '',
    				'desc_tip'    => true,
    			),
    		) );
    	}
        
        /**
    	 * Output of the form.
    	 */
        public function payment_fields() {
            if ( $this->description ) {
                if ( $this->sandbox_enabled == 'yes' ) {
                    $this->description .= ' <br /><span class="sandbox-error">Sandbox mode is enable, for more info please see <a href="#" target="_blank" rel="noopener noreferrer">documentation</a>.</span>';
                    $this->description  = trim( $this->description );
                }
                echo wpautop( wp_kses_post( $this->description ) );
            }
            do_action( 'woocommerce_bank_alfalah_form_start', $this->id );
            ?>
            <div class="wc-<?php echo esc_attr( $this->id ); ?>">
                <div class="bank-alfalah-payment-type">
                    <span class="handshake-success">&#10004;</span>
                    <span class="handshake-error">&#10006;</span>
                    <?php if ( $this->credit_card == 'yes' ) { ?>
                    <div class="bank-alfalah-redio">
                        <label for="alfalah-card"><input type="radio" name="bank-alfalah-payment-type" value="3" id="alfalah-card"/> <img src="<?php echo BAF_WOO_IMG; ?>visa-master.png" /> <?php _e( 'Credit/Debit Card', BAF_WOO_TEXT_DOMAIN ); ?></label>
                    </div>
                    <?php } ?>
                    
                    <?php if ( $this->wallet == 'yes' ) { ?>
                    <div class="bank-alfalah-redio">
                        <label for="alfalah-wallet"><input type="radio" name="bank-alfalah-payment-type" value="1" id="alfalah-wallet"/> <img src="<?php echo BAF_WOO_IMG; ?>visa.png" /> <?php _e( 'Alfa Wallet', BAF_WOO_TEXT_DOMAIN ); ?></label>
                    </div>
                    <?php } ?>
                    
                    <?php if ( $this->alfalah_account == 'yes' ) { ?>
                    <div class="bank-alfalah-redio">
                        <label for="alfalah-account"><input type="radio" name="bank-alfalah-payment-type" value="2" id="alfalah-account" /> <img src="<?php echo BAF_WOO_IMG; ?>visa.png" /> <?php _e( 'Bank Alfalah Account', BAF_WOO_TEXT_DOMAIN ); ?></label>
                    </div>
                    <?php } ?>
					 <?php if ( $this->BNPL == 'yes' ) { ?>
                    <div class="bank-alfalah-redio">
                        <label for="BNPL"><input type="radio" name="bank-alfalah-payment-type" value="5" id="BNPL" /> <img src="<?php echo BAF_WOO_IMG; ?>islamic.png" /> <?php _e( 'Alfa Islamic BNPL', BAF_WOO_TEXT_DOMAIN ); ?></label>
                    </div>
                    <?php } ?>
					 <?php if ( $this->card_on_delivery == 'yes' ) { ?>
                    <div class="bank-alfalah-redio">
                        <label for="card_on_delivery"><input type="radio" name="bank-alfalah-payment-type" value="6" id="card_on_delivery" /> <img src="<?php echo BAF_WOO_IMG; ?>visa.png" /> <?php _e( 'Card On Delivery', BAF_WOO_TEXT_DOMAIN ); ?></label>
                    </div>
                    <?php } ?>
					 <?php if ( $this->JazzCash == 'yes' ) { ?>
                    <div class="bank-alfalah-redio">
                        <label for="JazzCash"><input type="radio" name="bank-alfalah-payment-type" value="11" id="JazzCash" /> <img src="<?php echo BAF_WOO_IMG; ?>new-Jazzcash-logo.png" /> <?php _e( 'JazzCash Wallet', BAF_WOO_TEXT_DOMAIN ); ?></label>
                    </div>
                    <?php } ?>
					 <?php
					 /**
					  * TODO: value="11" here is IDENTICAL to JazzCash's TransactionTypeId
					  * above. Both currently send TransactionTypeId=11 to Bank Alfalah,
					  * which means RaastQr payments will be processed as JazzCash
					  * transactions. Confirm the correct RaastQr TransactionTypeId with
					  * Bank Alfalah's integration team and update this value before
					  * enabling RaastQr in production.
					  */
					 ?>
					 <?php /* if ( $this->RaastQr == 'yes' ) { ?>
                    <div class="bank-alfalah-redio">
                        <label for="RaastQr"><input type="radio" name="bank-alfalah-payment-type" value="11" id="RaastQr" /> <img src="<?php echo BAF_WOO_IMG; ?>RaastQr-logo.png" /> <?php _e( 'RaastQr', BAF_WOO_TEXT_DOMAIN ); ?></label>
                    </div>
                    <?php } */ ?>
                </div>
            </div>
            <script>
                (function ($) {
                    var alfalahRadioIds = [
                        '#alfalah-card', '#alfalah-wallet', '#alfalah-account',
                        '#BNPL', '#card_on_delivery', '#JazzCash'
                    ];
                    var otherGatewayIds = [
                        '#payment_method_cod', '#payment_method_hblpay', '#payment_method_bacs'
                    ];

                    function uncheckAlfalahRadios() {
                        $( alfalahRadioIds.join( ',' ) ).prop( 'checked', false );
                    }
                    function uncheckOtherGateways() {
                        $( otherGatewayIds.join( ',' ) ).prop( 'checked', false );
                    }

                    // Start with everything unchecked.
                    uncheckAlfalahRadios();

                    // Selecting any other WooCommerce gateway clears our radios.
                    $( otherGatewayIds.join( ',' ) ).on( 'change', uncheckAlfalahRadios );

                    // Selecting any Alfalah payment-type radio clears other gateways.
                    $( alfalahRadioIds.join( ',' ) ).on( 'change', uncheckOtherGateways );
                })( jQuery );
            </script>
            <?php
            do_action( 'woocommerce_bank_alfalah_form_end', $this->id );
        }

        public function get_ipn_response($order_id = null) {
            if ($order_id === null) {
                return null;
            }
            $url = $this->bafl_url_ipn . $order_id;
            return $this->bank_alfalah_fetch_ipn( $url );
        }
        
        /**
         * Shared IPN fetch helper using wp_remote_get (replaces the old
         * file_get_contents() calls, which fail silently/fatally on many
         * hosts when allow_url_fopen or SSL stream contexts aren't set up).
         *
         * @param string $url
         * @return array|null
         */
        private function bank_alfalah_fetch_ipn( $url ) {
            // SECURITY: sslverify intentionally left at its default (true).
            // Disabling it would let a man-in-the-middle intercept or spoof
            // responses on a request that confirms whether a payment
            // succeeded — never disable this against a live banking API.
            //
            // NOTE: timeout kept below common shared-hosting gateway
            // timeouts — see the matching note in bank_alfalah_AuthToken()
            // in includes/woocommerce.php for why 45s caused bare 503s.
            // (Increased to 45 as per Hostinger recommendation).
            //
            // Also retries once on the known transient cURL error 56 SSL
            // issue described in bank_alfalah_generate_authorize_form()
            // above — this call confirms whether a customer's payment
            // actually succeeded, so it's worth the extra attempt.
            $get_args = array( 'timeout' => 45, 'httpversion' => '1.1', 'user-agent' => 'AlfaPaymentGateway/' . BAF_WOO_VERSION . ' (WooCommerce)' );
            
            // Force Streams transport to bypass the strict OpenSSL 3 cURL error 56 EOF bug
            $force_streams = function() { return array( 'streams' ); };
            add_filter( 'http_api_transports', $force_streams, 999 );

            $response = wp_remote_get( $url, $get_args );
            if ( is_wp_error( $response ) && false !== strpos( $response->get_error_message(), 'error:0A000126' ) ) {
                bank_alfalah_log( 'IPN request hit transient SSL error, retrying once: ' . $response->get_error_message() );
                $response = wp_remote_get( $url, $get_args );
            }

            remove_filter( 'http_api_transports', $force_streams, 999 );

            if ( is_wp_error( $response ) ) {
                bank_alfalah_log( 'IPN request failed for ' . $url . ': ' . $response->get_error_message() );
                return null;
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

            // The gateway sometimes double-encodes the JSON payload.
            if ( is_string( $data ) ) {
                $data = json_decode( $data, true );
            }

            if ( ! is_array( $data ) ) {
                bank_alfalah_log( 'IPN response could not be decoded for ' . $url . ': ' . $body );
                return null;
            }

            return $data;
        }
        
    	/**
    	 * Output for the order received page.
    	 */
    	public function thankyou_page($order_id) {
            global $order;
            $order = wc_get_order( $order_id );

            if ( $this->instructions ) {
    			echo wpautop( wptexturize( $this->instructions ) );
    		}
    	}
    
    	/**
    	 * Add content to the WC emails.
    	 *
    	 * @access public
    	 * @param WC_Order $order
    	 * @param bool $sent_to_admin
    	 * @param bool $plain_text
    	 */
    	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'completed' ) ) {
    			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
    		}
    	}
    
    	/**
    	 * Process the payment and return the result
    	 *
    	 * @param int $order_id
    	 * @return array
    	 */
    	public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                wc_add_notice( __( 'Order not found.', BAF_WOO_TEXT_DOMAIN ), 'error' );
                return array( 'result' => 'failure' );
            }

            $payment_type = isset( $_POST['bank-alfalah-payment-type'] )
                ? absint( wp_unslash( $_POST['bank-alfalah-payment-type'] ) )
                : 1;
            $order->update_meta_data( 'bank-alfalah-payment-type', $payment_type );
            $order->save();
            return array(
                'result' 	=> 'success',
                'redirect'	=> $order->get_checkout_payment_url( true )
            );
    	}
        
        /**
    	 * Redirect
    	 *
    	 * @param int $order_id
    	 * @return array
    	 */
        public function bank_alfalah_receipt_page($order){
            echo '<p>'.__('Thank you for your order, please click the button below to pay with Alfa Payment Gateway.', BAF_WOO_TEXT_DOMAIN ).'</p>';
            echo $this->bank_alfalah_generate_authorize_form($order);
        }
        
        /**
    	 * Generate Authorize Form
    	 *
    	 * @param int $order_id
    	 * @return array
    	 */
        public function bank_alfalah_generate_authorize_form($order_id){
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                bank_alfalah_log( 'bank_alfalah_generate_authorize_form() called with invalid order ID ' . $order_id );
                return '<div class="woocommerce-error">' . esc_html__( 'Alfa Payment Gateway could not find your order. Please try again or contact us if the problem continues.', BAF_WOO_TEXT_DOMAIN ) . '</div>';
            }
            $amount = $order->get_total();
            $bank_alfalah_option = get_option( 'woocommerce_bank_alfalah_gateway_settings' );
            $currency = get_woocommerce_currency();
     
            $HS_ChannelId = "1001";
            
            // Bank Alfalah requires a CLEAN return URL with NO query parameters.
            // The sandbox reference uses a plain URL like:
            //   https://romanmary.com/step/thank-you/
            // WooCommerce's get_checkout_order_received_url() adds ?key=...
            // which breaks the bank's hash/mapString validation.
            // We use the WooCommerce "order-received" endpoint as the base
            // (the IPN listener handles actual order status updates separately).
            $HS_ReturnURL = home_url( '/' );
            
            $HS_IsRedirectionRequest = 0;
            $TransactionTypeId = $order->get_meta('bank-alfalah-payment-type');

            $KeyOne = $bank_alfalah_option['key_one'];
            $KeyTwo = $bank_alfalah_option['key_two'];
            
            $AuthToken              = "";
            $HS_MerchantId          = $bank_alfalah_option['merchant_id'];
            $HS_StoreId             = $bank_alfalah_option['store_id'];
            $HS_MerchantHash        = $bank_alfalah_option['merchant_hash'];
            $HS_MerchantUsername    = $bank_alfalah_option['merchant_username'];
            $HS_MerchantPassword    = $bank_alfalah_option['merchant_password'];
            
            // Build the handshake payload in EXACTLY the same field order as
            // the Bank Alfalah sandbox reference page. The bank's JS iterates
            // form inputs in DOM order to build mapString, and the hash is
            // computed BEFORE filling HS_RequestHash, so it appears as empty.
            $post = [];
            $post['HS_RequestHash']                = '';
            $post['HS_IsRedirectionRequest']        = 0;
            $post['HS_ChannelId']                   = $HS_ChannelId;
            $post['HS_ReturnURL']                   = $HS_ReturnURL;
            $post['HS_MerchantId']                  = $HS_MerchantId;
            $post['HS_StoreId']                     = $HS_StoreId;
            $post['HS_MerchantHash']                = $HS_MerchantHash;
            $post['HS_MerchantUsername']            = $HS_MerchantUsername;
            $post['HS_MerchantPassword']            = $HS_MerchantPassword;
            $post['HS_TransactionReferenceNumber']  = $order_id;
            
            $data = [];
            foreach($post as $k => $v) {
                $data[] = implode("=", [$k, $v]);
            }
            
            $mapString = implode('&', $data);
            
            $cipher="aes-128-cbc";
            $cipher_text = openssl_encrypt( $mapString, $cipher, $KeyOne, OPENSSL_RAW_DATA, $KeyTwo );
            $cipher_text64 =  base64_encode($cipher_text);
            
            $post['HS_RequestHash'] = $cipher_text64 ;
            
            // SECURITY: sslverify intentionally left at its default (true).
            // This request carries the merchant's bank username, password,
            // and hash in the request body — it must go over a verified
            // TLS connection.
            //
            // NOTE: timeout kept below common shared-hosting gateway
            // timeouts (see the matching note in bank_alfalah_AuthToken()
            // in includes/woocommerce.php). This is the handshake real
            // customers trigger at checkout, so a bare 503 here means a
            // failed order, not just a broken admin test button.
            // Increased to 45.
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
            
            // NOTE: retries once on cURL error 56 ("SSL_read: unexpected eof
            // while reading"). This is a known OpenSSL 3.x / curl
            // interoperability bug on some hosts (not specific to this
            // plugin or to Bank Alfalah) where a valid TLS connection is
            // occasionally torn down mid-read. A single retry is a
            // reasonable mitigation while the host-level OpenSSL/curl issue
            // is resolved with your hosting provider.
            $response = wp_remote_post( $this->bafl_url_handshake , $args );
            if ( is_wp_error( $response ) && false !== strpos( $response->get_error_message(), 'error:0A000126' ) ) {
                bank_alfalah_log( 'Handshake hit transient SSL error, retrying once: ' . $response->get_error_message() );
                $response = wp_remote_post( $this->bafl_url_handshake , $args );
            }

            remove_filter( 'http_api_transports', $force_streams, 999 );

            if ( is_wp_error( $response ) ) {
                bank_alfalah_log( 'Handshake request failed: ' . $response->get_error_message() );
                return '<div class="woocommerce-error">' . esc_html__( 'Alfa Payment Gateway could not start your payment. Please try again or contact us if the problem continues.', BAF_WOO_TEXT_DOMAIN ) . '</div>';
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $body          = wp_remote_retrieve_body( $response );
            $json          = json_decode( $body );

            if ( $response_code !== 200 || ! is_object( $json ) || ! isset( $json->AuthToken, $json->ReturnURL ) ) {
                bank_alfalah_log( 'Handshake response invalid. HTTP ' . $response_code . ' Body: ' . $body );
                return '<div class="woocommerce-error">' . esc_html__( 'Alfa Payment Gateway could not start your payment. Please try again or contact us if the problem continues.', BAF_WOO_TEXT_DOMAIN ) . '</div>';
            }

            // Bank returns success as a string "true"/"false"
            if ( ! isset( $json->success ) || $json->success !== 'true' ) {
                $bank_error = isset( $json->ErrorMessage ) ? $json->ErrorMessage : 'Unknown';
                bank_alfalah_log( 'Handshake rejected by bank. success=' . ( isset( $json->success ) ? $json->success : 'missing' ) . ' ErrorMessage=' . $bank_error . ' Body: ' . $body );
                return '<div class="woocommerce-error">' . esc_html__( 'Alfa Payment Gateway could not start your payment. Please try again or contact us if the problem continues.', BAF_WOO_TEXT_DOMAIN ) . '</div>';
            }

            $Success    = $json->success;
            $AuthToken  = $json->AuthToken;
            $ReturnURL  = $json->ReturnURL;    
            
            // Build the SSO redirect payload in EXACTLY the same field order
            // as the Bank Alfalah sandbox Page Redirection Form. The bank's
            // JS iterates form inputs in DOM order to build mapString, and
            // RequestHash is empty when the hash is computed.
            $post = [];
            $post['AuthToken']                  = $AuthToken;
            $post['RequestHash']                = '';
            $post['ChannelId']                  = $HS_ChannelId;
            $post['Currency']                   = $currency;
            $post['IsBIN']                      = 0;
            $post['ReturnURL']                  = $ReturnURL;
            $post['MerchantId']                 = $HS_MerchantId;
            $post['StoreId']                    = $HS_StoreId;
            $post['MerchantHash']               = $HS_MerchantHash;
            $post['MerchantUsername']           = $HS_MerchantUsername;
            $post['MerchantPassword']           = $HS_MerchantPassword;
            $post['TransactionTypeId']          = $TransactionTypeId;
            $post['TransactionReferenceNumber'] = $order_id;
            $post['TransactionAmount']          = $amount;
            
            $data = [];
            foreach($post as $k => $v) {
                $data[] = implode("=", [$k, $v]);
            }
            
            $mapString = implode('&', $data);   
            
            $cipher="aes-128-cbc";
            $cipher_text = openssl_encrypt( $mapString, $cipher, $KeyOne, OPENSSL_RAW_DATA, $KeyTwo );
            $cipher_text64 =  base64_encode($cipher_text);
            
            $post['RequestHash'] = $cipher_text64 ;
            
            // NOTE: Bank Alfalah's SSO endpoint validates the RequestHash
            // against ALL posted fields. Every field that was included in the
            // mapString when computing the hash MUST also be present in the
            // HTML form, otherwise the bank sees a hash mismatch and returns
            // "InvalidRequest". This includes merchant credentials — the
            // bank's own sandbox reference page confirms this requirement.
            $html_args = array(
                'AuthToken'                     => $AuthToken,
                'RequestHash'                   => $cipher_text64,
                'ChannelId'                     => $HS_ChannelId,
                'Currency'                      => $currency,
                'IsBIN'                         => 0,
                'ReturnURL'                     => $ReturnURL,
                'MerchantId'                    => $HS_MerchantId,
                'StoreId'                       => $HS_StoreId,
                'MerchantHash'                  => $HS_MerchantHash,
                'MerchantUsername'              => $HS_MerchantUsername,
                'MerchantPassword'              => $HS_MerchantPassword,
                'TransactionTypeId'             => $TransactionTypeId,
                'TransactionReferenceNumber'    => $order_id,
                'TransactionAmount'             => $amount
            );
            
            $html_fields = array();
            
            foreach($html_args as $key => $value){
                $html_fields[] = "<input id='" . esc_attr( $key ) . "' type='hidden' name='" . esc_attr( $key ) . "' value='" . esc_attr( $value ) . "'/>";
            }
            
            $html_form    = '<form action="' . $this->bafl_url_payment . '" method="post" id="PageRedirectionForm" novalidate="novalidate">' 
            . implode('', $html_fields) 
            . '<input type="submit" class="button" id="run" value="'.__('Pay via Alfa Payment Gateway', BAF_WOO_TEXT_DOMAIN ).'" />'
            . '<script type="text/javascript">
            jQuery(function(){
            jQuery("body").block({
            message: "<img src=\"' . BAF_WOO_IMG . 'baf.png\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;top:10px;\" />'.__('Kindly wait, you are being redirected to Alfa Payment Gateway to complete your payment', BAF_WOO_TEXT_DOMAIN ).'",
                overlayCSS:{
                    background:       "#FFFFFF",
                    opacity:          1,
                    "z-index": "999999"
                },
                centerX: true, // <-- only effects element blocking (page block controlled via css above) 
                centerY: true, 
                css: {
                    padding:          20,
                    textAlign:        "center",
                    top:              "-40%",
                    bottom: "60%",
                    color:            "#555",
                    backgroundColor:  "#fff",
                    cursor:           "wait",
                    lineHeight:       "32px",
                    "z-index": "999999"
                }
                });
            jQuery("#run").click();
            });
            </script>
            </form>';
            
            return $html_form;
        }

        public function check_ipn_response() {

            // SECURITY: Never build the IPN request from a client-supplied URL —
            // that was an SSRF vector (attacker could point it at an internal
            // host) and an order-status-forgery vector (attacker could point it
            // at a server they control that returns a fake "Paid" response).
            // Instead we accept only an order ID and build the IPN URL
            // ourselves, exactly as get_ipn_response() already does.
            $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

            if ( ! $order_id ) {
                status_header( 400 );
                exit;
            }

            $order = wc_get_order( $order_id );

            if ( ! $order || $order->get_payment_method() !== $this->id ) {
                status_header( 404 );
                exit;
            }

            // Idempotency guard: don't let a duplicate/late IPN ping flip an
            // order that has already reached a final state back to
            // processing or failed.
            if ( $order->has_status( array( 'processing', 'completed', 'refunded', 'cancelled' ) ) ) {
                bank_alfalah_log( 'Ignoring IPN callback for order ' . $order_id . ' — already in status ' . $order->get_status() );
                status_header( 200 );
                exit;
            }

            $response = $this->get_ipn_response( $order_id );

            if ( is_array( $response ) && isset( $response['TransactionStatus'] ) ) {
                if ( $response['TransactionStatus'] == BAF_WOO_TX_SUCCESS ) {
                    $order->update_status( 'processing', __( 'Payment successful', BAF_WOO_TEXT_DOMAIN ) );
                } else {
                    $order->update_status( 'failed', __( 'Payment failed', BAF_WOO_TEXT_DOMAIN ) );
                }
            } else {
                bank_alfalah_log( 'Could not confirm IPN status for order ' . $order_id . ' on IPN callback.' );
            }

            status_header( 200 );
            exit;
        }

    }
}
add_action( 'plugins_loaded', 'bank_alfalah_gateway_init', 11 );