<?php

namespace WeDevs\Dokan\Dashboard\Templates;

use WP_Error;

/**
 * Dokan settings Class
 *
 * @author weDves
 */
class Settings {

    public $currentuser;
    public $profile_info;

    /**
     * Loading autometically when class initiate
     *
     * @since 2.4
     *
     * @return void
     */
    function __construct() {
        add_action( 'dokan_settings_content_inside_before', array( $this, 'show_enable_seller_message' ) );
        add_action( 'dokan_settings_content_area_header', array( $this, 'render_settings_header' ), 10 );
        add_action( 'dokan_settings_content_area_header', array( $this, 'render_settings_help' ), 15 );
        add_action( 'dokan_settings_content_area_header', array( $this, 'render_settings_load_progressbar' ), 20 );
        add_action( 'dokan_settings_content_area_header', array( $this, 'render_settings_store_errors' ), 25 );
        add_action( 'dokan_settings_content', array( $this, 'render_settings_content' ), 10 );
    }

    /**
     * Show Seller Enable Error Message
     *
     * @since 2.4
     *
     * @return void
     */
    public function show_enable_seller_message() {
        $user_id = get_current_user_id();

        if ( ! dokan_is_seller_enabled( $user_id ) ) {
            dokan_seller_not_enabled_notice();
        }
    }
    /**
     * Render Settings Header
     *
     * @since 2.4
     *
     * @return void
     */
    public function render_settings_header() {
        global $wp;
        $is_store_setting = false;

        if ( isset( $wp->query_vars['settings'] ) && $wp->query_vars['settings'] == 'store' ) {
            $heading          = __( 'Settings', 'dokan-lite' );
            $is_store_setting = true;
        } elseif ( isset( $wp->query_vars['settings'] ) && 'payment' === substr( $wp->query_vars['settings'], 0, 7 ) ) {
            $heading = __( 'Payment Method', 'dokan-lite' );
            $slug    = str_replace( 'payment/manage-', '', $wp->query_vars['settings'] );
            $heading = $this->get_payment_heading( $slug, $heading );
        } else {
            $heading = apply_filters( 'dokan_dashboard_settings_heading_title', __( 'Settings', 'dokan-lite' ), $wp->query_vars['settings'] );
        }

        dokan_get_template_part( 'settings/header', '', array( 'heading' => $heading, 'is_store_setting' => $is_store_setting ) );
    }

    /**
     * Render Settings help
     *
     * @since 2.4
     *
     * @return void
     */
    public function render_settings_help() {
        global $wp;

        $help_text = '';

        if ( isset( $wp->query_vars['settings'] ) && $wp->query_vars['settings'] == 'payment' ) {
            $help_text = __( 'These are the withdraw methods available for you. Please update your payment information below to submit withdraw requests and get your store payments seamlessly.', 'dokan-lite' );
        }

        if ( $help_text = apply_filters( 'dokan_dashboard_settings_helper_text', $help_text, $wp->query_vars['settings'] ) ) {
            dokan_get_template_part( 'global/dokan-help', '', array(
                'help_text' => $help_text
            ) );
        }
    }

    /**
     * Render Settings Progressbar
     *
     * @since 2.4
     *
     * @return void
     */
    public function render_settings_load_progressbar() {
        ?>
            <div class="dokan-ajax-response">
                <?php do_action( 'dokan_settings_load_ajax_response' ); ?>
            </div>
        <?php
    }

    /**
     * Render Settings Store Errors
     *
     * @since 2.4
     *
     * @return void
     */
    public function render_settings_store_errors() {
        $validate = $this->validate();

        if ( is_wp_error( $validate ) ) {
            $messages = $validate->get_error_messages();

            foreach ( $messages as $message ) {
                dokan_get_template_part( 'global/dokan-error', '', array(
                    'message' => $message
                ) );
            }
        }
    }

