<?php

namespace Metabolism\WordpressBundle\Service;

class PaginationService
{
    /**
     * Retrieve paginated link for archive post pages.
     * @param array $args
     * @param \WP_Query $query
     * @return array|false
     */
    public function build($args=[], $query=null)
    {
        global $wp_query, $wp_rewrite;

		if( is_null($query) )
			$query = $wp_query;

	    $total = $query->max_num_pages ?? 1;

		if( $total <= 1 )
			return false;

        $pagenum_link = html_entity_decode( get_pagenum_link() );
        $url_parts    = explode( '?', $pagenum_link );

        $current = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;

        $pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

        $format  = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
        $format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

        $defaults = array(
            'base'               => $pagenum_link,
            'format'             => $format,
            'total'              => $total,
            'current'            => $current,
            'show_all'           => false,
            'prev_text'          => __( 'Previous' ),
            'next_text'          => __( 'Next' ),
            'end_size'           => 1,
            'mid_size'           => 2,
            'add_args'           => array(),
            'add_fragment'       => '',
            'before_page_number' => '',
            'after_page_number'  => '',
        );

        $args = wp_parse_args( $args, $defaults );

        if ( ! is_array( $args['add_args'] ) )
            $args['add_args'] = array();

        if ( isset( $url_parts[1] ) ) {

            $format = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
            $format_query = $format[1] ?? '';
            wp_parse_str( $format_query, $format_args );

            wp_parse_str( $url_parts[1], $url_query_args );

            foreach ( $format_args as $format_arg => $format_arg_value )
                unset( $url_query_args[ $format_arg ] );

            $args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
        }

        $total = (int) $args['total'];

	    $pagination = [
			'pages'=>[],
			'prev'=>false,
			'next'=>false
	    ];

        if ( $total < 2 )
            return $pagination;

        $current  = (int) $args['current'];
        $end_size = (int) $args['end_size'];
        if ( $end_size < 1 )
            $end_size = 1;

        $mid_size = (int) $args['mid_size'];
        if ( $mid_size < 0 )
            $mid_size = 2;

        $add_args = $args['add_args'];
        $dots = false;

        if ( $current && 1 < $current ):
            $link = str_replace('%_%', 2 == $current ? '' : $args['format'], $args['base']);
            $link = str_replace('%#%', $current - 1, $link);
            if ($add_args)
                $link = add_query_arg($add_args, $link);
            $link .= $args['add_fragment'];

            $pagination['prev'] = ['link' => esc_url(apply_filters('paginate_links', $link)), 'text' => $args['prev_text']];
        endif;

        for ( $n = 1; $n <= $total; $n++ ) :
            if ( $n == $current ) :
                $pagination['pages'][] = ['current'=>true, 'text'=> $args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number']];
                $dots = true;
            else :
                if ( $args['show_all'] || ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) ) :
                    $link = str_replace( '%_%', 1 == $n ? '' : $args['format'], $args['base'] );
                    $link = str_replace( '%#%', $n, $link );
                    if ( $add_args )
                        $link = add_query_arg( $add_args, $link );
                    $link .= $args['add_fragment'];

                    $pagination['pages'][] = ['current'=>false, 'link'=> esc_url( apply_filters( 'paginate_links', $link ) ), 'text'=> $args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number']];
                    $dots = true;
                elseif ( $dots && ! $args['show_all'] ) :
                    $pagination['pages'][] = ['current'=>false, 'link'=>false, 'text'=> __( '&hellip;' ) ];
                    $dots = false;
                endif;
            endif;
        endfor;

        if ( $current && $current < $total ) :
            $link = str_replace( '%_%', $args['format'], $args['base'] );
            $link = str_replace( '%#%', $current + 1, $link );
            if ( $add_args )
                $link = add_query_arg( $add_args, $link );
            $link .= $args['add_fragment'];

            $pagination['next'] = ['link'=> esc_url( apply_filters( 'paginate_links', $link ) ), 'text'=> $args['next_text'] ];
        endif;

        return $pagination;
    }
}
