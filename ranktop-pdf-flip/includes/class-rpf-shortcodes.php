<?php
/**
 * [edicao], [edicao_ultima] and [edicoes] shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPF_Shortcodes {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'edicao', array( $this, 'render_edicao' ) );
		add_shortcode( 'edicao_ultima', array( $this, 'render_edicao_ultima' ) );
		add_shortcode( 'edicoes', array( $this, 'render_biblioteca' ) );
	}

	/**
	 * [edicao id="123" height="700" width="900"]
	 *
	 * When "id" is omitted, falls back to the current post (if it is an
	 * edition) and finally to the most recent/featured edition.
	 */
	public function render_edicao( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'height' => 700,
				'width'  => '',
			),
			$atts,
			'edicao'
		);

		$id = absint( $atts['id'] );

		if ( $id ) {
			$data = RPF_Helpers::get_edicao_data( $id );
		} elseif ( is_singular( RPF_CPT ) ) {
			$data = RPF_Helpers::get_edicao_data( get_the_ID() );
		} else {
			$data = RPF_Helpers::get_edicao_destaque();
		}

		if ( ! $data ) {
			return '<p class="rpf-error">' . esc_html__( 'Edição não encontrada.', 'ranktop-pdf-flip' ) . '</p>';
		}

		RPF_Assets::enqueue_reader();

		return RPF_Helpers::render_reader_html(
			$data['pdf_url'],
			array(
				'height' => $atts['height'],
				'width'  => $atts['width'],
			)
		);
	}

	/**
	 * [edicao_ultima estilo="card|banner"]
	 *
	 * Colors, border and image shape are controlled globally in
	 * Edições → Configurações; "estilo" omitted uses the configured default.
	 */
	public function render_edicao_ultima( $atts ) {
		$atts = shortcode_atts(
			array(
				'estilo' => '',
			),
			$atts,
			'edicao_ultima'
		);

		$data = RPF_Helpers::get_edicao_destaque();

		if ( ! $data ) {
			return '';
		}

		wp_enqueue_style( 'rpf-reader' );

		return RPF_Helpers::render_card_html( $data, $atts['estilo'] );
	}

	/**
	 * [edicoes colunas="4" quantidade="0" filtro="sim" ordenacao="recentes"]
	 *
	 * Renders the "biblioteca"/estante grid of editions — an embeddable
	 * alternative to the plugin's own /edicoes/ archive page. Omitted
	 * attributes fall back to Edições → Configurações.
	 */
	public function render_biblioteca( $atts ) {
		$atts = shortcode_atts(
			array(
				'colunas'    => '',
				'quantidade' => '',
				'filtro'     => '',
				'ordenacao'  => '',
			),
			$atts,
			'edicoes'
		);

		$atts = array_filter( $atts, static function ( $value ) {
			return '' !== $value;
		} );

		RPF_Assets::enqueue_archive();

		return RPF_Helpers::render_biblioteca_html( $atts );
	}
}
