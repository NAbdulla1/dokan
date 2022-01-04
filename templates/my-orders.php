<?php
/**
 * Template Name: My Orders
 */
?>


<?php
    global $woocommerce;

    $page       = empty( $_GET['pagenum'] ) ? 1 : (int) sanitize_text_field( wp_unslash( $_GET['pagenum'] ) );
    $limit      = 10;
    $start_date = empty( $_GET['start_date'] ) ? null : sanitize_text_field( wp_unslash( $_GET['start_date'] ) );
    $end_date   = empty( $_GET['end_date'] ) ? null : sanitize_text_field( wp_unslash( $_GET['end_date'] ) );
    $max_price  = empty( $_GET['max_price'] ) ? '' : absint( wp_unslash( $_GET['max_price'] ) );
    $min_price  = empty( $_GET['min_price'] ) ? '' : absint( wp_unslash( $_GET['min_price'] ) );
    $status     = empty( $_GET['status'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['status'] ) );
    $sort_order = empty( $_GET['sort_order'] ) ? 'DESC' : sanitize_text_field( wp_unslash( $_GET['sort_order'] ) );

    $customer_orders = dokan_get_filtered_orders( $start_date, $end_date, $min_price, $max_price, $sort_order, $limit, $page );
    $statuses = wc_get_order_statuses();

    $customer_orders = dokan_filter_orders_by_status( $customer_orders, $status );
    ?>

    <form method="GET" action="">
        <div id="dokan-my-orders-filter">
            <input type="text" name="start_date" class="datepicker" value="<?php echo esc_attr( $start_date ); ?>" autocomplete="off" placeholder="<?php esc_attr_e( 'Start Date', 'dokan-lite' ); ?>">
            <input type="text" name="end_date" class="datepicker" value="<?php echo esc_attr( $end_date ); ?>" autocomplete="off" placeholder="<?php esc_attr_e( 'End Date', 'dokan-lite' ); ?>">
            <input type="number" name="min_price" class="dokan-form-control" value="<?php echo esc_attr( $min_price ); ?>" placeholder="<?php esc_attr_e( 'Min Order Total', 'dokan-lite'); ?>">
            <input type="number" name="max_price" class="dokan-form-control" value="<?php echo esc_attr( $max_price ); ?>" placeholder="<?php esc_attr_e( 'Max Order Total', 'dokan-lite'); ?>">
            <select name="status" class="dokan-form-control" placeholder="<?php esc_attr_e( 'Filter by order status', 'dokan-lite' ); ?>">
                <option value="" <?php selected( '', $status ); ?>><?php esc_html_e( 'All', 'dokan-lite' ); ?></option>
                <?php foreach ( $statuses as $status_key => $status_text) : ?>
                <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status_key, $status ); ?>><?php echo esc_html( $status_text ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="sort_order" class="dokan-form-control">
                <option value="DESC" <?php selected( 'DESC', $sort_order ); ?>>Newer Orders First</option>
                <option value="ASC" <?php selected( 'ASC', $sort_order ); ?>>Older Orders First</option>
            </select>
        </div>
        <div class="dokan-form-group">
            <button type="submit" class="dokan-btn dokan-btn-info">Filter</button>
        </div>
    </form>

    <?php
    if ( $customer_orders ) : ?>

        <h2><?php echo esc_html( apply_filters( 'woocommerce_my_account_my_orders_title', __( 'Recent Orders', 'dokan-lite' ) ) ); ?></h2>

        <table class="shop_table my_account_orders table table-striped">

            <thead>
                <tr>
                    <th class="order-number"><span class="nobr"><?php esc_html_e( 'Order', 'dokan-lite' ); ?></span></th>
                    <th class="order-date"><span class="nobr"><?php esc_html_e( 'Date', 'dokan-lite' ); ?></span></th>
                    <th class="order-status"><span class="nobr"><?php esc_html_e( 'Status', 'dokan-lite' ); ?></span></th>
                    <th class="order-total"><span class="nobr"><?php esc_html_e( 'Total', 'dokan-lite' ); ?></span></th>
                    <th class="order-total"><span class="nobr"><?php esc_html_e( 'Item List', 'dokan-lite' ); ?></span></th>
                    <th class="order-total"><span class="nobr"><?php esc_html_e( 'Vendor', 'dokan-lite' ); ?></span></th>
                    <th class="order-actions">&nbsp;</th>
                </tr>
            </thead>

            <tbody><?php
                foreach ( $customer_orders as $order ) {
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
                            <?php
                            foreach ( $order->get_items() as $id => $product ) {
                                echo esc_html( sprintf( "%s x %d\n", $product->get_name(), $product->get_quantity() ) );
                            }
                            ?>
                        </td>

                        <td class="order-total">
                            <?php
                                $seller_id = dokan_get_seller_id_by_order( dokan_get_prop( $order, 'id' ) );
                                if ( !is_array( $seller_id ) && $seller_id != 0 ) {
                                    $sellershop = dokan_get_store_info( $seller_id );
                                    echo '<a href="'. esc_url( dokan_get_store_url( $seller_id ) ) .'">'. esc_html( $sellershop['store_name'] ) .'</a>';
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
        $customer_orders_count = count( dokan_get_filtered_orders( $start_date, $end_date, $min_price, $max_price ) );

        $num_of_pages = ceil( $customer_orders_count / $limit );

        $base_url  = get_permalink( $my_order_page_id = dokan_get_option( 'my_orders', 'dokan_pages' ) );

        if ( $num_of_pages > 1 ) {
            echo '<div class="pagination-wrap">';
            $page_links = paginate_links( [
                'current'   => $page,
                'total'     => $num_of_pages,
                'base'      => $base_url . '%_%',
                'format'    => '?pagenum=%#%',
                'add_args'  => false,
                'type'      => 'array',
            ] );

            echo "<ul class='pagination'>\n\t<li>";
            echo join( "</li>\n\t<li>", $page_links );
            echo "</li>\n</ul>\n";
            echo '</div>';
        }
        ?>
    <?php else: ?>

        <p class="dokan-info"><?php esc_html_e( 'No orders found!', 'dokan-lite' ); ?></p>

    <?php endif; ?>

<script>
    (function($){
        $(document).ready(function(){
            $('.datepicker').datepicker({
                dateFormat: 'yy-m-d'
            });
        });
    })(jQuery);
</script>
