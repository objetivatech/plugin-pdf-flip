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
		<?php echo RPF_Helpers::render_periodo_filtro_html( $archive_url, $ano_atual, $mes_atual ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="rpf-archive-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				$data = RPF_Helpers::get_edicao_data( get_the_ID() );
				echo RPF_Helpers::render_grid_item_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
