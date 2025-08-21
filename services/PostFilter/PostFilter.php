<?php

namespace Netdust\Services\PostFilter;


use Netdust\Core\ServiceProvider;
use Netdust\Http\Request;
use Netdust\Http\URL;
use Netdust\Logger\Logger;
use Netdust\Service\Assets\AssetManager;
use Netdust\Service\Assets\Script;


class PostFilter
{
    //use Setters;

    protected ServiceProvider $provider;

    public string $post_type;

    public array $taxonomies;
    public array $filters;
    public array $metas;

    public array $query_args = [];
    public string $publish = '';
    public array $order_by = [];
    public string $id = '';
    public string $exclude_cat = '';
    private Script $script;

    public function set( array $args ): void {
        // Override default params.
        foreach ( $args as $name  => $value ) {
            if( property_exists($this, $name) ) {
                $this->{$name} = $value;
            }
        }
    }

    public function get( string $name, mixed $default='' ): mixed {
        if( $name=='post_type' && !empty( $_REQUEST['post_type'] ) ) {
            return $_REQUEST['post_type'];
        }

        // acf
        /*
        if(  function_exists( 'get_field') && !is_wp_error($value = get_field( $name ))  &&  !empty( $value ) )
            return $value;
        */

        if( !is_wp_error($value = $this->{$name}) && !empty( $value ) )
            return $value;

        return $default;

    }

    public function __construct( ServiceProvider $provider, array $params = [] ) {
        $this->provider = $provider;
        $this->set( $params );
    }


    public function add_noindexmeta_tags(): void {

        if ( (is_home()||is_archive()) && count( $this->get_query() ) > 0 && !array_key_exists('post-type', $this->get_query()) ) {
            echo '<meta name="robots" content="noindex, follow" />' . "\n";
        }

    }

    public function add_nocache_headers(): void {

        if ( (is_home()||is_archive()) && count( $this->get_query() ) > 0 && !array_key_exists('post-type', $this->get_query()) ) {
            nocache_headers();
        }

    }

    public function add_ajax(): void {
        add_action('wp_ajax_get_nonce',  [$this, 'get_filter_nonce']);
        add_action('wp_ajax_nopriv_get_nonce',  [$this,'get_filter_nonce']); // Allow non-logged-in users too

        add_action('wp_ajax_'.$this->get('id'), [$this, 'filter_products_ajax']);
        add_action('wp_ajax_nopriv_'.$this->get('id'), [$this, 'filter_products_ajax']);


    }

    public function get_filter_nonce() {
        $nonce = wp_create_nonce('filter-security');
        wp_send_json_success(['nonce' => $nonce]);
    }

