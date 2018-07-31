<?php

namespace Metabolism\WordpressBundle\Helper;

if(!class_exists('WP_List_Table'))
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Table extends \WP_List_Table {

	private $table, $args, $fields, $column_title;

	function __construct($table, $args)
	{
		global $wpdb;

		$this->table = $table;
		$this->column_title = isset($args['columns']['title'])?$args['columns']['title']:'Title';

		$structure = $wpdb->get_col( "DESCRIBE {$wpdb->prefix}{$this->table}", 0 );

		if(!$structure || !is_array($structure) )
			wp_die("Table {$wpdb->prefix}{$this->table} is missing");

		if( !in_array('id', $structure))
			wp_die("Field `id` is missing from table {$wpdb->prefix}{$this->table}");

		if( isset($args['columns']['title']) )
			unset($args['columns']['title']);

		$this->args  = $args;

		parent::__construct( array(
			'singular'  => $args['singular'],
			'plural'    => $args['plural'],
			'ajax'      => false
		) );

		$this->doActions();
	}


	function doActions() {

		if( $this->args['export'] && isset($_REQUEST['action'], $_REQUEST['page']) && $_REQUEST['action'] == "export"  && $_REQUEST['page'] == "table_".$this->table ){

			if( isset($_REQUEST['id']) )
				$ids = implode( ',', array_map( 'absint', (array)$_REQUEST['id'] ) );
			else
				$ids = false;

			global $wpdb;

			$query = apply_filters('list_table_export_query', "SELECT * FROM {$wpdb->prefix}{$this->table}".($ids?" WHERE `id` IN({$ids})":""), $this->table, $ids);
			$items = apply_filters('list_table_export_results', $wpdb->get_results( $query, ARRAY_A ), $this->table);
			$filename = apply_filters('list_table_export_filename', 'export-'.$this->table.'.'.date('YmdHis').'.csv', $this->table);

			header("Content-type: application/force-download");
			header('Content-Disposition: inline; filename="'.$filename.'"');

			if( count($items) )
			{
				$out = fopen('php://output', 'w');

				fputcsv($out, array_keys($items[0]));

				foreach ($items as $item)
					fputcsv($out, array_values($item));

				fclose($out);
			}

			exit();
		}
	}


	function extra_tablenav( $which ) {

		if( $this->args['export'] )
			echo '<a class="button button-primary" href="'.sprintf('?page=%s&action=export', $_REQUEST['page']).'" style="display: inline-block;float: right;margin-left: 10px;margin-right: 0;margin-bottom: 10px;">'.__('Export all').'</a>';
	}


	function column_default($item, $column_name)
	{
		$value = '';

		if( in_array($column_name, $this->fields) )
			$value = $item[$column_name];

		return apply_filters('list_table_column', $value, $column_name, $this->table);
	}


	function column_title($item){

		$actions = [];

		$actions['delete'] = sprintf('<a href="?page=%s&action=%s&id=%s" target="_blank">'.__('Delete').'</a>', $_REQUEST['page'], 'delete', $item['id']);

		if( $this->args['export'] )
			$actions['export'] = sprintf('<a href="?page=%s&action=%s&id=%s" target="_blank">'.__('Export').'</a>', $_REQUEST['page'], 'export', $item['id']);

		$value = [];

		foreach ((array)$this->args['column_title'] as $column_title )
			$value[] = $item[$column_title];

		$value = apply_filters('list_table_column', implode(' ', $value), 'title', $this->table, $item);

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
		return array_merge(['cb' => '<input type="checkbox" />', 'title'=>__($this->column_title)], $this->args['columns']);
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
		$actions = ['delete' => __('Delete')];

		if( $this->args['export'] )
			$actions['export'] = __('Export');

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
