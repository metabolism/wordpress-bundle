<?php

namespace Metabolism\WordpressBundle\Helper;

if(!class_exists('WP_List_Table'))
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class TableHelper extends \WP_List_Table {

	private $table, $args, $fields;

	function __construct($table, $args)
	{
		global $wpdb;

		$this->table = $table;
		$this->args  = $args;

		$structure = $wpdb->get_col( "DESCRIBE {$wpdb->prefix}{$this->table}", 0 );
		if(!$structure || !is_array($structure) || !in_array('id', $structure))
			wp_die("Field `id` is missing from table {$wpdb->prefix}{$this->table}");


		parent::__construct( array(
			'singular'  => $args['singular'],
			'plural'    => $args['plural'],
			'ajax'      => false
		) );

	}


	function column_default($item, $column_name)
	{
		$value = '';

		if( in_array($column_name, $this->fields) )
			$value = $item[$column_name];

		return apply_filters('list_table_column', $column_name, $value, $this->table);
	}


	function column_title($item){

		$actions = array(
			'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">'.__('Delete').'</a>', $_REQUEST['page'], 'delete', $item['id']),
		);

		$value = [];

		foreach ((array)$this->args['column_title'] as $column_title )
			$value[] = $item[$column_title];

		$value = apply_filters('list_table_column', 'title', implode(' ', $value), $this->table, $item);

		return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			$value,
			$item['id'],
			$this->row_actions($actions)
		);
	}


	function column_cb($item)
	{
		return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />','id', $item['id']);
	}


	function get_columns()
	{
		return array_merge(['cb' => '<input type="checkbox" />', 'title'=>__('Title')], $this->args['columns']);
	}


	function get_sortable_columns()
	{
		$sortable_columns = [];

		foreach ($this->args['columns'] as $column=>$name)
		{
			$sortable_columns[$column] = [$column, false];
		}

		return $sortable_columns;
	}


	function get_bulk_actions()
	{
		$actions = ['delete' => 'Delete'];

		return $actions;
	}



	function process_bulk_action()
	{
		global $wpdb;

		if( 'delete' === $this->current_action() && isset($_REQUEST['id']) )
		{
			$ids = implode( ',', array_map( 'absint', (array)$_REQUEST['id'] ) );

			if( !$wpdb->query("DELETE FROM {$wpdb->prefix}{$this->table} WHERE ID IN({$ids})") )
				wp_die('Unable to delete '.$ids.' from table '.$this->table);

			$redirect = admin_url('admin.php?page='.$_REQUEST['page']);

			echo '<script type="text/javascript">'.
				    'window.location = "' . $redirect . '"'.
			     '</script>';

			exit();
		}
	}


	function display()
	{
		echo '<div class="wrap">'.
			     '<h2>'.$this->args['page_title'].'</h2>'.
			     '<form id="'.$this->table.'-filter" method="get">'.
			         '<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
		             parent::display();
		echo     '</form>'.
			 '</div>';
	}


	function prepare_items()
	{
		global $wpdb;

		$per_page     = $this->args['per_page'];
		$order        = isset($_REQUEST['orderby'], $_REQUEST['order'])? 'ORDER BY '.$_REQUEST['orderby'].' '.$_REQUEST['order']:'';
		$current_page = $this->get_pagenum();

		$column_title = $this->args['column_title'];

		if( is_array($column_title) )
			$column_title[] = 'id';
		else
			$column_title = [$column_title, 'id'];

		$this->fields = array_unique(array_merge($column_title, array_keys($this->args['columns'])));

		$this->_column_headers = array($this->get_columns(), [], $this->get_sortable_columns());

		$this->process_bulk_action();

		$total_items = $wpdb->get_var( "SELECT count(`id`) FROM {$wpdb->prefix}{$this->table}");

		$query       = "SELECT `".implode("`,`", $this->fields)."` FROM {$wpdb->prefix}{$this->table} {$order} LIMIT ".(($current_page-1)*$per_page).", ".($current_page*$per_page);
		$this->items = $wpdb->get_results( $query, ARRAY_A );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		) );
	}
}
