<?php

namespace Netdust\Services\Yootheme;

use Netdust\Service\Pages\VirtualPage;

class Yootheme_VirtualPage extends VirtualPage
{

    public function do_template() {

    }

    /**
     * hooks into the yootheme page builder
     */
    public function createPage(): void {
        parent::createPage();

        do_action( 'netdust:before_template', $this->template() );

        $app = \Yootheme\Application::getInstance();
        $app->get('config')->set('app.isBuilder', true);

        get_header();

        $this->do_template();

        get_footer();

        die();
    }

    public function custom_404_error(  ) {
        global $wp_query;

        if ( ! is_admin() && $wp_query->is_404 ) {
            $wp_query->set_404();

            status_header( 404 );
            nocache_headers();

            include get_query_template( '404' );
            exit;
        }
    }

    public function redirect_to_login(  ) {
        remove_action( 'template_redirect', [$this, 'redirect_to_login']);
        $_SESSION['subscribe_redirect_to'] = esc_url_raw( $_SERVER['REQUEST_URI'] );

        // Redirect to the login page
        wp_redirect( wp_login_url() );
    }

    public function redirect_after_login( $redirect_to, $request, $user ) {

        remove_filter( 'login_redirect', [$this, 'redirect_after_login']);

        // Check if the redirect session variable is set
        if ( isset( $_SESSION['subscribe_redirect_to'] ) ) {
            $redirect_to = $_SESSION['subscribe_redirect_to'];
            unset( $_SESSION['subscribe_redirect_to'] ); // Clear the session variable
        }

        return $redirect_to;
    }
}