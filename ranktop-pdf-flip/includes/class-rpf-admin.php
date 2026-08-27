<?php
/**
 * Admin meta box, save logic and list-table columns for "Edições".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPF_Admin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . RPF_CPT, array( $this, 'save_meta' ) );

		add_filter( 'manage_' . RPF_CPT . '_posts_columns', array( $this, 'admin_columns' ) );
		add_action( 'manage_' . RPF_CPT . '_posts_custom_column', array( $this, 'admin_column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . RPF_CPT . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_numero' ) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'rpf_edicao_detalhes',
			__( 'Detalhes da edição', 'ranktop-pdf-flip' ),
			array( $this, 'render_meta_box' ),
			RPF_CPT,
			'side',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'rpf_save_meta', 'rpf_meta_nonce' );

		$numero      = get_post_meta( $post->ID, '_rpf_numero', true );
		$pdf_id      = get_post_meta( $post->ID, '_rpf_pdf_id', true );
		$destaque    = get_post_meta( $post->ID, '_rpf_destaque', true );
		$pdf_url     = $pdf_id ? wp_get_attachment_url( $pdf_id ) : '';
		$pdf_filename = $pdf_url ? basename( $pdf_url ) : '';
		?>
		<p>
			<label for="rpf_numero"><strong><?php esc_html_e( 'Número da edição', 'ranktop-pdf-flip' ); ?></strong></label><br>
			<input type="text" id="rpf_numero" name="rpf_numero" class="widefat" value="<?php echo esc_attr( $numero ); ?>" placeholder="<?php esc_attr_e( 'Ex: 125', 'ranktop-pdf-flip' ); ?>">
		</p>
		<p>
			<strong><?php esc_html_e( 'Arquivo PDF da edição', 'ranktop-pdf-flip' ); ?></strong><br>
			<input type="hidden" id="rpf_pdf_id" name="rpf_pdf_id" value="<?php echo esc_attr( $pdf_id ); ?>">
			<span id="rpf_pdf_filename" style="display:block;margin:6px 0;word-break:break-all;"><?php echo esc_html( $pdf_filename ? $pdf_filename : __( 'Nenhum arquivo selecionado.', 'ranktop-pdf-flip' ) ); ?></span>
			<button type="button" class="button" id="rpf_pdf_select"><?php esc_html_e( 'Selecionar PDF', 'ranktop-pdf-flip' ); ?></button>
			<button type="button" class="button" id="rpf_pdf_remove" style="<?php echo $pdf_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remover', 'ranktop-pdf-flip' ); ?></button>
		</p>
		<p>
			<label for="rpf_destaque">
				<input type="checkbox" id="rpf_destaque" name="rpf_destaque" value="1" <?php checked( $destaque, '1' ); ?>>
				<strong><?php esc_html_e( 'Edição em destaque', 'ranktop-pdf-flip' ); ?></strong>
			</label>
			<br>
			<span class="description"><?php esc_html_e( 'Se marcada, esta edição é usada pelo shortcode [edicao_ultima] em vez da mais recente por data.', 'ranktop-pdf-flip' ); ?></span>
		</p>
		<script>
		( function( $ ) {
			var frame;
			$( '#rpf_pdf_select' ).on( 'click', function( e ) {
				e.preventDefault();
				if ( frame ) {
					frame.open();
					return;
				}
				frame = wp.media( {
					title: <?php echo wp_json_encode( __( 'Selecionar arquivo PDF', 'ranktop-pdf-flip' ) ); ?>,
					button: { text: <?php echo wp_json_encode( __( 'Usar este PDF', 'ranktop-pdf-flip' ) ); ?> },
					library: { type: 'application/pdf' },
					multiple: false
				} );
				frame.on( 'select', function() {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					$( '#rpf_pdf_id' ).val( attachment.id );
					$( '#rpf_pdf_filename' ).text( attachment.filename || attachment.url );
					$( '#rpf_pdf_remove' ).show();
				} );
				frame.open();
			} );
			$( '#rpf_pdf_remove' ).on( 'click', function( e ) {
				e.preventDefault();
				$( '#rpf_pdf_id' ).val( '' );
				$( '#rpf_pdf_filename' ).text( <?php echo wp_json_encode( __( 'Nenhum arquivo selecionado.', 'ranktop-pdf-flip' ) ); ?> );
				$( this ).hide();
			} );
		} )( jQuery );
		</script>
		<?php
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['rpf_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['rpf_meta_nonce'] ), 'rpf_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['rpf_numero'] ) ) {
			update_post_meta( $post_id, '_rpf_numero', sanitize_text_field( wp_unslash( $_POST['rpf_numero'] ) ) );
		}

		if ( isset( $_POST['rpf_pdf_id'] ) && '' !== $_POST['rpf_pdf_id'] ) {
			update_post_meta( $post_id, '_rpf_pdf_id', absint( $_POST['rpf_pdf_id'] ) );
		} else {
			delete_post_meta( $post_id, '_rpf_pdf_id' );
		}

		if ( isset( $_POST['rpf_destaque'] ) ) {
			update_post_meta( $post_id, '_rpf_destaque', '1' );
		} else {
			delete_post_meta( $post_id, '_rpf_destaque' );
		}
	}

	public function admin_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['rpf_capa']     = __( 'Capa', 'ranktop-pdf-flip' );
				$new['rpf_numero']   = __( 'Número', 'ranktop-pdf-flip' );
				$new['rpf_pdf']      = __( 'PDF', 'ranktop-pdf-flip' );
				$new['rpf_destaque'] = __( 'Destaque', 'ranktop-pdf-flip' );
			}
		}
		return $new;
	}

	public function admin_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'rpf_capa':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 40, 56 ) );
				} else {
					echo '&#8212;';
				}
				break;

			case 'rpf_numero':
				echo esc_html( get_post_meta( $post_id, '_rpf_numero', true ) );
				break;

			case 'rpf_pdf':
				$pdf_id = get_post_meta( $post_id, '_rpf_pdf_id', true );
				if ( $pdf_id ) {
					printf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( wp_get_attachment_url( $pdf_id ) ),
						esc_html__( 'Ver arquivo', 'ranktop-pdf-flip' )
					);
				} else {
					echo '&#8212;';
				}
				break;

			case 'rpf_destaque':
				echo get_post_meta( $post_id, '_rpf_destaque', true ) ? '&#9733;' : '&#8212;';
				break;
		}
	}

	public function sortable_columns( $columns ) {
		$columns['rpf_numero'] = 'rpf_numero';
		return $columns;
	}

	public function sort_by_numero( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'rpf_numero' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_rpf_numero' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}
}
