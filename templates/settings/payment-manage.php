<?php
/**
 * Dokan Settings Payment Template
 *
 * @package dokan
 */
?>

<form method="post" id="payment-form"  action="" class="dokan-form-horizontal">

    <?php wp_nonce_field( 'dokan_payment_settings_nonce' );

    if ( ! empty( $method ) && isset( $method['callback'] ) && is_callable( $method['callback'] ) ) : ?>
        <fieldset class="payment-field-<?php echo esc_attr( $method_key ); ?>">
            <div class="dokan-form-group">
                <?php if ( 'bank' === $method_key ) :
                    call_user_func( $method['callback'], $profile_info );
                else : ?>
                    <label class="dokan-w3 dokan-control-label" for="dokan_setting"><?php echo esc_html( $method['title'] ) ?></label>
                    <div class="dokan-w6">
                        <?php call_user_func( $method['callback'], $profile_info ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </fieldset>

        <?php
        /**
         * @since DOKAN_LITE_SINCE Insert action on botton of payment settings form
         */
        do_action( 'dokan_payment_settings_form_bottom', $current_user, $profile_info );

        if ( 'bank' !== $method_key ) : ?>
            <div class="dokan-form-group">
                <div class="dokan-w4 ajax_prev dokan-text-left">
                    <input type="submit" name="dokan_update_payment_settings" class="dokan-btn dokan-btn-danger dokan-btn-theme" value="<?php esc_attr_e( 'Update Settings', 'dokan-lite' ); ?>">
                </div>
            </div>
        <?php endif; ?>
</form>
    <?php
    else:
        dokan_get_template_part( 'global/dokan-error', '', array( 'deleted' => false, 'message' => __( 'Invalid withdraw method. Please contact site admin', 'dokan-lite' ) ) );
    endif;
?>
