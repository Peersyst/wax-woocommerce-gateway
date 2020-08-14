<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters( 'wc_wax_settings',
	array(
		'enabled' => array(
			'title'       => __( 'Enable/Disable', 'woocommerce-gateway-wax' ),
			'label'       => __( 'Enable WAX payments', 'woocommerce-gateway-wax' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => __( 'Title', 'woocommerce-gateway-wax' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-wax' ),
			'default'     => __( 'WAX (Digital currency)', 'woocommerce-gateway-wax' ),
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => __( 'Description', 'woocommerce-gateway-wax' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout. Leave it empty and it will not show.', 'woocommerce-gateway-wax' ),
			'default'     => __( 'Pay with WAX.', 'woocommerce-gateway-wax'),
			'desc_tip'    => true,
		),
		'server_url' => array(
			'title'       => __( 'Server URL', 'woocommerce-gateway-wax' ),
			'type'        => 'text',
			'description' => __( 'A server API entrypoint supporting v2. For example: https://api.waxsweden.org/v2', 'woocommerce-gateway-wax' ),
			'default'     => __( 'https://api.waxsweden.org/v2', 'woocommerce-gateway-wax'),
			'desc_tip'    => true,
		),
		'wax_address' => array(
			'title'       => __( 'WAX address', 'woocommerce-gateway-wax' ),
			'type'        => 'text',
			'description' => __( 'Input the WAX address where you want customers to pay WAX to.', 'woocommerce-gateway-wax' ),
			'default'     => '',
			'placeholder' => 'cg2qw.wam',
			'desc_tip'    => true,
		),
		'match_amount' => array(
			'title'       => __( 'Match amount', 'woocommerce-gateway-wax' ),
			'label'       => __( 'Match transactions on Amount', 'woocommerce-gateway-wax' ),
			'type'        => 'checkbox',
			'description' => __( 'When customers paid in checkout and the system tried to match a transfer to your account, it will also try to match it exactly on amount if the memo does not match.', 'woocommerce-gateway-xem' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
        'prices_in_wax' => array(
            'title'       => __( 'Show prices in WAX', 'woocommerce-gateway-wax' ),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __( 'Show prices on store pages in WAX', 'woocommerce-gateway-wax' ),
            'default'     => 'no',
            'desc_tip'    => true,
            'options'     => array(
                    'no'    => __( 'Default prices', 'woocommerce-gateway-wax' ),
                    'only'    => __( 'Only WAX price', 'woocommerce-gateway-wax' ),
                    'both'    => __( 'Default and WAX prices', 'woocommerce-gateway-wax' ),
            ),
        ),
		'logging' => array(
			'title'       => __( 'Logging', 'woocommerce-gateway-wax' ),
			'label'       => __( 'Log debug messages', 'woocommerce-gateway-wax' ),
			'type'        => 'checkbox',
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'woocommerce-gateway-wax' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
	)
);
