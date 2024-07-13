<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class class_LinkSh_Main_Table extends WP_List_Table {
	function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'title'     => 'Название',
			'shortlink' => 'Короткий адрес',
			'date'      => 'Дата',
			'clicks'    => 'Переходы'
		);
		return $columns;
	}

	function prepare_items() {
		$per_page = 20;
		$current_page = $this->get_pagenum();
		$total_items = $this->get_total_items();

		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		));

		$this->items = $this->get_posts($per_page, $current_page);
	}

	function get_total_items() {
		$args = array(
			'post_type'      => 'links_shrt',
			'post_status'    => 'publish',
			'posts_per_page' => -1
		);
		$query = new WP_Query($args);
		return $query->found_posts;
	}

	function get_posts($per_page, $page_number) {
		$args = array(
			'posts_per_page' => $per_page,
			'offset'         => ($page_number - 1) * $per_page,
			'post_type'      => 'links_shrt',
			'post_status'    => 'publish'
		);
		return get_posts($args);
	}

	function column_default($item, $column_name) {
		switch ($column_name) {
			case 'title':
				return $item->post_title;
			case 'shortlink':
				return wp_get_shortlink($item->ID);
			case 'date':
				return $item->post_date;
			case 'clicks':
				return get_post_meta($item->ID, 'clicks', true); // Предполагая, что данные о переходах хранятся в метаполе 'clicks'
			default:
				return print_r($item, true);
		}
	}

	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="post[]" value="%s" />',
			$item->ID
		);
	}
}

function linksh_render_main_table(): void {
	$listTable = new class_LinkSh_Main_Table();
	$listTable->prepare_items();
	$listTable->display();
}
