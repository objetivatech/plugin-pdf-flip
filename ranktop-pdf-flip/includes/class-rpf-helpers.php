<?php
/**
 * Shared helpers for reading edition data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPF_Helpers {

	/**
	 * Get a normalized data array for an edition post.
	 *
	 * @param int|WP_Post $post
	 * @return array|null
	 */
	public static function get_edicao_data( $post ) {
		$post = get_post( $post );

		if ( ! $post || RPF_CPT !== $post->post_type ) {
			return null;
		}

		$pdf_id = (int) get_post_meta( $post->ID, '_rpf_pdf_id', true );

		return array(
			'id'       => $post->ID,
			'titulo'   => get_the_title( $post ),
			'numero'   => get_post_meta( $post->ID, '_rpf_numero', true ),
			'data'     => get_the_date( '', $post ),
			'data_iso' => get_the_date( 'c', $post ),
			'capa_url' => get_the_post_thumbnail_url( $post, 'medium' ),
			'pdf_id'   => $pdf_id,
			'pdf_url'  => $pdf_id ? wp_get_attachment_url( $pdf_id ) : '',
			'destaque' => (bool) get_post_meta( $post->ID, '_rpf_destaque', true ),
			'permalink' => get_permalink( $post ),
		);
	}

	/**
	 * Find the edition to feature: manually flagged "destaque" wins,
	 * otherwise the most recently published edition.
	 *
	 * @return array|null
	 */
	public static function get_edicao_destaque() {
		$featured = get_posts(
			array(
				'post_type'      => RPF_CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => '_rpf_destaque',
				'meta_value'     => '1',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( ! empty( $featured ) ) {
			return self::get_edicao_data( $featured[0] );
		}

		$latest = get_posts(
			array(
				'post_type'      => RPF_CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( ! empty( $latest ) ) {
			return self::get_edicao_data( $latest[0] );
		}

		return null;
	}

	/**
	 * Render the flipbook reader markup for a given PDF url.
	 *
	 * @param string $pdf_url
	 * @param array  $args {
	 *     @type int|string $height CSS height in px (default 700).
	 *     @type int|string $width  Optional max-width in px.
	 * }
	 * @return string
	 */
	public static function render_reader_html( $pdf_url, $args = array() ) {
		if ( ! $pdf_url ) {
			return '<p class="rpf-error">' . esc_html__( 'Esta edição ainda não possui um PDF associado.', 'ranktop-pdf-flip' ) . '</p>';
		}

		$defaults = array(
			'height' => 700,
			'width'  => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$style = 'height:' . absint( $args['height'] ) . 'px;';
		if ( $args['width'] ) {
			$style .= 'max-width:' . absint( $args['width'] ) . 'px;';
		}

		ob_start();
		?>
		<div class="rpf-flipbook" data-pdf-url="<?php echo esc_url( $pdf_url ); ?>" data-height="<?php echo esc_attr( absint( $args['height'] ) ); ?>" style="<?php echo esc_attr( $style ); ?>">
			<div class="rpf-toolbar">
				<button type="button" class="rpf-btn rpf-prev" aria-label="<?php esc_attr_e( 'Página anterior', 'ranktop-pdf-flip' ); ?>">&#8249;</button>
				<span class="rpf-page-indicator"><span class="rpf-current">1</span> / <span class="rpf-total">&#8211;</span></span>
				<button type="button" class="rpf-btn rpf-next" aria-label="<?php esc_attr_e( 'Próxima página', 'ranktop-pdf-flip' ); ?>">&#8250;</button>
				<span class="rpf-toolbar-spacer"></span>
				<button type="button" class="rpf-btn rpf-zoom-out" aria-label="<?php esc_attr_e( 'Diminuir zoom', 'ranktop-pdf-flip' ); ?>">&minus;</button>
				<button type="button" class="rpf-btn rpf-zoom-in" aria-label="<?php esc_attr_e( 'Aumentar zoom', 'ranktop-pdf-flip' ); ?>">+</button>
				<button type="button" class="rpf-btn rpf-thumbnails" aria-label="<?php esc_attr_e( 'Miniaturas', 'ranktop-pdf-flip' ); ?>" aria-pressed="false">&#9638;</button>
				<button type="button" class="rpf-btn rpf-fullscreen" aria-label="<?php esc_attr_e( 'Tela cheia', 'ranktop-pdf-flip' ); ?>">&#10021;</button>
				<a class="rpf-btn rpf-download" href="<?php echo esc_url( $pdf_url ); ?>" download aria-label="<?php esc_attr_e( 'Baixar PDF', 'ranktop-pdf-flip' ); ?>">&#8681;</a>
			</div>
			<div class="rpf-stage">
				<div class="rpf-loading"><?php esc_html_e( 'Carregando edição…', 'ranktop-pdf-flip' ); ?></div>
				<div class="rpf-book-wrapper">
					<div class="rpf-book"></div>
				</div>
				<div class="rpf-thumb-panel" hidden></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the "última edição" card.
	 *
	 * @param array  $data  Edition data from get_edicao_data()/get_edicao_destaque().
	 * @param string $estilo "card" or "banner".
	 * @return string
	 */
	public static function render_card_html( $data, $estilo = 'card' ) {
		if ( ! $data ) {
			return '';
		}

		$estilo = in_array( $estilo, array( 'card', 'banner' ), true ) ? $estilo : 'card';

		ob_start();
		?>
		<div class="rpf-card rpf-card--<?php echo esc_attr( $estilo ); ?>">
			<a class="rpf-card-link" href="<?php echo esc_url( $data['permalink'] ); ?>">
				<?php if ( $data['capa_url'] ) : ?>
					<span class="rpf-card-capa">
						<img src="<?php echo esc_url( $data['capa_url'] ); ?>" alt="<?php echo esc_attr( $data['titulo'] ); ?>" loading="lazy">
					</span>
				<?php endif; ?>
				<span class="rpf-card-info">
					<span class="rpf-card-label"><?php esc_html_e( 'Última edição', 'ranktop-pdf-flip' ); ?></span>
					<?php if ( $data['numero'] ) : ?>
						<span class="rpf-card-numero"><?php printf( esc_html__( 'Edição %s', 'ranktop-pdf-flip' ), esc_html( $data['numero'] ) ); ?></span>
					<?php endif; ?>
					<span class="rpf-card-data"><?php printf( esc_html__( 'Publicado em %s', 'ranktop-pdf-flip' ), esc_html( $data['data'] ) ); ?></span>
					<span class="rpf-card-cta"><?php esc_html_e( 'Ler edição', 'ranktop-pdf-flip' ); ?> &rarr;</span>
				</span>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}
}
