<?php
/**
 * Default archive template for "Edições" — grid of covers with
 * ano/mês filter and pagination. A theme can override this by
 * providing archive-rpf_edicao.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$periods    = RPF_Frontend::get_available_periods();
$ano_atual  = isset( $_GET['rpf_ano'] ) ? absint( $_GET['rpf_ano'] ) : 0;
$mes_atual  = isset( $_GET['rpf_mes'] ) ? absint( $_GET['rpf_mes'] ) : 0;
$archive_url = get_post_type_archive_link( RPF_CPT );
?>
<main id="rpf-archive" class="rpf-archive-edicoes">
	<header class="rpf-archive-header">
		<h1 class="rpf-archive-title"><?php esc_html_e( 'Edições', 'ranktop-pdf-flip' ); ?></h1>
	</header>

	<?php if ( ! empty( $periods ) ) : ?>
		<form class="rpf-archive-filter" method="get" action="<?php echo esc_url( $archive_url ); ?>">
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
				<a class="rpf-filter-clear" href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'Limpar filtro', 'ranktop-pdf-flip' ); ?></a>
			<?php endif; ?>
		</form>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="rpf-archive-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				$data = RPF_Helpers::get_edicao_data( get_the_ID() );
				?>
				<a class="rpf-archive-item" href="<?php echo esc_url( $data['permalink'] ); ?>">
					<span class="rpf-archive-capa">
						<?php if ( $data['capa_url'] ) : ?>
							<img src="<?php echo esc_url( $data['capa_url'] ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
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
			endwhile;
			?>
		</div>

		<nav class="rpf-archive-pagination">
			<?php
			echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				array(
					'prev_text' => __( '&laquo; Anterior', 'ranktop-pdf-flip' ),
					'next_text' => __( 'Próxima &raquo;', 'ranktop-pdf-flip' ),
				)
			);
			?>
		</nav>
	<?php else : ?>
		<p class="rpf-archive-empty"><?php esc_html_e( 'Nenhuma edição publicada ainda.', 'ranktop-pdf-flip' ); ?></p>
	<?php endif; ?>
</main>
<?php
get_footer();
