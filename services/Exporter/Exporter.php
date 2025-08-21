<?php
/**
 * Registers a personal data exporter
 *
 * @since   1.0.0
 * @package Underpin\Abstracts
 */



namespace Netdust\Services\Exporter;


use Netdust\Logger\Logger;

/**
 * Class Exporter
 *
 * @since   3.0.0
 */
abstract class Exporter {


    abstract protected function get_export_fields( );


    abstract protected function get_query_args( $args );


    abstract protected function get_data( $query_args );

    /**
     * Handles the actual download.
     *
     */
    public function export( $args=[] ) {

        $data      = $this->get_data( $this->get_query_args( $args ) );
        $stream    = $this->prepare_output();
        $headers   = $this->get_export_fields( );
        $file_type = $this->prepare_file_type( $args );

        if ( 'csv' == $file_type ) {
            $this->download_csv( $data, $stream, $headers );
            fclose( $stream );
            exit;
        } else if( 'xml' == $file_type ) {
            $this->download_xml( $data, $stream, $headers );
            fclose( $stream );
            exit;
        } else if( 'json' == $file_type ) {
          $this->download_json( $data, $stream, $headers );
          fclose( $stream );
          exit;
        } else {
            do_action( 'ntdst:export_data', $data, $stream, $headers );
        }


    }

    /**
     * Prepares the output stream.
     *
     * @return resource
     */
    public function prepare_output() {

        $output  = fopen( 'php://output', 'w' );

        if ( false === $output ) {
            wp_die( __( 'Unsupported server', 'ntdst-lib' ), 500 );
        }

        return $output;
    }

    protected function get_file_name( $graph ) {
        return  wpinv_sanitize_key( "export-".$graph."_" . current_time( 'Y-m-d' ) );
    }

    /**
     * Prepares the file type.
     *
     * @return string
     */
    public function prepare_file_type( $args ) {

        $file_type = empty( $args['file_type'] ) ? 'csv' : sanitize_text_field( $args['file_type'] );
        $file_name = $this->get_file_name( strtolower( $args['action']??'fields' ) );

        header( "Content-Type:application/$file_type" );
        header( "Content-Disposition:attachment;filename=$file_name.$file_type" );

        return $file_type;
    }


    /**
     * Downloads graph as csv
     *
     * @param array $stats The stats being downloaded.
     * @param resource $stream The stream to output to.
     * @param array $headers The fields to stream.
     * @since       1.0.19
     */
    public function download_csv( $stats, $stream, $headers ) {

        // Output the csv column headers.
        fputcsv( $stream, $headers );

        // Loop through
        foreach ( $stats as $stat ) {
            $row  = array_values( $this->prepare_row( $stat, $headers ) );
            $row  = array_map( 'maybe_serialize', $row );
            fputcsv( $stream, $row );
        }

    }

    /**
     * Downloads graph as json
     *
     * @param array $stats The stats being downloaded.
     * @param resource $stream The stream to output to.
     * @param array $headers The fields to stream.
     * @since       1.0.19
     */
    public function download_json( $stats, $stream, $headers ) {

        $prepared = array();

        // Loop through
        foreach ( $stats as $stat ) {
            $prepared[] = $this->prepare_row( $stat, $headers );
        }

        fwrite( $stream, wp_json_encode( $prepared ) );

    }

    /**
     * Downloads graph as xml
     *
     * @param array $stats The stats being downloaded.
     * @param resource $stream The stream to output to.
     * @param array $headers The fields to stream.
     * @since       1.0.19
     */
    public function download_xml( $stats, $stream, $headers ) {

        $prepared = array();

        // Loop through
        foreach ( $stats as $stat ) {
            $prepared[] = $this->prepare_row( $stat, $headers );
        }

        $xml = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
        $this->convert_array_xml( $prepared, $xml );

        fwrite( $stream, $xml->asXML() );

    }

    /**
     * Converts stats array to xml
     *
     * @access      public
     * @since      1.0.19
     */
    protected function convert_array_xml( $data, $xml ) {

        // Loop through
        foreach ( $data as $key => $value ) {

            $key = preg_replace( "/[^A-Za-z0-9_\-]/", '', $key );

            if ( is_array( $value ) ) {

                if ( is_numeric( $key ) ){
                    $key = 'item'.$key; //dealing with <0/>..<n/> issues
                }

                $subnode = $xml->addChild( $key );
                $this->convert_array_xml( $value, $subnode );

            } else {
                $xml->addChild( $key, htmlspecialchars( $value ) );
            }

        }

    }

    /**
     * Prepares a single row for download.
     *
     * @param array $row The row to prepare..
     * @param array $fields The fields to stream.
     * @since       1.0.19
     * @return array
     */
    protected function prepare_row( $row, $fields ) {

        $prepared = array();

        foreach ( $fields as $field ) {
            $slug = str_replace(' ', '_', strtolower( $field ) );
            $method = "get_$slug";
            $prepared[ $field ] = '';


            if ( method_exists( $this, $method ) ) {
                $prepared[ $field ]  = sanitize_text_field( $this->$method( $row ) );
            }
            else if( is_array($row) && isset($row[ $field ]) ) {

                $prepared[ $field ] = strip_tags( $row[ $field ] );
            }
            else if ( method_exists( $this, 'get_field' ) ) {
                $prepared[ $field ]  = sanitize_text_field( $this->get_field( $field, $row ) );
            }
        }

        return $prepared;
    }


}