    /**
     * Render Settings Content
     *
     * @since 2.4
     *
     * @return void
     */
    public function render_settings_content() {
        global $wp;

        if ( isset( $wp->query_vars['settings'] ) && 'store' == $wp->query_vars['settings'] ) {
            if ( ! current_user_can( 'dokan_view_store_settings_menu' ) ) {
                dokan_get_template_part('global/dokan-error', '', array(
                    'deleted' => false,
                    'message' => __( 'You have no permission to view this page', 'dokan-lite' )
                ) );
            } else {
                $this->load_store_content();
            }
        }

        if ( isset( $wp->query_vars['settings'] ) && 'payment' === substr( $wp->query_vars['settings'], 0, 7 ) ) {
            if ( ! current_user_can( 'dokan_view_store_payment_menu' ) ) {
                dokan_get_template_part('global/dokan-error', '', array(
                    'deleted' => false,
                    'message' => __( 'You have no permission to view this page', 'dokan-lite' )
                ) );
            } else {
                $this->load_payment_content( substr( $wp->query_vars['settings'], 7 ) );
            }
        }

        do_action( 'dokan_render_settings_content', $wp->query_vars );
    }

    /**
     * Load Store Content
     *
     * @since 2.4
     *
     * @return void
     */
    public function load_store_content() {
        $validate     = $this->validate();
        $currentuser  = dokan_get_current_user_id();
        $profile_info = dokan_get_store_info( dokan_get_current_user_id() );

        dokan_get_template_part( 'settings/store-form', '', array(
            'current_user' => $currentuser,
            'profile_info' => $profile_info,
            'validate'     => $validate,
        ) );

    }

    /**
     * Load Payment Content
     *
     * @since 2.4
     *
     * @param string $slug_suffix
     *
     * @return void
     */
    public function load_payment_content( $slug_suffix ) {
        $methods      = dokan_withdraw_get_active_methods();
        $currentuser  = dokan_get_current_user_id();
        $profile_info = dokan_get_store_info( dokan_get_current_user_id() );
        $method_key   = str_replace( '/manage-', '', $slug_suffix );
        $is_edit_mode = false;

        /**
         * This filter is only used if the Payment method has different key than the key used in meta value of
         * meta key 'dokan_profile_settings' for that Payment method
         *
         * @since DOKAN_LITE_SINCE
         */
        $mis_match_map = apply_filters( 'dokan_payment_method_storage_key', [] );

        $unused_methods = $this->dokan_get_unused_payment_methods( $methods, $profile_info['payment'], array_values( $mis_match_map ) );
        $unused_methods = array_reduce(
            $unused_methods,
            function ( $in_dropdown, $method_key ) {
                $cur_method = dokan_withdraw_get_method( $method_key );

                if ( ! empty( $cur_method ) ) {
                    $in_dropdown[ $method_key ] = $cur_method;
                }

                return $in_dropdown;
            },
            []
        );

        $methods = array_reduce(
            $methods,
            function ( $previous_array, $method_key ) use ( $mis_match_map, $profile_info ) {
                if ( ! isset( $profile_info['payment'][ $method_key ] ) && ! ( isset( $mis_match_map[ $method_key] ) && isset( $profile_info['payment'][ $mis_match_map[ $method_key ] ] ) ) ) {
                    return $previous_array;
                }

                $cur_method = dokan_withdraw_get_method( $method_key );

                if ( ! empty( $cur_method ) ) {
                    $previous_array[ $method_key ] = $cur_method;
                }

                return $previous_array;
            },
            []
        );

        if ( stripos( $method_key, '/edit' ) !== false ) {
            $is_edit_mode = true;
            $method_key   = str_replace( '/edit', '', $method_key );
        }

        if ( $is_edit_mode && 'bank' === $method_key ) {
            $profile_info['is_edit_method'] = $is_edit_mode;
        }

        $args = [
            'current_user' => $currentuser,
            'profile_info' => $profile_info,
        ];

        if ( empty( $method_key ) ) {
            $args = array_merge(
                $args,
                [
                    'methods'        => $methods,
                    'unused_methods' => $unused_methods,
                ]
            );

            if ( empty( $methods ) ) {
                dokan_get_template_part( 'global/dokan-error', '', array( 'deleted' => false, 'message' => __( 'No withdraw method is available. Please contact site admin.', 'dokan-lite' ) ) );
                return;
            }
        } else {
            $method = dokan_withdraw_get_method( $method_key );
            $args = array_merge(
                $args,
                [
                    'method'     => $method,
                    'method_key' => $method_key,
                ]
            );

            if ( empty( $method ) || ! isset( $method['callback'] ) || ! is_callable( $method['callback'] ) ) {
                dokan_get_template_part( 'global/dokan-error', '', array( 'deleted' => false, 'message' => __( 'Invalid withdraw method. Please contact site admin', 'dokan-lite' ) ) );
                return;
            }
        }

        dokan_get_template_part( 'settings/payment', ! empty( $method_key ) ? 'manage' : '', $args );
    }

