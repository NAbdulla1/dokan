<?php
/**
 * Template Name: My Orders
 */
?>


<?php
    global $woocommerce;

    $page       = empty( $_GET['pagenum'] ) ? 1 : (int) sanitize_text_field( wp_unslash( $_GET['pagenum'] ) );
    $limit      = 20;
    $start_date = empty( $_GET['start_date'] ) ? null : sanitize_text_field( wp_unslash( $_GET['start_date'] ) );
    $end_date   = empty( $_GET['end_date'] ) ? null : sanitize_text_field( wp_unslash( $_GET['end_date'] ) );
    $sort_order = empty( $_GET['sort_order'] ) ? 'DESC' : sanitize_text_field( wp_unslash( $_GET['sort_order'] ) );
    $vendor_id  = empty( $_GET['vendor'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['vendor'] ) );

    $statuses = wc_get_order_statuses();

    list( $customer_orders, $total_pages ) = dokan_get_orders( [
        'start_date' => $start_date,
        'end_date'   => $end_date,
        'vendor_id'  => $vendor_id,
        'sort_order' => $sort_order,
        'limit'      => $limit,
        'page'       => $page,
    ] );

    $vendors = dokan()->vendor->get_vendors( [ 'number' => -1 ] );
    ?>

    <form method="GET" action="">
        <div id="dokan-my-orders-filter">
            <input type="hidden" value="<?php echo esc_attr( $sort_order ); ?>" name="sort_order">
            <div class="dokan-form-group">
                <select name="vendor" class="dokan-form-control dokan-my-order-select2">
                    <option value="" <?php selected( '', $vendor_id ); ?>><?php esc_html_e( 'All Vendors', 'dokan-lite' ); ?></option>
                    <?php foreach ( $vendors as $vendor ) :
                        $shop_info = dokan_get_store_info( $vendor->id );?>
                        <option value="<?php echo esc_attr( $vendor->id ); ?>" <?php selected( $vendor->id, $vendor_id ); ?>><?php echo esc_html( $shop_info['store_name'] ); ?></option>
                    <?php endforeach; ?>
                </select>

                <input autocomplete="off" id="my_order_date_range" type="text" class="dokan-form-control" placeholder="<?php esc_attr_e( 'Select Date Range', 'dokan-lite' ); ?>" value="<?php echo esc_attr( $start_date && $end_date ? dokan_format_date( $start_date ) . ' - ' . dokan_format_date( $end_date ) : null ); ?>">
                <input type="hidden" name="start_date" class="datepicker dokan-form-control" value="<?php echo esc_attr( $start_date ); ?>">
                <input type="hidden" name="end_date" class="datepicker dokan-form-control" value="<?php echo esc_attr( $end_date ); ?>">
            </div>
        </div>
        <div class="dokan-form-group">
            <button type="submit" class="dokan-btn dokan-btn-info"><span class="fa fa-filter"></span> <?php esc_html_e( 'Filter', 'dokan-lite' ); ?></button>
            <a id="dokan-my-order-filter-reset" class="dokan-btn"><span class="fa fa-undo"></span> <?php esc_html_e( 'Reset', 'dokan-lite' ); ?></a>
        </div>
    </form>

    <?php
    if ( $customer_orders ) : ?>

        <h2><?php echo esc_html( apply_filters( 'woocommerce_my_account_my_orders_title', __( 'Recent Orders', 'dokan-lite' ) ) ); ?></h2>

        <table class="shop_table my_account_orders table table-striped">

            <thead>
                <tr>
                    <th class="order-number"><span class="nobr <?php echo $sort_order === 'ASC' ? esc_attr( 'rotated' ) : ''; ?>"><?php esc_html_e( 'Order', 'dokan-lite' ); ?></span></th>
                    <th class="order-date"><span class="nobr"><?php esc_html_e( 'Date', 'dokan-lite' ); ?></span></th>
                    <th class="order-status"><span class="nobr"><?php esc_html_e( 'Status', 'dokan-lite' ); ?></span></th>
                    <th class="order-total"><span class="nobr"><?php esc_html_e( 'Total', 'dokan-lite' ); ?></span></th>
                    <th class="order-total"><span class="nobr"><?php esc_html_e( 'Items List', 'dokan-lite' ); ?></span></th>
                    <th class="order-total"><span class="nobr"><?php esc_html_e( 'Vendor', 'dokan-lite' ); ?></span></th>
                    <th class="order-actions">&nbsp;</th>
                </tr>
            </thead>

            <tbody><?php
                foreach ( $customer_orders as $customer_order ) {
                    $order      = wc_get_order( $customer_order );
                    $item_count = $order->get_item_count();

                    ?><tr class="order">
                        <td class="order-number">
                            <a href="<?php echo esc_attr( $order->get_view_order_url() ); ?>">
                                <?php echo esc_html( $order->get_order_number() ); ?>
                            </a>
                        </td>
                        <td class="order-date">
                            <time datetime="<?php echo esc_attr( date('Y-m-d', strtotime( dokan_get_date_created( $order ) ) ) ); ?>" title="<?php echo esc_attr( strtotime( dokan_get_date_created( $order ) ) ); ?>"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( dokan_get_date_created( $order ) ) ) ); ?></time>
                        </td>
                        <td class="order-status" style="text-align:left; white-space:nowrap;">
                            <?php echo isset( $statuses['wc-'.dokan_get_prop( $order, 'status' )] ) ? esc_html( $statuses['wc-'.dokan_get_prop( $order, 'status' )] ) : esc_html( dokan_get_prop( $order, 'status' ) ); ?>
                        </td>
                        <td class="order-total">
                            <?php echo wp_kses_post( sprintf( _n( '%s for %s item', '%s for %s items', $item_count, 'dokan-lite' ), $order->get_formatted_order_total(), $item_count ) ); ?>
                        </td>
                        <td class="order-total">
                            <?php foreach ( $order->get_items() as $product ) : ?>
                                <?php echo esc_html( sprintf( "%s x %d\n", $product->get_name(), $product->get_quantity() ) ); ?>
                            <?php endforeach; ?>
                        </td>

                        <td class="order-total">
                            <?php
                                $seller_id = dokan_get_seller_id_by_order( dokan_get_prop( $order, 'id' ) );
                                if ( !is_array( $seller_id ) && $seller_id != 0 ) {
                                    $shop_info = dokan_get_store_info( $seller_id );
                                    echo '<a href="'. esc_url( dokan_get_store_url( $seller_id ) ) .'">'. esc_html( $shop_info['store_name'] ) .'</a>';
                                } else {
                                    esc_html_e( 'Multiple Vendor', 'dokan-lite' );
                                }
                            ?>
                        </td>

                        <td class="order-actions">
                            <?php
                                $actions = array();

                                if ( in_array( dokan_get_prop( $order, 'status' ), apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $order ) ) )
                                    $actions['pay'] = array(
                                        'url'  => $order->get_checkout_payment_url(),
                                        'name' => __( 'Pay', 'dokan-lite' )
                                    );

                                if ( in_array( dokan_get_prop( $order, 'status' ), apply_filters( 'woocommerce_valid_order_statuses_for_cancel', array( 'pending', 'failed' ), $order ) ) )
                                    $actions['cancel'] = array(
                                        'url'  => $order->get_cancel_order_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ),
                                        'name' => __( 'Cancel', 'dokan-lite' )
                                    );

                                $actions['view'] = array(
                                    'url'  => $order->get_view_order_url(),
                                    'name' => __( 'View', 'dokan-lite' )
                                );

                                $actions = apply_filters( 'woocommerce_my_account_my_orders_actions', $actions, $order );

                                foreach( $actions as $key => $action ) {
                                    echo '<a href="' . esc_url( $action['url'] ) . '" class="button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a>';
                                }
                            ?>
                        </td>
                    </tr><?php
                }
            ?></tbody>

        </table>

    <?php
        $base_url  = get_permalink( $my_order_page_id = dokan_get_option( 'my_orders', 'dokan_pages' ) );

        if ( $total_pages > 1 ) :
            $page_links = paginate_links( [
                'current'   => $page,
                'total'     => $total_pages,
                'base'      => $base_url . '%_%',
                'format'    => '?pagenum=%#%',
                'add_args'  => false,
                'type'      => 'array',
            ] );
            ?>
            <div class="pagination-wrap">
                <ul class='pagination'>
                    <li>
                        <?php echo join( "</li>\n\t<li>", $page_links ); ?>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    <?php else: ?>

        <p class="dokan-info"><?php esc_html_e( 'No orders found!', 'dokan-lite' ); ?></p>

    <?php endif; ?>
