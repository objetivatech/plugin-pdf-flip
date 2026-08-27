<?php
/**
 * Frontend: template loading + archive filtering (ano/mês, busca) for "Edições".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPF_Frontend {

	private static $instance = null;

	const PER_PAGE = 12;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'template_include', array( $this, 'template_loader' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_archive_query' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_reader' ), 20 );
	}

	public function template_loader( $template ) {
		if ( is_singular( RPF_CPT ) ) {
			$theme_file = locate_template( array( 'single-' . RPF_CPT . '.php' ) );
			return $theme_file ? $theme_file : RPF_PATH . 'templates/single-edicao.php';
		}

		if ( is_post_type_archive( RPF_CPT ) ) {
			$theme_file = locate_template( array( 'archive-' . RPF_CPT . '.php' ) );
			return $theme_file ? $theme_file : RPF_PATH . 'templates/archive-edicoes.php';
		}

		return $template;
	}

	public function maybe_enqueue_reader() {
		if ( is_singular( RPF_CPT ) ) {
			RPF_Assets::enqueue_reader();
		} elseif ( is_post_type_archive( RPF_CPT ) ) {
			RPF_Assets::enqueue_archive();
		}
	}

	public function filter_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( RPF_CPT ) ) {
			return;
		}

		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );
		$query->set( 'posts_per_page', self::PER_PAGE );

		$ano = isset( $_GET['rpf_ano'] ) ? absint( $_GET['rpf_ano'] ) : 0;
		$mes = isset( $_GET['rpf_mes'] ) ? absint( $_GET['rpf_mes'] ) : 0;

		if ( $ano ) {
			$date_query = array( 'year' => $ano );
			if ( $mes ) {
				$date_query['month'] = $mes;
			}
			$query->set( 'date_query', array( $date_query ) );
		}
	}

	/**
	 * Distinct year => [months] map for published editions, used to build
	 * the archive filter dropdowns.
	 *
	 * @return array
	 */
	public static function get_available_periods() {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT YEAR(post_date) AS ano, MONTH(post_date) AS mes
				 FROM {$wpdb->posts}
				 WHERE post_type = %s AND post_status = 'publish'
				 ORDER BY ano DESC, mes DESC",
				RPF_CPT
			)
		);

		$periods = array();
		foreach ( $rows as $row ) {
			$ano = (int) $row->ano;
			if ( ! isset( $periods[ $ano ] ) ) {
				$periods[ $ano ] = array();
			}
			$periods[ $ano ][] = (int) $row->mes;
		}

		return $periods;
	}
}