    /**
     * Save settings via ajax
     *
     * @since 2.4
     *
     * @return void
     */
    function ajax_settings() {

        if ( ! dokan_is_user_seller( get_current_user_id() ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'dokan-lite' ) );
        }

        if ( ! isset( $_POST['_wpnonce'] ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'dokan-lite' ) );
        }

        $post_data = wp_unslash( $_POST );

        $post_data['dokan_update_profile'] = '';

        switch ( $post_data['form_id'] ) { // WPCS: CSRF ok.
            case 'profile-form':
                if ( ! current_user_can( 'dokan_view_store_social_menu' ) ) {
                    wp_send_json_error( __( 'Pemission denied social', 'dokan-lite' ) );
                }

                if ( ! wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_profile_settings_nonce' ) ) {
                    wp_send_json_error( __( 'Are you cheating?', 'dokan-lite' ) );
                }

                $ajax_validate = $this->profile_validate();
                break;

            case 'store-form':
                if ( ! current_user_can( 'dokan_view_store_settings_menu' ) ) {
                    wp_send_json_error( __( 'Pemission denied', 'dokan-lite' ) );
                }

                if ( ! wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_store_settings_nonce' ) ) {
                    wp_send_json_error( __( 'Are you cheating?', 'dokan-lite' ) );
                }

                $ajax_validate = $this->store_validate();
                break;

            case 'payment-form':
                if ( ! current_user_can( 'dokan_view_store_payment_menu' ) ) {
                    wp_send_json_error( __( 'Pemission denied', 'dokan-lite' ) );
                }

                if ( ! wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_payment_settings_nonce' ) ) {
                    wp_send_json_error( __( 'Are you cheating?', 'dokan-lite' ) );
                }

                $ajax_validate = $this->payment_validate();
                break;
            default:
                $ajax_validate = new WP_Error( 'form_id_not_matched', __( 'Failed to process data, invalid submission', 'dokan-lite' ) );
        }

        if ( is_wp_error( $ajax_validate ) ) {
            wp_send_json_error( $ajax_validate->get_error_messages() );
        }

        // we are good to go
        $save_data = $this->insert_settings_info();

        $success_msg = __( 'Your information has been saved successfully', 'dokan-lite' );

        $data = apply_filters( 'dokan_ajax_settings_response', array(
            'msg' => $success_msg,
        ) );

        wp_send_json_success( $data );
    }

    /**
     * Validate settings submission
     *
     * @return void
     */
    function validate() {

        $post_data = wp_unslash( $_POST );

        if ( ! isset( $post_data['dokan_update_profile'] ) ) {
            return false;
        }

        if ( ! isset( $post_data['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_settings_nonce' ) ) {
            wp_die( esc_attr__( 'Are you cheating?', 'dokan-lite' ) );
        }

        $error = new WP_Error();

        $dokan_name = sanitize_text_field( $post_data['dokan_store_name'] );

        if ( empty( $dokan_name ) ) {
            $error->add( 'dokan_name', __( 'Store name required', 'dokan-lite' ) );
        }

        if ( isset( $post_data['setting_category'] ) ) {

            if ( ! is_array( $post_data['setting_category'] ) || ! count( $post_data['setting_category'] ) ) {
                $error->add( 'dokan_type', __( 'Store type required', 'dokan-lite' ) );
            }
        }

        if ( ! empty( $post_data['setting_paypal_email'] ) ) {
            $email = sanitize_email( $post_data['setting_paypal_email'] );

            if ( empty( $email ) ) {
                $error->add( 'dokan_email', __( 'Invalid email', 'dokan-lite' ) );
            }
        }

        /* Address Fields Validation */
        $required_fields = array(
            'street_1',
            'city',
            'zip',
            'country',
        );

        if ( $post_data['dokan_address']['state'] != 'N/A' ) {
            $required_fields[] = 'state';
        }

        foreach ( $required_fields as $key ) {
            if ( empty( $post_data['dokan_address'][ $key ] ) ) {
                $code = 'dokan_address[' . $key . ']';
                $error->add( $code, sprintf( __( 'Address field for %s is required', 'dokan-lite' ), $key ) );
            }
        }

        if ( $error->get_error_codes() ) {
            return $error;
        }

        return true;
    }

    /**
     * Validate profile settings
     *
     * @return bool|WP_Error
     */
    function profile_validate() {

        $post_data = wp_unslash( $_POST );

        if ( ! isset( $post_data['dokan_update_profile_settings'] ) ) {
            return false;
        }

        if ( ! isset( $post_data['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_profile_settings_nonce' ) ) {
            wp_die( esc_attr__( 'Are you cheating?', 'dokan-lite' ) );
        }

        $error = new WP_Error();

        if ( isset( $post_data['setting_category'] ) ) {

            if ( ! is_array( $post_data['setting_category'] ) || ! count( $post_data['setting_category'] ) ) {
                $error->add( 'dokan_type', __( 'Store type required', 'dokan-lite' ) );
            }
        }

        if ( ! empty( $_POST['setting_paypal_email'] ) ) {
            $email = sanitize_email( $post_data['setting_paypal_email'] );

            if ( empty( $email ) ) {
                $error->add( 'dokan_email', __( 'Invalid email', 'dokan-lite' ) );
            }
        }

        if ( $error->get_error_codes() ) {
            return $error;
        }

        return true;
    }

    /**
     * Validate store settings
     *
     * @return bool|WP_Error
     */
    function store_validate() {

        $post_data = wp_unslash( $_POST );

        if ( ! isset( $post_data['dokan_update_store_settings'] ) ) {
            return false;
        }

        if ( ! isset( $post_data['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_store_settings_nonce' ) ) {
            wp_die( esc_attr__( 'Are you cheating?', 'dokan-lite' ) );
        }

        $error = new WP_Error();

        $dokan_name = sanitize_text_field( $post_data['dokan_store_name'] );

        if ( empty( $dokan_name ) ) {
            $error->add( 'dokan_name', __( 'Store name required', 'dokan-lite' ) );
        }

        if ( isset( $_POST['setting_category'] ) ) {

            if ( ! is_array( $_POST['setting_category'] ) || ! count( $post_data['setting_category'] ) ) {
                $error->add( 'dokan_type', __( 'Store type required', 'dokan-lite' ) );
            }
        }

        if ( ! empty( $post_data['setting_paypal_email'] ) ) {
            $email = sanitize_email( $post_data['setting_paypal_email'] );

            if ( empty( $email ) ) {
                $error->add( 'dokan_email', __( 'Invalid email', 'dokan-lite' ) );
            }
        }

        if ( $error->get_error_codes() ) {
            return $error;
        }

        return true;

    }

    /**
     * validate payment settings
     *
     * @since 2.4
     *
     * @return bool|WP_Error
     */
    function payment_validate() {

        $post_data = wp_unslash( $_POST );

        if ( ! isset( $post_data['dokan_update_payment_settings'] ) ) {
            return false;
        }

        if ( ! isset( $post_data['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_payment_settings_nonce' ) ) {
            wp_die( esc_attr__( 'Are you cheating?', 'dokan-lite' ) );
        }

        $error = new WP_Error();

        if ( ! empty( $post_data['settings']['paypal'] ) && isset( $post_data['settings']['paypal']['email'] ) ) {
            $email = sanitize_email( $post_data['settings']['paypal']['email'] );

            if ( empty( $email ) ) {
                $error->add( 'dokan_email', __( 'Invalid email', 'dokan-lite' ) );
            }
        }

        if ( ! empty( $post_data['settings']['skrill'] ) && isset( $post_data['settings']['skrill']['email'] ) ) {
            $email = sanitize_email( $post_data['settings']['skrill']['email'] );

            if ( empty( $email ) ) {
                $error->add( 'dokan_email', __( 'Invalid email', 'dokan-lite' ) );
            }
        }

        if ( ! empty( $post_data['settings']['bank'] ) ) {
            if ( empty( $post_data['settings']['bank']['ac_name'] ) ) {
                $error->add( 'dokan_bank_ac_name', __( 'Account holder name is required', 'dokan-lite' ) );
            }

            if ( empty( $post_data['settings']['bank']['ac_number'] ) ) {
                $error->add( 'dokan_bank_ac_number', __( 'Account number is required', 'dokan-lite' ) );
            }

            if ( empty( $post_data['settings']['bank']['routing_number'] ) ) {
                $error->add( 'dokan_bank_ac_routing_number', __( 'Routing number is required', 'dokan-lite' ) );
            }

            if ( empty( $post_data['settings']['bank']['ac_type'] ) ) {
                $error->add( 'dokan_bank_ac_type', __( 'Please select account type', 'dokan-lite' ) );
            } else if ( ! in_array( $post_data['settings']['bank']['ac_type'], [ 'personal', 'business' ] ) ) {
                $error->add( 'dokan_bank_ac_type', __( 'Invalid Account Type', 'dokan-lite' ) );
            }

            if ( empty( $post_data['settings']['bank']['declaration'] ) ) {
                $error->add( 'dokan_bank_declaration', __( 'You must attest that the bank account is yours.', 'dokan-lite' ) );
            }
        }

        if ( $error->get_error_codes() ) {
            return $error;
        }

        return true;

    }

    /**
     * Save store settings
     *
     * @return void
     */
    function insert_settings_info() {
        $store_id                = dokan_get_current_user_id();
        $existing_dokan_settings = get_user_meta( $store_id, 'dokan_profile_settings', true );
        $prev_dokan_settings     = ! empty( $existing_dokan_settings ) ? $existing_dokan_settings : array();
        $post_data               = wp_unslash( $_POST );

        if ( ! isset( $post_data['_wpnonce'] ) ) {
            return;
        }

        if ( wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_profile_settings_nonce' ) ) {

            // update profile settings info
            $social         = $post_data['settings']['social'];
            $social_fields  = dokan_get_social_profile_fields();
            $dokan_settings = array( 'social' => array() );

            if ( is_array( $social ) ) {
                foreach ( $social as $key => $value ) {
                    if ( isset( $social_fields[ $key ] ) ) {
                        $dokan_settings['social'][ $key ] = esc_url_raw( $social[ $key ] );
                    }
                }
            }

        } elseif ( wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_store_settings_nonce' ) ) {

            $default_locations = dokan_get_option( 'location', 'dokan_geolocation' );

            if ( ! is_array( $default_locations ) || empty( $default_locations ) ) {
                $default_locations = array(
                    'latitude'  => '',
                    'longitude' => '',
                    'address'   => '',
                );
            }

            $find_address      = ! empty( $post_data['find_address'] ) ? sanitize_text_field( $post_data['find_address'] ) : $default_locations['address'];
            $default_location  = $default_locations['latitude'] . ',' . $default_locations['longitude'];
            $location          = ! empty( $post_data['find_address'] ) ? sanitize_text_field( $post_data['location'] ) : $default_location;

            // update store setttings info
            $dokan_settings = array(
                'store_name'               => sanitize_text_field( $post_data['dokan_store_name'] ),
                'store_ppp'                => absint( $post_data['dokan_store_ppp'] ),
                'address'                  => isset( $post_data['dokan_address'] ) ? array_map( 'sanitize_text_field', $post_data['dokan_address'] ) : $prev_dokan_settings['address'],
                'location'                 => $location,
                'find_address'             => $find_address,
                'banner'                   => isset( $post_data['dokan_banner'] ) ? absint( $post_data['dokan_banner'] ) : 0,
                'phone'                    => sanitize_text_field( $post_data['setting_phone'] ),
                'show_email'               => sanitize_text_field( $post_data['setting_show_email'] ),
                'show_more_ptab'           => sanitize_text_field( $post_data['setting_show_more_ptab'] ),
                'gravatar'                 => isset( $post_data['dokan_gravatar'] ) ? absint( $post_data['dokan_gravatar'] ) : 0,
                'enable_tnc'               => isset( $post_data['dokan_store_tnc_enable'] ) && 'on' == $post_data['dokan_store_tnc_enable'] ? 'on' : 'off',
                'store_tnc'                => isset( $post_data['dokan_store_tnc'] ) ? wp_kses_post( $post_data['dokan_store_tnc'] ) : '',
                'dokan_store_time'         => array(
                    'sunday'               => array(
                        'status'           => isset( $post_data['sunday_on_off'] ) && 'open' == $post_data['sunday_on_off'] ? 'open' : 'close',
                        'opening_time'     => isset( $post_data['sunday_opening_time'] ) ? sanitize_text_field( $post_data['sunday_opening_time'] ) : '',
                        'closing_time'     => isset( $post_data['sunday_closing_time'] ) ? sanitize_text_field( $post_data['sunday_closing_time'] ) : '',
                    ),
                    'monday'               => array(
                        'status'           => isset( $post_data['monday_on_off'] ) && 'open' == $post_data['monday_on_off'] ? 'open' : 'close',
                        'opening_time'     => isset( $post_data['monday_opening_time'] ) ? sanitize_text_field( $post_data['monday_opening_time'] ) : '',
                        'closing_time'     => isset( $post_data['monday_closing_time'] ) ? sanitize_text_field( $post_data['monday_closing_time'] ) : '',
                    ),
                    'tuesday'              => array(
                        'status'           => isset( $post_data['tuesday_on_off'] ) && 'open' == $post_data['tuesday_on_off'] ? 'open' : 'close',
                        'opening_time'     => isset( $post_data['tuesday_opening_time'] ) ? sanitize_text_field( $post_data['tuesday_opening_time'] ) : '',
                        'closing_time'     => isset( $post_data['tuesday_closing_time'] ) ? sanitize_text_field( $post_data['tuesday_closing_time'] ) : '',
                    ),
                    'wednesday'            => array(
                        'status'           => isset( $post_data['wednesday_on_off'] ) && 'open' == $post_data['wednesday_on_off'] ? 'open' : 'close',
                        'opening_time'     => isset( $post_data['wednesday_opening_time'] ) ? sanitize_text_field( $post_data['wednesday_opening_time'] ) : '',
                        'closing_time'     => isset( $post_data['wednesday_closing_time'] ) ? sanitize_text_field( $post_data['wednesday_closing_time'] ) : '',
                    ),
                    'thursday'             => array(
                        'status'           => isset( $post_data['thursday_on_off'] ) && 'open' == $post_data['thursday_on_off'] ? 'open' : 'close',
                        'opening_time'     => isset( $post_data['thursday_opening_time'] ) ? sanitize_text_field( $post_data['thursday_opening_time'] ) : '',
                        'closing_time'     => isset( $post_data['thursday_closing_time'] ) ? sanitize_text_field( $post_data['thursday_closing_time'] ) : '',
                    ),
                    'friday'               => array(
                        'status'           => isset( $post_data['friday_on_off'] ) && 'open' == $post_data['friday_on_off'] ? 'open' : 'close',
                        'opening_time'     => isset( $post_data['friday_opening_time'] ) ? sanitize_text_field( $post_data['friday_opening_time'] ) : '',
                        'closing_time'     => isset( $post_data['friday_closing_time'] ) ? sanitize_text_field( $post_data['friday_closing_time'] ) : '',
                    ),
                    'saturday'             => array(
                        'status'           => isset( $post_data['saturday_on_off'] ) && 'open' == $post_data['saturday_on_off'] ? 'open' : 'close',
                        'opening_time'     => isset( $post_data['saturday_opening_time'] ) ? sanitize_text_field( $post_data['saturday_opening_time'] ) : '',
                        'closing_time'     => isset( $post_data['saturday_closing_time'] ) ? sanitize_text_field( $post_data['saturday_closing_time'] ) : '',
                    ),
                ),
                'dokan_store_time_enabled' => isset( $post_data['dokan_store_time_enabled'] ) && 'yes' == $post_data['dokan_store_time_enabled'] ? 'yes' : 'no',
                'dokan_store_open_notice'  => isset( $post_data['dokan_store_open_notice'] ) ? sanitize_textarea_field( $post_data['dokan_store_open_notice'] ) : '',
                'dokan_store_close_notice' => isset( $post_data['dokan_store_close_notice'] ) ? sanitize_textarea_field( $post_data['dokan_store_close_notice'] ) : '',
            );

            update_user_meta( $store_id, 'dokan_store_name', $dokan_settings['store_name'] );

        } elseif ( wp_verify_nonce( sanitize_key( $post_data['_wpnonce'] ), 'dokan_payment_settings_nonce' ) ) {

            //update payment settings info
            $dokan_settings = array(
                'payment' => $prev_dokan_settings['payment'],
            );

            if ( isset( $post_data['settings']['bank'] ) ) {
                $bank = $post_data['settings']['bank'];

                $dokan_settings['payment']['bank'] = array(
                    'ac_name'        => sanitize_text_field( $bank['ac_name'] ),
                    'ac_number'      => sanitize_text_field( $bank['ac_number'] ),
                    'bank_name'      => sanitize_text_field( $bank['bank_name'] ),
                    'bank_addr'      => sanitize_text_field( $bank['bank_addr'] ),
                    'routing_number' => sanitize_text_field( $bank['routing_number'] ),
                    'iban'           => sanitize_text_field( $bank['iban'] ),
                    'swift'          => sanitize_text_field( $bank['swift'] ),
                    'ac_type'        => sanitize_text_field( $bank['ac_type'] ),
                    'declaration'    => sanitize_text_field( $bank['declaration'] ),
                );
            }

            if ( isset( $post_data['settings']['paypal'] ) ) {
                $dokan_settings['payment']['paypal'] = array(
                    'email' => sanitize_email( $post_data['settings']['paypal']['email'] ),
                );
            }
        }

        $dokan_settings = array_merge( $prev_dokan_settings, $dokan_settings );

        $dokan_settings = apply_filters( 'dokan_store_profile_settings_args', $dokan_settings, $store_id );

        update_user_meta( $store_id, 'dokan_profile_settings', $dokan_settings );

        do_action( 'dokan_store_profile_saved', $store_id, $dokan_settings );

        if ( ! defined( 'DOING_AJAX' ) ) {
            $_GET['message'] = 'profile_saved';
        }
    }

    /**
     * Dokan Get Category Format
     *
     * @since 1.0
     *
     * @return array
     */
    function get_dokan_categories() {
        $dokan_category = array(
            'book'       => __( 'Book', 'dokan-lite' ),
            'dress'      => __( 'Dress', 'dokan-lite' ),
            'electronic' => __( 'Electronic', 'dokan-lite' ),
        );

        return apply_filters( 'dokan_category', $dokan_category );
    }

    /**
     * Get proper heading for payments of vendor dashboard payment settings
     *
     * @since DOKAN_LITE_SINCE
     *
     * @param string $slug
     * @param string $heading
     *
     * @return string
     */
    private function get_payment_heading( $slug, $heading ) {
        switch ( $slug ) {
            case 'bank':
            case 'bank/edit':
                $heading = __( 'Bank Account Settings', 'dokan-lite' );
                break;

            case 'paypal':
            case 'paypal/edit':
                $heading = __( 'Paypal Settings', 'dokan-lite' );
                break;
        }

        /**
         * To allow new payment extension give their own heading
         *
         * @since DOKAN_LITE_SINCE
         *
         * @param string $heading previous heading
         */
        $heading = apply_filters( "dokan_withdraw_method_settings_title", $heading, $slug );

        return $heading;
    }

    /**
     * Get unused payment methods
     *
     * @since DOKAN_SINCE
     *
     * @param array $methods           All methods
     * @param array $profile_methods   Used Methods
     * @param array $mis_match_methods The list of methods which has different key and name but the name is substring of key
     *
     * @return array
     */
    private function dokan_get_unused_payment_methods( $methods, $profile_methods, $mis_match_methods ) {
        $profile_methods = array_keys( $profile_methods );
        $unused_methods  = array_diff( $methods, $profile_methods );

        $mis_match_methods = array_filter(
            $mis_match_methods,
            function ( $key ) use ( $profile_methods ) {
                return in_array( $key, $profile_methods, true );
            }
        );

        return array_filter(
            $unused_methods,
            function ( $key ) use ( $mis_match_methods ) {
                $found = false;

                foreach ( $mis_match_methods as $mis_match_method ) {
                    $found = $found || ( false !== stripos( $key, $mis_match_method ) );
                }

                return ! $found;
            }
        );
    }
}
