<?php
/**
 * Default single "Edição" template — loads the flipbook reader.
 * A theme can override this by providing single-rpf_edicao.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$data = RPF_Helpers::get_edicao_data( get_the_ID() );
	?>
	<main id="rpf-single" class="rpf-single-edicao">
		<article <?php post_class( 'rpf-edicao' ); ?> id="post-<?php the_ID(); ?>">
			<header class="rpf-edicao-header">
				<h1 class="rpf-edicao-title"><?php the_title(); ?></h1>
				<p class="rpf-edicao-meta">
					<?php if ( ! empty( $data['numero'] ) ) : ?>
						<span class="rpf-edicao-numero"><?php printf( esc_html__( 'Edição %s', 'ranktop-pdf-flip' ), esc_html( $data['numero'] ) ); ?></span>
						<span class="rpf-sep">&middot;</span>
					<?php endif; ?>
					<span class="rpf-edicao-data"><?php echo esc_html( $data['data'] ); ?></span>
				</p>
			</header>

			<?php if ( get_the_content() ) : ?>
				<div class="rpf-edicao-descricao"><?php the_content(); ?></div>
			<?php endif; ?>

			<div class="rpf-edicao-reader">
				<?php echo RPF_Helpers::render_reader_html( $data['pdf_url'], array( 'height' => 750 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</article>
	</main>
	<?php
endwhile;

get_footer();
