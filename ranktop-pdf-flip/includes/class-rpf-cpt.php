<?php
/**
 * Registers the "Edições" custom post type.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPF_CPT {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'                  => __( 'Edições', 'ranktop-pdf-flip' ),
			'singular_name'         => __( 'Edição', 'ranktop-pdf-flip' ),
			'menu_name'             => __( 'Edições', 'ranktop-pdf-flip' ),
			'add_new'               => __( 'Adicionar nova', 'ranktop-pdf-flip' ),
			'add_new_item'          => __( 'Adicionar nova edição', 'ranktop-pdf-flip' ),
			'edit_item'             => __( 'Editar edição', 'ranktop-pdf-flip' ),
			'new_item'              => __( 'Nova edição', 'ranktop-pdf-flip' ),
			'view_item'             => __( 'Ver edição', 'ranktop-pdf-flip' ),
			'view_items'            => __( 'Ver edições', 'ranktop-pdf-flip' ),
			'search_items'          => __( 'Buscar edições', 'ranktop-pdf-flip' ),
			'not_found'             => __( 'Nenhuma edição encontrada', 'ranktop-pdf-flip' ),
			'not_found_in_trash'    => __( 'Nenhuma edição encontrada na lixeira', 'ranktop-pdf-flip' ),
			'all_items'             => __( 'Todas as edições', 'ranktop-pdf-flip' ),
			'archives'              => __( 'Arquivo de edições', 'ranktop-pdf-flip' ),
			'featured_image'        => __( 'Capa da edição', 'ranktop-pdf-flip' ),
			'set_featured_image'    => __( 'Definir capa da edição', 'ranktop-pdf-flip' ),
			'remove_featured_image' => __( 'Remover capa da edição', 'ranktop-pdf-flip' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'menu_position'      => 20,
			'has_archive'        => 'edicoes',
			'rewrite'            => array(
				'slug'       => 'edicoes',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'          => 'dashicons-media-spreadsheet',
		);

		/**
		 * Filter the "Edições" post type registration args.
		 *
		 * @param array $args
		 */
		$args = apply_filters( 'rpf_cpt_args', $args );

		register_post_type( RPF_CPT, $args );
	}
}
