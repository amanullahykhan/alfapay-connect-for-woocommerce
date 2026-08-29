/*
 * Admin Test
 */
jQuery("#bank_alfalah_testing_button").click(function(){

    var _check_form = jQuery("#form-status").val();
    var _hs_requesthash = jQuery("#HS_RequestHash").val();

    jQuery("#bank_alfalah_testing_responce_wait").css( 'display', 'block' );
    jQuery("#bank_alfalah_testing_responce_success").css( 'display', 'none' );
    jQuery("#bank_alfalah_testing_responce_error").css( 'display', 'none' );

    jQuery.post(
        bank_alfalah_AuthToken.ajaxurl,
        {
            action          : 'bank_alfalah_AuthToken',
            nonce           : bank_alfalah_AuthToken.nonce,
            hs_requesthash  : _hs_requesthash,
            CheckForm       : _check_form
        },
        function( response ){
            jQuery("#bank_alfalah_testing_responce_wait").css( 'display', 'none' );
            // wp_send_json_success()/wp_send_json_error() always return a
            // real boolean "success" field — safer than comparing a string.
            if ( response && response.success ) {
                jQuery("#bank_alfalah_testing_responce_success").css( 'display', 'block' );
            } else {
                jQuery("#bank_alfalah_testing_responce_error").css( 'display', 'block' );
            }
        }
    ).fail(function(){
        jQuery("#bank_alfalah_testing_responce_wait").css( 'display', 'none' );
        jQuery("#bank_alfalah_testing_responce_error").css( 'display', 'block' );
    });
    return false;
});
// Fixed typo: was "#RasstQr" (extra "s"), which never matched the actual
// "#RaastQr" radio button rendered in admin.php — the click handler below
// silently did nothing for the RaastQr option.
jQuery( "body" ).on( "click", "#alfalah-card, #alfalah-wallet, #alfalah-account,#BNPL,#card_on_delivery,#JazzCash,#RaastQr",  function(){
    jQuery("#payment_method_bank_alfalah_gateway").prop("checked", true);
});

/**/
jQuery( "body" ).on( "click", ".payment_method_cod", function(){
    jQuery("#payment_method_bank_alfalah_gateway").prop("checked", false);
    jQuery("#alfalah-card, #alfalah-wallet, #alfalah-account,#BNPL,#card_on_delivery,#JazzCash,#RaastQr").prop("checked", false);
});
