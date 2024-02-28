<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Repository\CommentRepository;
use Metabolism\WordpressBundle\Repository\PostRepository;
use Metabolism\WordpressBundle\Repository\TermRepository;

/**
 * Class Post
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Post extends Entity
{
    public $entity = 'post';
    
    protected $comment_status;
    protected $comment_count;
    protected $menu_order;
    protected $password;
    
    protected $taxonomies;
    protected $blocks;
    protected $blocks_list;
    protected $slug;
    protected $status;
    protected $type;
    protected $title;
    protected $public;
    protected $thumbnail;
    protected $ancestor;
    protected $ancestors;
    protected $children;
    protected $siblings;
    protected $parent;
    protected $author;
    protected $template;
    protected $content;
    protected $class;
    protected $classes;
    protected $link;
    protected $sticky;
    protected $excerpt;
    protected $next;
    protected $prev;
    protected $current;
    protected $state;
    protected $path;
    protected $parameters;
    
    /** @var \WP_Post|bool */
    protected $post;
    
    public function __toString(): string
    {
        
        return $this->title??'Invalid post';
    }
    
    /**
     * Post constructor.
     *
     * @param null $id
     */
    public function __construct($id = null) {
        
        if( $post = $this->get($id) ) {
            
            $this->ID = $post->ID;
            $this->comment_status = $post->comment_status;
            $this->comment_count = $post->comment_count;
            $this->menu_order = $post->menu_order;
            $this->password = $post->post_password;
            $this->slug = $post->post_name;
            $this->status = $post->post_status;
            $this->type = $post->post_type;
            $this->title = $post->post_title;
            
            $this->loadMetafields($this->ID, 'post');
        }
    }
    
    
    /**
     * @param $pid
     * @return \WP_Post|false
     */
    protected function get($pid) {
        
        if( $post = get_post($pid) ) {
            
            if( is_wp_error($post) || !post_type_exists($post->post_type) )
                return false;
            
            $this->post = $post;
        }
        
        return $post;
    }
    
    /**
     * @return string
     */
    public function getCommentStatus(): string
    {
        return $this->comment_status;
    }
    
    /**
     * @return bool
     */
    public function isCurrent(): bool
    {
        if( is_null($this->current) )
            $this->current = $this->ID == get_queried_object_id();
        
        return $this->current;
    }
    
    /**
     * @return string
     */
    public function getState(): string
    {
        if( is_null($this->state) ){
            
            global $wpdb;
            
            $option = $wpdb->get_row( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'page_on_%' AND option_value = %s LIMIT 1", $this->ID ) );
            
            $this->state = str_replace('page_on_', '', $option->option_name??'');
        }
        
        return $this->state;
    }
    
    /**
     * @return int|string
     */
    public function getCommentCount()
    {
        return $this->comment_count;
    }
    
    /**
     * @return int
     */
    public function getMenuOrder(): int
    {
        return $this->menu_order;
    }
    
    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }
    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    
    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }
    
    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
    
    /**
     * @param bool|string $property
     * @return mixed
     */
    public function getType($property=false)
    {
        if( !$property )
            return $this->type;

        $type = get_post_type_object($this->type);
        return $type->$property??false;
    }
    
    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        if( is_null($this->public) )
            $this->public = is_post_type_viewable($this->type);
        
        return $this->public;
    }
    
    /**
     * Get post date
     *
     * @param string|bool $format
     * @return mixed|string|void
     */
    public function getDate($format=true){
        
        return $this->formatDate($this->post->post_date, $format);
    }
    
    /**
     * Get post blocks
     *
     * @return array[]
     */
    public function getBlocks(){
        
        if( is_null($this->blocks) ){
            
            $_blocks = parse_blocks($this->post->post_content);
            
            $blocks = [];
            $blocks_list = [];
            
            foreach ($_blocks as $_block){
                
                if( !empty($_block['blockName']) ){
                    
                    $blocks[] = new Block($_block);
                    $blocks_list[] = $_block['blockName'];
                }
            }
            
            $this->blocks = $blocks;
            $this->blocks_list = array_unique($blocks_list);
        }
        
        return $this->blocks;
    }
    
    /**
     * Get post modified date
     *
     * @param string|bool $format
     * @return mixed|string|void
     */
    public function getModified($format=true){
        
        return $this->formatDate($this->post->post_modified, $format);
    }
    
    /**
     * Get post date gmt
     *
     * @param string|bool $format
     * @return mixed|string|void
     */
    public function getDateGmt($format=true){
        
        return $this->formatDate($this->post->post_date_gmt, $format);
    }
    
    /**
     * Get post modified date gmt
     *
     * @param string|bool $format
     * @return string
     */
    public function getModifiedGmt($format=true){
        
        return $this->formatDate($this->post->post_modified_gmt, $format);
    }
    
    /**
     * Get excerpt
     *
     * @return string
     */
    public function getExcerpt(){
        
        if( is_null($this->excerpt) ){
            
            if( !empty($this->post->post_excerpt) ){
                
                $this->excerpt = nl2br($this->post->post_excerpt);
            }
            elseif( $this->hasBlocks() ){

                $this->excerpt = false;
            }
            else{
                
                $excerpt_length = (int) apply_filters( 'excerpt_length', 55 );
                
                $content = $this->getContent();
                $text = wp_trim_words( $content, $excerpt_length, '...' );
                
                $this->excerpt = apply_filters( 'wp_trim_excerpt', $text, $content );
            }
        }
        
        return $this->excerpt;
    }
    
    /**
     * Detect excerpt
     *
     * @return bool
     */
    public function hasExcerpt(){
        
        return !empty($this->post->post_excerpt);
    }
    
    /**
     * Get post author
     *
     * @return User
     */
    public function getAuthor(){
        
        if( is_null($this->author) )
            $this->author = Factory::create($this->post->post_author, 'user');
        
        return $this->author;
    }
    
    /**
     * Get post class
     *
     * @return string
     */
    public function getClass(){
        
        if( is_null($this->class) )
            $this->class = implode(' ', $this->getClasses());
        
        return $this->class;
    }
    
    /**
     * Get post classes
     *
     * @return string[]
     */
    public function getClasses(){
        
        if( is_null($this->classes) )
            $this->classes = get_post_class('', $this->post);
        
        return $this->classes;
    }
    
    /**
     * Get is sticky
     *
     * @return bool
     */
    public function isSticky(){
        
        if( is_null($this->sticky) )
            $this->sticky = is_sticky($this->post->ID);
        
        return $this->sticky;
    }
    
    /**
     * Get post link
     *
     * @return false|string
     */
    public function getLink(){
        
        if( is_null($this->link) && $this->isPublic() )
            $this->link = get_permalink( $this->post );
        
        return $this->link;
    }
    
    /**
     * @deprecated
     * @return false|string
     */
    public function getUrl(){
        
        return $this->getLink();
    }
    
    /**
     * Get post path
     *
     * @return false|string
     */
    public function getPath(){
        
        if( is_null($this->path) && $this->isPublic() ){
            
            $post_type_object = get_post_type_object($this->type);

                $path = str_replace(get_home_url(), '', $this->getLink());
            
            if( $rewrite_slug = $post_type_object->rewrite['slug']??false ){

                $rewrite_slug = preg_replace('/{([^%]+)}/m', '([^\/]+)', str_replace('/','\/', '/'.$rewrite_slug));
                $path = preg_replace('/^'.$rewrite_slug.'/m', '', $path);
            }

            if( substr($path, 0, 1) == '/')
                $this->path = substr($path, 1);
            else
                $this->path = false;
        }
        
        return $this->path;
    }
    
    /**
     * Get post url parameters
     *
     * @return array
     */
    public function getParameters(){

        if( is_null($this->parameters) && $this->isPublic() ){

            $parameters = [];
            $rewrite = $this->getType('rewrite');

            if( $rewrite_slug = $rewrite['slug']??false ){

                preg_match_all('/{([^%]+)}/m', $rewrite_slug, $matches, PREG_SET_ORDER);

                foreach ($matches as $match){

                    if( $term = $this->getTerm($match[1]) )
                        $parameters[$match[1]] = $term->getSlug();
                    else
                        $parameters[$match[1]] = 'default';
                }
            }

            if( $this->getType() == 'page' )
                $parameters['pagename'] = $this->getPath();
            else
                $parameters[$this->getType()] = $this->getPath();

            $this->parameters = $parameters;
        }

        return $this->parameters;
    }

    /**
     * @return bool
     */
    public function hasBlocks(){
        
        return has_blocks( $this->post->post_content );
    }
    
    /**
     * @return bool
     */
    public function hasBlock($name){
        
        if( is_null($this->blocks_list) )
            $this->getBlocks();
        
        return in_array($name, $this->blocks_list);
    }
    
    /**
     * Get filtered content
     *
     * @param bool $index
     * @return string
     */
    public function getContent($index=false){
        
        if( is_null($this->content) ){
            
            if( $this->hasBlocks() ){
                
                $this->content = [];
                
                $blocks = parse_blocks( $this->post->post_content );
                
                foreach ($blocks as $block){
                    
                    if( !empty($block['innerContent']??'') ){

                        $this->content = array_merge($this->content, $block['innerContent']);
                    }
                    else{

                        $data = $block['attrs']['data']??[];
                        $content = [];

                        foreach ($data as $key=>$value){

                            if( substr($key, 0,1 ) != '_' && is_string($value) && !is_numeric($value) && $value != 'none' )
                                $content[] = $value;
                        }

                        $this->content = array_merge($this->content, $content);
                    }
                }

                $this->content = array_unique(array_filter($this->content));
                $this->content = implode("\n", $this->content);
                $this->content = preg_replace("/[\r\n]+/", "\n", $this->content);
            }
            else{
                
                $post_content = get_the_content(null, false, $this->post);
                $post_content = apply_filters( 'the_content', $post_content );
                $post_content = str_replace( ']]>', ']]&gt;', $post_content );
                
                $this->content = $post_content;
            }
        }
        
        if( $index ){

            // normalize
            $content = html_entity_decode(strtolower(strip_tags($this->content)));
            // keep only words > 3 char
            $content = preg_replace("/\b\w{1,3}\b/u", '', $content);
            //remove punctuation
            $content = preg_replace("/[\(\)\*\.:\+%;\[\],'’#\-\/\?\!]/u", '', $content);
            //remove multiple space
            $content = preg_replace("/\s+/u", ' ', $content);

            //keep unique words
            $content = preg_split('/\s+/', $content);
            $content = array_unique($content);

            return implode(' ', $content);
        }

        return $this->content;
        
    }
    
    /**
     * Get template
     *
     * @return false|string
     */
    public function getTemplate(){
        
        if( is_null($this->template) )
            $this->template = get_page_template_slug( $this->post );
        
        return $this->template;
    }
    
    /**
     * Get thumbnail
     *
     * @param int $width
     * @param int $height
     * @param array $args
     * @return false|Image
     */
    public function getThumbnail($width=0, $height=0, $args=[]){
        
        if( is_null($this->thumbnail) ){
            
            $post_thumbnail_id = get_post_thumbnail_id( $this->post );
            
            if( $post_thumbnail_id )
                $this->thumbnail = Factory::create($post_thumbnail_id, 'image');
        }
        
        if( !func_num_args() || !$this->thumbnail ){
            
            return $this->thumbnail;
        }
        else{
            
            $args['resize'] = [$width, $height];
            return $this->thumbnail->edit($args);
        }
    }
    
    
    /**
     * Get sibling post using date order
     *
     * @param $direction
     * @param $in_same_term
     * @param $excluded_terms
     * @param $taxonomy
     * @return Post|false
     */
    protected function getSibling($direction, $in_same_term = false , $excluded_terms = '', $taxonomy = 'category'){
        
        global $post;
        
        $old_global = $post;
        $post = $this->post;
        
        if( $direction === 'prev')
            $sibling = get_previous_post($in_same_term , $excluded_terms, $taxonomy);
        else
            $sibling = get_next_post($in_same_term , $excluded_terms, $taxonomy);
        
        $post = $old_global;
        
        if( $sibling instanceof \WP_Post)
            return PostFactory::create($sibling->ID);
        else
            return false;
    }
    
    
    /**
     * Get next post
     *
     * See: https://developer.wordpress.org/reference/functions/get_next_post/
     *
     * @param bool $in_same_term
     * @param string $excluded_terms
     * @param string $taxonomy
     * @return Post|false
     */
    public function getNext($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {
        
        if( is_null($this->next) )
            $this->next = $this->getSibling('next', $in_same_term , $excluded_terms, $taxonomy);
        
        return $this->next;
    }
    
    
    /**
     * Get child posts
     *
     * @return PostCollection|false
     */
    public function getChildren($orderBy='menu_order') {
        
        if( is_null($this->children) || $orderBy != 'menu_order' ){
            
            $postRepository = new PostRepository();

            if( is_string($orderBy) )
                $orderBy = [$orderBy=>'ASC'];

            $this->children = $postRepository->findBy(['post_parent'=>$this->ID, 'post_type'=>$this->type], $orderBy, -1);
        }
        
        return $this->children;
    }
    
    
    /**
     * Get siblings
     *
     * @return PostCollection|null
     */
    public function getSiblings($orderBy='menu_order') {
        
        if( is_null($this->siblings) || $orderBy != 'menu_order' ){
            
            $postRepository = new PostRepository();
            $this->siblings = $postRepository->findBy(['post_parent'=>$this->post->post_parent, 'post_type'=>$this->type, 'post__not_in'=>[$this->ID]], $orderBy, -1);
        }
        
        return $this->siblings;
    }
    
    
    /**
     * Has parent post
     *
     * @return bool
     */
    public function hasParent() {
        
        return $this->post->post_parent != 0;
    }
    
    
    /**
     * Post has children
     *
     * @return bool
     */
    public function hasChildren() {

        $children = get_posts(['post_parent' => $this->ID, 'posts_per_page' => 1, 'fields'=>'ids', 'post_type'=>$this->type]);

        return count($children) > 0;
    }
    
    
    /**
     * Get parent post
     *
     * @return Post|false
     */
    public function getParent() {
        
        if( is_null($this->parent) )
            $this->parent = PostFactory::create($this->post->post_parent);
        
        return $this->parent;
    }
    
    /**
     * Get post ancestors
     *
     * @param $reverse
     * @return array|Post[]
     */
    public function getAncestors($reverse=true){
        
        if( is_null($this->ancestors) ){
            
            $parents_id = get_post_ancestors($this->ID);
            
            if( $reverse )
                $parents_id = array_reverse($parents_id);
            
            $ancestors = [];
            
            foreach ($parents_id as $post_id)
                $ancestors[] = PostFactory::create($post_id);
            
            $this->ancestors = $ancestors;
        }
        
        return $this->ancestors;
    }
    
    /**
     * Get post root ancestor
     *
     * @return false|Post
     */
    public function getAncestor(){
        
        if( is_null($this->ancestor) ){
            
            $parents_id = get_post_ancestors($this->ID);
            
            $parents_id = array_reverse($parents_id);
            
            if( count($parents_id) )
                $this->ancestor = PostFactory::create($parents_id[0]);
            else
                $this->ancestor = false;
        }
        
        return $this->ancestor;
    }
    
    
    /**
     * Get post comments
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @return CommentCollection
     */
    public function getComments($criteria=[], array $orderBy = null, $limit = null, $offset = null) {
        
        $default_args = [
            'status'=> 'approve',
        ];
        
        $args = array_merge($default_args, $criteria);
        
        $args['post_id'] = $this->ID;
        $args['type'] = 'comment';
        $args['parent'] = 0;
        
        $commentRepository = new CommentRepository();
        
        return $commentRepository->findBy($args, $orderBy, $limit, $offset);
    }
    
    
    /**
     * Get previous post
     * See: https://developer.wordpress.org/reference/functions/get_previous_post/
     *
     * @param bool $in_same_term
     * @param string $excluded_terms
     * @param string $taxonomy
     * @return Post|false
     */
    public function getPrev($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {
        
        if( is_null($this->prev) )
            $this->prev = $this->getSibling('prev', $in_same_term , $excluded_terms, $taxonomy);
        
        return $this->prev;
    }
    
    /**
     * @param null $public
     * @return \WP_Taxonomy[]
     */
    public function getTaxonomies($public=null){

        if( is_null($this->taxonomies) || !is_null($public) ){

            $taxonomies = get_object_taxonomies( $this->type, 'objects' );

            if( $public === true )
                $taxonomies = array_filter($taxonomies, function ($taxonomy){ return is_taxonomy_viewable($taxonomy); });
            elseif( $public === false )
                $taxonomies = array_filter($taxonomies, function ($taxonomy){ return !is_taxonomy_viewable($taxonomy); });

            $taxonomies = array_map(function ($taxonomy){ return $taxonomy->name; }, $taxonomies);

            if( !is_null($public) )
                return $taxonomies;

            $this->taxonomies = $taxonomies;
        }
        
        return $this->taxonomies;
    }
    
    /**
     * Get primary term
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
     *
     * @param string $tax
     * @param array $args
     * @return Term|bool
     */
    public function getTerm( $tax='', $args=[] ) {
        
        $args['number'] = 1;
        $args['hierarchical'] = false;
        
        $terms = $this->getTerms($tax, $args);
        
        if( count($terms) )
            return $terms[0];
        else
            return false;
    }
    
    /**
     * Get term list
     * See : https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
     *
     * @param string $tax
     * @param array $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @return TermCollection
     */
    public function getTerms( $tax='', $criteria=[], array $orderBy = null, $limit = null, $offset = null ) {
        
        if ( is_array($tax) )
            $criteria = $tax;
        elseif( !empty($tax) )
            $criteria['taxonomy'] = $tax;
        
        if ( !isset($criteria['taxonomy']) )
            $criteria['taxonomy'] = $this->getTaxonomies($criteria['public']??null);

        $criteria['object_ids'] = $this->ID;
                
        $termRepository = new TermRepository();
                    
        return $termRepository->findBy($criteria, $orderBy, $limit, $offset);
    }
    
    /**
     * @param string $tax
     * @param bool $args
     * @return TermCollection
     * @deprecated
     */
    public function get_terms( $tax='', $args=false ) { return $this->getTerms($tax, $args); }
    
    /**
     * @param string $tax
     * @param bool $args
     * @return bool|Term
     * @deprecated
     */
    public function get_term( $tax='', $args=false ) { return $this->getTerm($tax, $args); }
}
