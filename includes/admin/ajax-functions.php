<?php
/**
 * AJAX Functions
 *
 * Process ajax admin functions.
 *
 * @package     ERM
 * @copyright   Copyright (c) 2022, Alejandro Pascual
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Update menu item - CSRF + post_type check
 */
function erm_update_menu_item() {

	//echo '<pre>'; print_r( $_POST ); echo '</pre>'; exit();

	if (!wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'erm_menu_actions' ))
	{
		wp_send_json_error();
	}

	if (isset($_POST['post_id'])) {

		$post_id = absint( $_POST['post_id'] );

		if (get_post_type($post_id) != 'erm_menu_item')
		{
			wp_send_json_error();
		}

		$title = sanitize_text_field($_POST['title']);
		$visible = sanitize_text_field($_POST['visible']);
		$prices = qrm_sanitize_text_or_array_field($_POST['prices']);

		wp_update_post(array(
			'ID'            => $post_id,
			'post_title'    => $title,
			'post_name'     => $title,
			'post_content'  => wp_kses_post($_POST['content']),
		));

		update_post_meta( $post_id, '_erm_visible', ($visible == 'true' || $visible == 1) ? true : false );
		update_post_meta( $post_id, '_erm_prices', $prices);

		$image_id = absint( $_POST['image_id'] );
		if ( $image_id != 0 ) {
			set_post_thumbnail( $post_id, $image_id );
		} else {
			delete_post_thumbnail( $post_id );
		}

		wp_send_json_success();
	}
	exit();
}
add_action( 'wp_ajax_erm_update_menu_item', 'erm_update_menu_item' );

/**
 * Delete menu item - CSRF + post_type check
 */
function erm_delete_menu_item() {

	if (!wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'erm_menu_actions' ))
	{
		wp_send_json_error();
	}

	if (isset($_POST['post_id'])) {

		if (get_post_type(absint($_POST['post_id'])) != 'erm_menu_item')
		{
			wp_send_json_error();
		}

		wp_delete_post( absint($_POST['post_id']), true);
		wp_send_json_success();
	}
	exit();
}
add_action( 'wp_ajax_erm_delete_menu_item', 'erm_delete_menu_item' );

/**
 * Create new menu item - CSRF
 */
function erm_create_menu_item() {

	if (!wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'erm_menu_actions' ))
	{
		wp_send_json_error();
	}

	$post_id = wp_insert_post(array(
		'post_type'      => 'erm_menu_item',
		'post_content'   => '',
		'post_name'      => 'New',
		'post_title'     => 'New',
		'post_status'    => 'publish'
	), true );

	if ( is_wp_error($post_id) ) {
		wp_send_json_error();

	} else {
		$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'product';
		update_post_meta( $post_id, '_erm_visible', true );
		update_post_meta( $post_id, '_erm_type', $type );
		wp_send_json_success(array(
			'id'        => $post_id,
			'type'      => $type,
			'title'     => 'New',
			'content'   => '',
			'image_id'  => 0,
			'src_thumb' => '',
			'src_big'   => '',
			'visible'   => 1,
			'prices' => array(),
			'link'  => get_edit_post_link( $post_id )
		));
	}

	exit();
}
add_action( 'wp_ajax_erm_create_menu_item', 'erm_create_menu_item' );

/**
 * Update list menu item - CSRF + post_type check
 *
 * @since 1.0
 */
function erm_update_list_menu_items() {

	if (!wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), 'erm_menu_actions' ))
	{
		wp_send_json_error();
	}

	if ( isset($_POST['ids']) ) {
		$post_id = absint( $_POST['post_id'] );

		if (get_post_type($post_id) != 'erm_menu')
		{
			wp_send_json_error();
		}

		update_post_meta( $post_id, '_erm_menu_items', sanitize_text_field($_POST['ids']));
		wp_send_json_success();
	}

	exit();
}
add_action( 'wp_ajax_erm_update_list_menu_items', 'erm_update_list_menu_items' );

/**
 * Get list of menu items ajax
 *
 * @since 1.0
 */
function erm_list_menu_items() {

	$posts = get_posts( array(
		'post_type' => 'erm_menu_item',
		'numberposts' => -1,
		'order_by' => 'post_title',
		'order' => 'ASC'
	) );

	$html = '';
	$items = array();
	if ($posts) {
		$html .= '<div style="display: inline-block; text-align: left; margin-bottom:20px;">';
		foreach( $posts as $post ) {
			if ( get_post_meta($post->ID,'_erm_type',true) == 'product'){
				$html .= '<label><input data-id="'.$post->ID.'" type="checkbox">'.$post->post_title.'</label><br>';
				$items[] = erm_get_menu_item_data( $post->ID );
			}
		}
		$html .= '</div><hr>';
		$html .= '<button id="add-menu-items" class="button button-default">'.__('Add Menu Items','erm').'</button>';
	} else {
		$html .= '<h1>NO MENU ITEMS</h1>';
	}
	wp_send_json_success( array('html'=>$html, 'items'=>$items) );
	exit();
}
add_action( 'wp_ajax_erm_list_menu_items', 'erm_list_menu_items' );


/**
 * Save menu week
 *
 * @since 1.1
 */
/*
function erm_update_menu_week() {

	$post_id = absint($_POST['post_id']);
	$franjas = qrm_sanitize_text_or_array_field($_POST['franjas']);
	//echo '<pre>'; print_r( $franjas ); echo '</pre>';
	update_post_meta( $post_id, 'erm_week_rules', $franjas );


	wp_send_json_success();
}
add_action( 'wp_ajax_erm_update_menu_week', 'erm_update_menu_week' );*/
