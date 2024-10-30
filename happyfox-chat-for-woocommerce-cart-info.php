<?php

add_action('wp_ajax_integration_info', 'get_integration_info');
add_action('wp_ajax_nopriv_integration_info', 'get_integration_info');

function get_integration_info() {
  $integration_info = array(
    'cartInfo' => get_cart_info(),
    'ordersInfo' => array(
      'contact' => get_contact_info(),
      'orders' => get_orders_info()
    )
  );

  if (count($integration_info['cartInfo']) == 0) {
    $integration_info['cartInfo'] = null;
  }

  $orders = $integration_info['ordersInfo'];

  if (count($orders['orders']) == 0) {
    $integration_info['ordersInfo'] = null;
  }

  echo json_encode($integration_info);
  wp_die();
}

function get_cart_info() {
  $currencyCode = get_woocommerce_currency();
  $cartItems;

  foreach(WC()->cart->cart_contents as $product) {
    if (array_key_exists('data', $product)) {
      $wc_product = wc_get_product($product['data']);
      $url = htmlspecialchars_decode($wc_product->get_permalink());
      $title = $wc_product->get_title();

      // $cartItems[] = array(
      //   'url' => $url,
      //   'title' => $title,
      //   'quantity' => $product['quantity'],
      //   'price' => $product['line_subtotal'],
      //   'currency' => $currencyCode
      // );

      $data = array(
        'post' => array(
          'post_title' => $title
        ) ,
        'price' => $wc_product->get_price(),
        'currency' => $currencyCode
      );

      $cartItems[] = array(
        'data' => $data,
        'quantity' => $product['quantity'],
      );
    }
  }

  return $cartItems;
}

