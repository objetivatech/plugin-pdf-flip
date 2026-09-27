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
	 * Render the "última edição" card. Colors, border and image shape come
	 * from the plugin's global settings (RPF_Settings); $estilo is the only
	 * per-instance override, and falls back to the configured default.
	 *
	 * @param array  $data   Edition data from get_edicao_data()/get_edicao_destaque().
	 * @param string $estilo "card" or "banner". Empty uses the configured default.
	 * @return string
	 */
	public static function render_card_html( $data, $estilo = '' ) {
		if ( ! $data ) {
			return '';
		}

		$settings = RPF_Settings::all();
		$estilo   = in_array( $estilo, array( 'card', 'banner' ), true ) ? $estilo : $settings['card_default_style'];
		$shape    = in_array( $settings['card_image_shape'], array( 'retrato', 'quadrado', 'paisagem', 'circular' ), true ) ? $settings['card_image_shape'] : 'retrato';

		$style_vars = sprintf(
			'--rpf-card-bg:%1$s;--rpf-card-border-color:%2$s;--rpf-card-border-width:%3$dpx;--rpf-card-radius:%4$dpx;--rpf-card-title-color:%5$s;--rpf-card-text-color:%6$s;--rpf-card-cta-color:%7$s;',
			sanitize_hex_color( $settings['card_bg_color'] ) ?: '#ffffff',
			sanitize_hex_color( $settings['card_border_color'] ) ?: '#e2e2e2',
			absint( $settings['card_border_width'] ),
			absint( $settings['card_border_radius'] ),
			sanitize_hex_color( $settings['card_title_color'] ) ?: '#111111',
			sanitize_hex_color( $settings['card_text_color'] ) ?: '#666666',
			sanitize_hex_color( $settings['card_cta_color'] ) ?: '#2b5cff'
		);

		ob_start();
		?>
		<div class="rpf-card rpf-card--<?php echo esc_attr( $estilo ); ?> rpf-shape-<?php echo esc_attr( $shape ); ?>" style="<?php echo esc_attr( $style_vars ); ?>">
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

	/**
	 * Render a single grid item (used by both the archive template and the
	 * [edicoes] "biblioteca" shortcode).
	 *
	 * @param array $data
	 * @return string
	 */
	public static function render_grid_item_html( $data ) {
		ob_start();
		?>
		<a class="rpf-archive-item" href="<?php echo esc_url( $data['permalink'] ); ?>">
			<span class="rpf-archive-capa">
				<?php if ( $data['capa_url'] ) : ?>
					<img src="<?php echo esc_url( $data['capa_url'] ); ?>" alt="<?php echo esc_attr( $data['titulo'] ); ?>" loading="lazy">
				<?php else : ?>
					<span class="rpf-archive-capa-placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</span>
			<span class="rpf-archive-item-info">
				<?php if ( $data['numero'] ) : ?>
					<span class="rpf-archive-numero"><?php printf( esc_html__( 'Edição %s', 'ranktop-pdf-flip' ), esc_html( $data['numero'] ) ); ?></span>
				<?php endif; ?>
				<span class="rpf-archive-data"><?php echo esc_html( $data['data'] ); ?></span>
			</span>
		</a>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the ano/mês filter form (used by both the archive template and
	 * the [edicoes] "biblioteca" shortcode).
	 *
	 * @param string $action_url
	 * @param int    $ano_atual
	 * @param int    $mes_atual
	 * @return string
	 */
	public static function render_periodo_filtro_html( $action_url, $ano_atual = 0, $mes_atual = 0 ) {
		$periods = RPF_Frontend::get_available_periods();

		if ( empty( $periods ) ) {
			return '';
		}

		ob_start();
		?>
		<form class="rpf-archive-filter" method="get" action="<?php echo esc_url( $action_url ); ?>">
			<label class="screen-reader-text" for="rpf-filtro-ano"><?php esc_html_e( 'Ano', 'ranktop-pdf-flip' ); ?></label>
			<select id="rpf-filtro-ano" name="rpf_ano" onchange="this.form.submit()">
				<option value=""><?php esc_html_e( 'Todos os anos', 'ranktop-pdf-flip' ); ?></option>
				<?php foreach ( array_keys( $periods ) as $ano ) : ?>
					<option value="<?php echo esc_attr( $ano ); ?>" <?php selected( $ano_atual, $ano ); ?>><?php echo esc_html( $ano ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php if ( $ano_atual && ! empty( $periods[ $ano_atual ] ) ) : ?>
				<label class="screen-reader-text" for="rpf-filtro-mes"><?php esc_html_e( 'Mês', 'ranktop-pdf-flip' ); ?></label>
				<select id="rpf-filtro-mes" name="rpf_mes" onchange="this.form.submit()">
					<option value=""><?php esc_html_e( 'Todos os meses', 'ranktop-pdf-flip' ); ?></option>
					<?php foreach ( $periods[ $ano_atual ] as $mes ) : ?>
						<option value="<?php echo esc_attr( $mes ); ?>" <?php selected( $mes_atual, $mes ); ?>>
							<?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $mes, 1 ) ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<?php if ( $ano_atual ) : ?>
				<a class="rpf-filter-clear" href="<?php echo esc_url( remove_query_arg( array( 'rpf_ano', 'rpf_mes', 'rpf_pg' ), $action_url ) ); ?>"><?php esc_html_e( 'Limpar filtro', 'ranktop-pdf-flip' ); ?></a>
			<?php endif; ?>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the [edicoes] "biblioteca/estante" grid: an embeddable, self
	 * contained listing of editions (own query, own pagination), independent
	 * from the main "/edicoes/" archive page.
	 *
	 * @param array $atts {
	 *     @type int    $colunas    Grid columns (2-8).
	 *     @type int    $quantidade Editions to show; 0 = all, paginated.
	 *     @type string $filtro     "sim" or "nao" — show the ano/mês filter.
	 *     @type string $ordenacao  "recentes" or "numero".
	 * }
	 * @return string
	 */
	public static function render_biblioteca_html( $atts = array() ) {
		$settings = RPF_Settings::all();

		$atts = wp_parse_args(
			$atts,
			array(
				'colunas'    => $settings['biblioteca_colunas'],
				'quantidade' => $settings['biblioteca_quantidade'],
				'filtro'     => $settings['biblioteca_mostrar_filtro'] ? 'sim' : 'nao',
				'ordenacao'  => $settings['biblioteca_ordenacao'],
			)
		);

		$colunas        = min( 8, max( 2, absint( $atts['colunas'] ) ) );
		$quantidade     = absint( $atts['quantidade'] );
		$mostrar_filtro = ( 'nao' !== $atts['filtro'] );
		$ordenacao      = in_array( $atts['ordenacao'], array( 'recentes', 'numero' ), true ) ? $atts['ordenacao'] : 'recentes';

		$ano_atual = isset( $_GET['rpf_ano'] ) ? absint( $_GET['rpf_ano'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$mes_atual = isset( $_GET['rpf_mes'] ) ? absint( $_GET['rpf_mes'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged     = isset( $_GET['rpf_pg'] ) ? max( 1, absint( $_GET['rpf_pg'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$query_args = array(
			'post_type'      => RPF_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => $quantidade > 0 ? $quantidade : RPF_Frontend::PER_PAGE,
			'paged'          => $paged,
		);

		if ( 'numero' === $ordenacao ) {
			$query_args['meta_key'] = '_rpf_numero'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['orderby']  = 'meta_value_num';
			$query_args['order']    = 'DESC';
		} else {
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'DESC';
		}

		if ( $ano_atual ) {
			$date_query = array( 'year' => $ano_atual );
			if ( $mes_atual ) {
				$date_query['month'] = $mes_atual;
			}
			$query_args['date_query'] = array( $date_query );
		}

		$query = new WP_Query( $query_args );

		ob_start();
		?>
		<div class="rpf-biblioteca">
			<?php if ( $mostrar_filtro ) : ?>
				<?php echo self::render_periodo_filtro_html( remove_query_arg( 'rpf_pg' ), $ano_atual, $mes_atual ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php if ( $query->have_posts() ) : ?>
				<div class="rpf-archive-grid" style="grid-template-columns:repeat(<?php echo (int) $colunas; ?>, 1fr);">
					<?php
					foreach ( $query->posts as $post ) :
						$item_data = self::get_edicao_data( $post );
						echo self::render_grid_item_html( $item_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					endforeach;
					?>
				</div>

				<?php if ( 0 === $quantidade && $query->max_num_pages > 1 ) : ?>
					<nav class="rpf-archive-pagination">
						<?php
						echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							array(
								'base'      => add_query_arg( 'rpf_pg', '%#%' ),
								'format'    => '',
								'current'   => $paged,
								'total'     => $query->max_num_pages,
								'prev_text' => __( '&laquo; Anterior', 'ranktop-pdf-flip' ),
								'next_text' => __( 'Próxima &raquo;', 'ranktop-pdf-flip' ),
							)
						);
						?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<p class="rpf-archive-empty"><?php esc_html_e( 'Nenhuma edição publicada ainda.', 'ranktop-pdf-flip' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
