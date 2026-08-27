<?php
/**
 * Registers and enqueues admin + frontend assets.
 *
 * Frontend reader assets (PDF.js + page-flip) are only *registered* here and
 * *enqueued* on demand by the shortcode/template code that actually needs
 * them, so pages without a flipbook never load the extra JS/CSS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPF_Assets {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
	}

	public function admin_assets( $hook ) {
		global $post_type;

		if ( RPF_CPT !== $post_type ) {
			return;
		}

		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_style( 'rpf-admin', RPF_URL . 'assets/css/rpf-admin.css', array(), RPF_VERSION );
	}

	public function register_frontend_assets() {
		wp_register_style( 'rpf-reader', RPF_URL . 'assets/css/rpf.css', array(), RPF_VERSION );

		wp_register_script( 'rpf-pdfjs', RPF_URL . 'assets/js/vendor/pdfjs/pdf.min.js', array(), RPF_VERSION, true );
		wp_register_script( 'rpf-page-flip', RPF_URL . 'assets/js/vendor/page-flip/page-flip.browser.js', array(), RPF_VERSION, true );

		wp_register_script(
			'rpf-flipbook',
			RPF_URL . 'assets/js/rpf-flipbook.js',
			array( 'rpf-pdfjs', 'rpf-page-flip' ),
			RPF_VERSION,
			true
		);

		wp_localize_script(
			'rpf-flipbook',
			'RPF_Reader',
			array(
				'workerUrl' => RPF_URL . 'assets/js/vendor/pdfjs/pdf.worker.min.js',
				'i18n'      => array(
					'loading'    => __( 'Carregando edição…', 'ranktop-pdf-flip' ),
					'error'      => __( 'Não foi possível carregar o PDF desta edição.', 'ranktop-pdf-flip' ),
					'page'       => __( 'Página', 'ranktop-pdf-flip' ),
					'of'         => __( 'de', 'ranktop-pdf-flip' ),
					'download'   => __( 'Baixar PDF', 'ranktop-pdf-flip' ),
					'fullscreen' => __( 'Tela cheia', 'ranktop-pdf-flip' ),
					'thumbnails' => __( 'Miniaturas', 'ranktop-pdf-flip' ),
					'zoomIn'     => __( 'Aumentar zoom', 'ranktop-pdf-flip' ),
					'zoomOut'    => __( 'Diminuir zoom', 'ranktop-pdf-flip' ),
					'next'       => __( 'Próxima página', 'ranktop-pdf-flip' ),
					'prev'       => __( 'Página anterior', 'ranktop-pdf-flip' ),
				),
			)
		);
	}

	/**
	 * Enqueue everything the flipbook reader needs. Safe to call multiple
	 * times (WordPress de-dupes enqueues).
	 */
	public static function enqueue_reader() {
		wp_enqueue_style( 'rpf-reader' );
		wp_enqueue_script( 'rpf-flipbook' );
	}

	/**
	 * Enqueue the (lightweight) archive/listing styles only.
	 */
	public static function enqueue_archive() {
		wp_enqueue_style( 'rpf-reader' );
	}
}