function isAuthenticatedRequest() {

  if (empty($_POST['consumerSecret'])) {
    return false;
  } else {
    $ckey = ($_POST['consumerSecret']);
    global $wpdb;
    $result = $wpdb->get_var($wpdb->prepare(" SELECT consumer_secret
    FROM wp_woocommerce_api_keys
    WHERE consumer_secret = %s", $ckey));
    return ($_POST['consumerSecret'] == $result);
  }
}

function get_orders_info() {
  if (isAuthenticatedRequest()) {
    $woo_version = get_woo_version_number();

    if ($woo_version >= 2.2) {
      return get_orders_info_latest();
    } else if ($woo_version < 2.2) {
      return get_orders_info_old();
    } else {
      return null;
    }
  } else {
    return null;
  }
}

function get_orders_info_latest() {
  $email = get_customer_email();
  if (!$email) {
    return array();
  }

  $customer_orders = get_posts(array(
    'post_type' => 'shop_order',
    'post_status' => array_keys(wc_get_order_statuses()),
    'meta_key' => '_customer_user',
    'posts_per_page' => '-1',
    'meta_query' => array(
      array(
        'key' => '_billing_email',
        'value' => urldecode($email),
        'compare' => 'LIKE'
      )
    )
  ));
  $orders = array();

  foreach ($customer_orders as $customer_order) {
    if ($customer_order->post_type === 'shop_order') {
      $orders[] = get_order_details($customer_order->ID);
    }
  }
  return ($orders);
}

function get_orders_info_old() {
  $email = get_customer_email();
  if (!$email) {
    return array();
  }
  $args = array(
    'post_type' => 'shop_order',
    'post_status' => 'publish',
    'meta_key' => '_customer_user',
    'posts_per_page' => '-1',
    'meta_query' => array(
      array(
        'key' => '_billing_email',
        'value' => urldecode($email),
        'compare' => 'LIKE'
      )
    )
  );
  $my_query = new WP_Query($args);
  $customer_orders = $my_query->posts;
  $orders = array();

  foreach ($customer_orders as $customer_order) {
    if ($customer_order->post_type === 'shop_order') {
      $orders[] = get_order_details($customer_order->ID);
    }
  }
  return ($orders);
}

function get_order_details($id) {
  // Get the decimal precession

  $dp = (isset($filter['dp']) ? intval($filter['dp']) : 2);
  $order = wc_get_order($id);
  $order_post = get_post($id);
  $order_data = array(
    'id' => $order->id,
    'order_number' => $order->get_order_number(),
    'created_at' => $order_post->post_date_gmt,
    'updated_at' => $order_post->post_modified_gmt,
    'completed_at' => $order->completed_date,
    'status' => $order->get_status(),
    'currency' => $order->get_order_currency(),
    'total' => wc_format_decimal($order->get_total(), $dp),
    'subtotal' => wc_format_decimal($order->get_subtotal(), $dp),
    'total_line_items_quantity' => $order->get_item_count(),
    'total_tax' => wc_format_decimal($order->get_total_tax(), $dp),
    'total_shipping' => wc_format_decimal($order->get_total_shipping(), $dp),
    'cart_tax' => wc_format_decimal($order->get_cart_tax(), $dp),
    'shipping_tax' => wc_format_decimal($order->get_shipping_tax(), $dp),
    'total_discount' => wc_format_decimal($order->get_total_discount(), $dp),
    'shipping_methods' => $order->get_shipping_method(),
    'payment_details' => array(
      'method_id' => $order->payment_method,
      'method_title' => $order->payment_method_title,
      'paid' => isset($order->paid_date)
    ),
    'billing_address' => array(
      'first_name' => $order->billing_first_name,
      'last_name' => $order->billing_last_name,
      'company' => $order->billing_company,
      'address_1' => $order->billing_address_1,
      'address_2' => $order->billing_address_2,
      'city' => $order->billing_city,
      'state' => $order->billing_state,
      'postcode' => $order->billing_postcode,
      'country' => $order->billing_country,
      'email' => $order->billing_email,
      'phone' => $order->billing_phone
    ),
    'shipping_address' => array(
      'first_name' => $order->shipping_first_name,
      'last_name' => $order->shipping_last_name,
      'company' => $order->shipping_company,
      'address_1' => $order->shipping_address_1,
      'address_2' => $order->shipping_address_2,
      'city' => $order->shipping_city,
      'state' => $order->shipping_state,
      'postcode' => $order->shipping_postcode,
      'country' => $order->shipping_country
    ),
    'note' => $order->customer_note,
    'customer_ip' => $order->customer_ip_address,
    'customer_user_agent' => $order->customer_user_agent,
    'customer_id' => $order->get_user_id(),
    'view_order_url' => $order->get_view_order_url(),
    'line_items' => array(),
    'shipping_lines' => array(),
    'tax_lines' => array(),
    'fee_lines' => array(),
    'coupon_lines' => array()
  );

  // add line items

  foreach ($order->get_items() as $item_id => $item) {
    $product = $order->get_product_from_item($item);
    $product_id = null;
    $product_sku = null;

    // Check if the product exists.

    if (is_object($product)) {
      $product_id = (isset($product->variation_id)) ? $product->variation_id : $product->id;
      $product_sku = $product->get_sku();
    }

    $meta = new WC_Order_Item_Meta($item, $product);
    $item_meta = array();
    $hideprefix = (isset($filter['all_item_meta']) && $filter['all_item_meta'] === 'true') ? null : '_';
    foreach ($meta->get_formatted($hideprefix) as $meta_key => $formatted_meta) {
      $item_meta[] = array(
        'key' => $formatted_meta['key'],
        'label' => $formatted_meta['label'],
        'value' => $formatted_meta['value']
      );
    }

    $order_data['line_items'][] = array(
      'id' => $item_id,
      'subtotal' => wc_format_decimal($order->get_line_subtotal($item, false, false), $dp),
      'subtotal_tax' => wc_format_decimal($item['line_subtotal_tax'], $dp),
      'total' => wc_format_decimal($order->get_line_total($item, false, false), $dp),
      'total_tax' => wc_format_decimal($item['line_tax'], $dp),
      'price' => wc_format_decimal($order->get_item_total($item, false, false), $dp),
      'quantity' => wc_stock_amount($item['qty']),
      'tax_class' => (!empty($item['tax_class'])) ? $item['tax_class'] : null,
      'name' => $item['name'],
      'product_id' => $product_id,
      'sku' => $product_sku,
      'meta' => $item_meta
    );
  }

  // add shipping

  foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
    $order_data['shipping_lines'][] = array(
      'id' => $shipping_item_id,
      'method_id' => $shipping_item['method_id'],
      'method_title' => $shipping_item['name'],
      'total' => wc_format_decimal($shipping_item['cost'], $dp)
    );
  }

  // add taxes

  foreach ($order->get_tax_totals() as $tax_code => $tax) {
    $order_data['tax_lines'][] = array(
      'id' => $tax->id,
      'rate_id' => $tax->rate_id,
      'code' => $tax_code,
      'title' => $tax->label,
      'total' => wc_format_decimal($tax->amount, $dp),
      'compound' => (bool) $tax->is_compound
    );
  }

  // add fees

  foreach ($order->get_fees() as $fee_item_id => $fee_item) {
    $order_data['fee_lines'][] = array(
      'id' => $fee_item_id,
      'title' => $fee_item['name'],
      'tax_class' => (!empty($fee_item['tax_class'])) ? $fee_item['tax_class'] : null,
      'total' => wc_format_decimal($order->get_line_total($fee_item), $dp),
      'total_tax' => wc_format_decimal($order->get_line_tax($fee_item), $dp)
    );
  }

  // add coupons

  foreach ($order->get_items('coupon') as $coupon_item_id => $coupon_item) {
    $order_data['coupon_lines'][] = array(
      'id' => $coupon_item_id,
      'code' => $coupon_item['name'],
      'amount' => wc_format_decimal($coupon_item['discount_amount'], $dp)
    );
  }

  return $order_data;
}

function get_contact_info() {
  $contact_info = wp_get_current_user();
  if (!function_exists('get_user_by')){
    require_once(ABSPATH . 'wp-includes/pluggable.php');
  }

  if(!isset($contact_info->data->user_email)){
    $info = get_user_by('email', $_POST['email']);
    return array(
      'customer_id' => $info->ID,
      'email' => $info->user_email,
      'created_at' => $info->user_registered,
      'firstname' => $info->display_name,
      'created_in' => $info->user_registered
    );
  } else {
    return array(
      'email' => $contact_info->data->user_email,
      'created_at' => $contact_info->data->user_registered,
      'firstname' => $contact_info->data->display_name,
      'created_in' => $contact_info->data->user_registered
    );
  }
}

function get_customer_email() {
  if ($_POST['email']) {
    return $_POST['email'];
  } else {
    $info = get_contact_info();
    return $info['email'];
  }
}

function get_woo_version_number() {
  if (!function_exists('get_plugins'))
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');

  $plugin_folder = get_plugins('/' . 'woocommerce');
  $plugin_file = 'woocommerce.php';

  if (isset($plugin_folder[$plugin_file]['Version'])) {
    return $plugin_folder[$plugin_file]['Version'];
  } else {
    return NULL;
  }
}
?>