    public function register(): void {

        // we need to make this conditional
        add_filter('term_link', function($link, $term, $taxonomy) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $taxonomy)));
            return URL::basePath() . '/?'.$slug.'='.$term->slug;
        }, 10, 3);

        add_action( 'init', [$this,'add_nocache_headers']);
        add_action( 'wp_head', [$this,'add_noindexmeta_tags']);

        $this->register_script();
        $this->add_ajax();


    }

    protected function do_query( array $filter, array $taxonomies, array $metas ): string {

        $filter = apply_filters('postfilter:filter', $filter );

        $query = new \WP_Query( $filter );

        do_action('postfilter:query', $query );

        $pagination = $this->custom_pagination(
            $query->max_num_pages,
            $this->get( 'post_type' )
        );

        if( is_admin() && defined('DOING_AJAX') && DOING_AJAX ) {

            wp_reset_postdata();
            wp_reset_query();

            $result_html = $this->render( 'filter_result', [
                'results'=> $query,
            ]);

            $ajax =  [
                'total' => $query->found_posts,
                'page' =>  $pagination,
                'html' => $query->found_posts==0 ? '<div uk-alert>Jammer, we hebben geen resultaten gevonden.</div>' : $result_html,
            ];

            die( json_encode( $ajax ) );

        }
        else {

            return $this->render(  'filter', [
                'taxonomies'=>$taxonomies,
                'metas'=>$metas,
                'results'=>$query,
                'page'=>$pagination,
                's'=>$_REQUEST['s']??'',
            ] );

        }
    }

    protected function render(string $template_name, array $data = []):string {
        return $this->provider->render( 'filter/' . $template_name, $data );
    }

    public function custom_pagination( $total_pages, $posttype ) {

        if ($total_pages > 1) {
            $current_page = max(1, get_query_var('paged'));

            $filters = array_map( function($filter){
                $keys = array_keys( $filter );
                return end( $keys );
            }, $this->get_filters( ) );

            if( !empty( $_REQUEST['s'] ) ){
                $filters["s"] = str_replace(' ', '+', sanitize_text_field($_REQUEST['s']) );
            }

            $pagination_args = array(
                'base' =>  get_post_type_archive_link( $posttype ).'%_%',
                'add_args' =>  array_merge(  $filters ),
                'format' => 'page/%#%/',
                'current' => $current_page,
                'total' => $total_pages,
                'prev_text' => __('« Prev'),
                'next_text' => __('Next »')
            );

            return paginate_links($pagination_args);
        }

        return null;
    }

    public function update_filter( $filter, $atts=[] ) {

        $taxonomies = $this->get_taxonomies();

        // add text search
        if( ! empty( $_REQUEST['s'] ) ){
            $filter['s'] = str_replace(' ', '+', sanitize_text_field($_REQUEST['s']) );
        }

        if ( ! isset( $filter['tax_query'] ) ) {
            $filter['tax_query'] = [];
        }

        // add taxonomy filters
        if( ! empty( $tax_filters = $this->get_filters( ) ) ) {

            foreach ( $tax_filters as $category => $terms ) {
                $filter['tax_query'][] = [
                    'taxonomy' => $taxonomies[$category]['tax'],
                    'field' => 'slug',
                    'terms' => array_keys($terms),
                    'include_children' => true,
                    'operator' => 'IN',
                ];
            }
        }


        $filter['tax_query'] = apply_filters( 'postfilter:tax_query', $filter['tax_query'], $this->get('post_type', 'post' ) );

        if (isset( $filter['tax_query'] ) && count( $filter['tax_query'] ) > 1) {

            $tax_query           = $filter['tax_query'];
            $filter['tax_query'] = array( 'relation' => 'AND' );
            $filter['tax_query'] = array_merge( $filter['tax_query'], $tax_query );

        }

        if( empty( $filter['tax_query'] ) ) {
            unset(  $filter['tax_query'] );
        }


        if ( ! isset( $filter['meta_query'] ) ) {
            $filter['meta_query'] = [];
        }

        //add filter out no wanted metas
        $exclude_meta = apply_filters( 'postfilter:meta_exclude', ['s'] );
        $filter_request = array_diff_key($this->get_metas( ), array_combine($exclude_meta, array_fill(0, count($exclude_meta), [])) );

        //add meta filters
        if( ! empty( $filter_request ) ) {
            foreach (  $filter_request as $key=>$value ) {
                if( is_string($value) ) {
                    $list = preg_split( '/ +/', trim( $value ) );
                    $regex = implode( '.*', array_map( 'preg_quote', $list ) );
                    $filter['meta_query'][] = [
                        'key'       => $key,
                        'value'     => $regex,
                        'compare'   => 'REGEXP'
                    ];
                }
            }
        }


        $filter['meta_query'] = apply_filters( 'postfilter:meta_query', $filter['meta_query'], $this->get('post_type', 'post' ) );

        if (isset( $filter['meta_query'] ) && count( $filter['meta_query'] ) > 1 ) {

            $meta_query          = $filter['meta_query'];
            $filter['meta_query'] = array( 'relation' => 'OR' );
            $filter['meta_query'] = array_merge( $filter['meta_query'], $meta_query );

        }

        if( empty( $filter['meta_query'] ) ) {
            unset(  $filter['meta_query'] );
        }



        return $filter;

    }

    public function echo_template(): void {

        nocache_headers();

        if( !empty($this->query_args) && is_array($this->query_args)) {
            $this->set( $this->query_args );
        }

        $this->enqueue_script( );

        $this->taxonomies = $this->get_taxonomies( );
        $this->metas = $this->get_metas();

        /*
        echo $this->render(  'filter', [
            'taxonomies'=>$this->taxonomies,
            'metas'=>$this->metas,
            'results'=>null,
            'page'=>null,
            's'=>$_REQUEST['s']??'',
        ] );*/

        echo $this->filter_products();

    }

    public function filter_products(): string {

        do_action( 'postfilter:before_filter', $this );

        $this->taxonomies = $this->get_taxonomies( );
        $this->metas = $this->get_metas();

        $args = $this->update_filter([
            'post_type' => $this->get('post_type', 'post'),

            'post_status' => $this->get('publish', 'publish'),
            'orderby' => $this->get('order_by', [

                'menu_order' => 'DESC',
                'date' => 'DESC',
                'title' => 'ASC'

            ]),

            'paged' => (get_query_var('paged')) ? get_query_var('paged') : 1
        ]);



        return $this->do_query( $args, $this->taxonomies, $this->metas );

    }

    public function filter_products_ajax(): void {
        check_ajax_referer( 'filter-security', 'security' );
        if( !empty($_REQUEST['query_args']) ) {
            $this->set( $_REQUEST['query_args'] );
        }
        $this->filter_products();
    }


    /**
     * get structured array with taxonomies and terms
     * @return array
     */
    public function get_taxonomies( ): array {

        if( isset( $this->taxonomies ) ) {
            return $this->taxonomies;
        }

        $taxonomies = [];
        $object_taxonomies = get_object_taxonomies(array('post_type' => $this->get('post_type' ) ));
        $filter_taxonomies = array_diff($object_taxonomies, explode(',', $this->get('exclude_cat', '' ) ));

        foreach( $filter_taxonomies as $taxonomy ) :

            $terms = get_terms( $taxonomy );
            $details = get_taxonomy( $taxonomy );

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $details->name)));

            if( ! empty( $terms ) ) {

                $taxonomies[$slug] = [
                    'label'=> $details->label,
                    'tax'=> $taxonomy,
                    'terms'=>[]
                ];

                foreach( $terms as $term ) :
                    $taxonomies[$slug]['terms'][$term->slug]= $term->name;
                endforeach;
            }
        endforeach;

        return apply_filters( 'postfilter:get_taxonomies', $taxonomies );
    }

    /**
     * get structured array with filter parameters for wp_query
     * these will be added to meta_query
     */
    public function get_metas( ): array{

        if( isset( $this->metas ) ) {
            return  $this->metas;
        }

        $filters_arr=[];
        $this->filters = $this->get_filters();

        $request = array_merge( $_REQUEST['metas']??[], $_GET );
        foreach( $request as $category => $value ) {
            if( !isset( $this->filters[$category] ) ) {
                $filters_arr[$category] = $value;
            }
        }

        return apply_filters( 'postfilter:get_metas', $filters_arr );
    }

    /**
     * get structured array with filter parameters for wp_query
     * these will be added to tax_query
     * @return array
     */
    public function get_filters( ): array {

        if( isset( $this->filters ) ) {
            return  $this->filters;
        }

        $filters_arr=[];
        $filters = $this->get_query();
        $taxonomies = $this->get_taxonomies( );

        foreach( $filters as $category => $terms ) {
            if( isset( $taxonomies[$category] ) ) {
                $terms_arr = is_string( $terms) ? explode(',', $terms) : array_keys($terms);
                foreach ($terms_arr as $term_slug) {
                    if (isset($taxonomies[$category]['terms'][$term_slug])) {
                        $filters_arr[$category][$term_slug] = $taxonomies[$category]['terms'][$term_slug];
                    }
                }
            }
        }

        return apply_filters( 'postfilter:get_filters', $filters_arr );
    }

    /**
     * get query already set by filter or WordPress
     * $wp_query->query is used when we are on a tag archive
     * $_REQUEST['filter'] will be set when ajax is used
     *
     * @return array
     */
    public function get_query(): array{
        $filters = [];

        global $wp_query;
        if( !empty( $wp_query->query )) {
            foreach( $wp_query->query as $key=>$value ) {
                $filters[$key] = $value;
            };
        }

        return apply_filters( 'postfilter:get_query', array_merge($filters, $_REQUEST['filter'] ?? $_GET ) );
    }


    public function enqueue_script( ): void {
        $this->script->setLocalizedVar( 'filter_data', [
            'filters'=> json_encode( $this->get_filters() ),
            'metas'=> json_encode( $this->get_metas() ),
            'query_args' => $this->get( 'query_args' ),
            'ajaxurl'=> admin_url( 'admin-ajax.php' ),
            'action'=> $this->get('id'),
            //'nonce'=>wp_create_nonce( $this->get('id') .'-security' )
        ]);

        wp_enqueue_script( $this->script->getHandle() );
        $this->script->localize();
    }
    public function register_script( ): void {
        // enqueue and localize script
        $asset = plugin_dir_url( __FILE__ ) . 'assets/filter.js';
        $this->script =  \Netdust\App::get( AssetManager::class )->script(
            'filter-js', $asset,  ['deps'=> ['jquery'], 'footer'=>true, 'ver'=>'0.4'], false
        );

        add_action('wp_enqueue_scripts', function() {
            wp_register_script(
                $this->script->getHandle(),
                $this->script->getUrl(),
                $this->script->getDependencies(),
                $this->script->getVersion(),
                $this->script->getInFooter()
            );
        });

    }

    //search ACF fields
    //deprecated
    public function search_custom_meta_acf_alter_search($search,$qry) {
        global $wpdb;
        remove_filter('posts_search',[$this,'search_custom_meta_acf_alter_search'],1);
        $add = $wpdb->prepare("(CAST(CMS15.meta_value AS CHAR) LIKE '%%%s%%')", $qry->get( 's' ) );
        $search = preg_replace('|\(\((.+)\)\)|','(($1 OR '.$add.'))',$search);

        return $search;
    }

    public function search_custom_meta_acf_add_join($joins,$qry) {
        global $wpdb;
        remove_filter('posts_join',[$this,'search_custom_meta_acf_add_join']);
        return $joins . " INNER JOIN {$wpdb->postmeta} as CMS15 ON ({$wpdb->posts}.ID = CMS15.post_id)";
    }

    public function search_custom_meta_acf_distinct($distinct,$qry) {
        remove_filter('posts_distinct',[$this,'search_custom_meta_acf_distinct']);
        return "DISTINCT";
    }

    public function modify_acf_relationship_search_query( ) {
        add_filter('posts_join',[$this,'search_custom_meta_acf_add_join'],999,2);
        add_filter('posts_search',[$this,'search_custom_meta_acf_alter_search'],999,2);
        add_filter('posts_distinct', [$this,'search_custom_meta_acf_distinct'],999,2);
    }

}