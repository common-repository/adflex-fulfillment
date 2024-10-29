<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'aff_money_format' ) ) {
	function aff_logger( \Exception $e ) {
		$data = [ 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine() ];
		error_log( json_encode( $data ) );
	}
}

if ( ! function_exists( 'aff_money_format' ) ) {
	function aff_money_format( $money, $currency = '$', $decimal = 2 ) {
		return $currency . number_format( $money, $decimal );
	}
}
if ( ! function_exists( 'aff_sanitize_arr' ) ) {
	function aff_sanitize_arr( $arr = [] ) {
		return array_map( 'sanitize_text_field', $arr );
	}
}
