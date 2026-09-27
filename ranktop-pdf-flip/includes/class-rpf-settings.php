<?php
/**
 * Settings page: card appearance ([edicao_ultima]) and biblioteca
 * defaults ([edicoes]).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPF_Settings {

	const OPTION_KEY = 'rpf_settings';

	private static $instance = null;

	private $page_hook = '';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function add_menu() {
		$this->page_hook = add_submenu_page(
			'edit.php?post_type=' . RPF_CPT,
			__( 'Configurações — Ranktop PDF Flip', 'ranktop-pdf-flip' ),
			__( 'Configurações', 'ranktop-pdf-flip' ),
			'manage_options',
			'rpf-settings',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'rpf-reader', RPF_URL . 'assets/css/rpf.css', array(), RPF_VERSION );
		wp_enqueue_style( 'rpf-admin', RPF_URL . 'assets/css/rpf-admin.css', array(), RPF_VERSION );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'rpf-settings-admin', RPF_URL . 'assets/js/rpf-settings-admin.js', array( 'wp-color-picker' ), RPF_VERSION, true );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'card_bg_color'             => '#ffffff',
			'card_border_color'         => '#e2e2e2',
			'card_border_width'         => 1,
			'card_border_radius'        => 8,
			'card_title_color'          => '#111111',
			'card_text_color'           => '#666666',
			'card_cta_color'            => '#2b5cff',
			'card_image_shape'          => 'retrato',
			'card_default_style'        => 'card',
			'biblioteca_colunas'        => 4,
			'biblioteca_quantidade'     => 0,
			'biblioteca_mostrar_filtro' => 1,
			'biblioteca_ordenacao'      => 'recentes',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function all() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	/**
	 * @param string $key
	 * @param mixed  $fallback
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Field definitions rendered on the settings page.
	 *
	 * @return array
	 */
	private function fields() {
		return array(
			array(
				'key'     => 'card_bg_color',
				'label'   => __( 'Cor de fundo do card', 'ranktop-pdf-flip' ),
				'type'    => 'color',
				'section' => 'rpf_card_section',
			),
			array(
				'key'     => 'card_border_color',
				'label'   => __( 'Cor da borda', 'ranktop-pdf-flip' ),
				'type'    => 'color',
				'section' => 'rpf_card_section',
			),
			array(
				'key'     => 'card_border_width',
				'label'   => __( 'Espessura da borda (px)', 'ranktop-pdf-flip' ),
				'type'    => 'number',
				'section' => 'rpf_card_section',
				'min'     => 0,
				'max'     => 10,
			),
			array(
				'key'     => 'card_border_radius',
				'label'   => __( 'Arredondamento das bordas (px)', 'ranktop-pdf-flip' ),
				'type'    => 'number',
				'section' => 'rpf_card_section',
				'min'     => 0,
				'max'     => 60,
			),
			array(
				'key'     => 'card_title_color',
				'label'   => __( 'Cor do número/título', 'ranktop-pdf-flip' ),
				'type'    => 'color',
				'section' => 'rpf_card_section',
			),
			array(
				'key'     => 'card_text_color',
				'label'   => __( 'Cor do texto secundário', 'ranktop-pdf-flip' ),
				'type'    => 'color',
				'section' => 'rpf_card_section',
			),
			array(
				'key'     => 'card_cta_color',
				'label'   => __( 'Cor do link "Ler edição"', 'ranktop-pdf-flip' ),
				'type'    => 'color',
				'section' => 'rpf_card_section',
			),
			array(
				'key'     => 'card_image_shape',
				'label'   => __( 'Formato da capa', 'ranktop-pdf-flip' ),
				'type'    => 'select',
				'section' => 'rpf_card_section',
				'options' => array(
					'retrato'  => __( 'Retrato (3:4)', 'ranktop-pdf-flip' ),
					'quadrado' => __( 'Quadrado (1:1)', 'ranktop-pdf-flip' ),
					'paisagem' => __( 'Paisagem (16:9)', 'ranktop-pdf-flip' ),
					'circular' => __( 'Circular', 'ranktop-pdf-flip' ),
				),
			),
			array(
				'key'     => 'card_default_style',
				'label'   => __( 'Layout padrão do card', 'ranktop-pdf-flip' ),
				'type'    => 'select',
				'section' => 'rpf_card_section',
				'options' => array(
					'card'   => __( 'Card (capa em cima)', 'ranktop-pdf-flip' ),
					'banner' => __( 'Banner (capa ao lado)', 'ranktop-pdf-flip' ),
				),
			),
			array(
				'key'     => 'biblioteca_colunas',
				'label'   => __( 'Colunas na grade', 'ranktop-pdf-flip' ),
				'type'    => 'number',
				'section' => 'rpf_biblioteca_section',
				'min'     => 2,
				'max'     => 8,
			),
			array(
				'key'     => 'biblioteca_quantidade',
				'label'   => __( 'Quantidade exibida (0 = todas, com paginação)', 'ranktop-pdf-flip' ),
				'type'    => 'number',
				'section' => 'rpf_biblioteca_section',
				'min'     => 0,
				'max'     => 100,
			),
			array(
				'key'     => 'biblioteca_mostrar_filtro',
				'label'   => __( 'Filtro de ano/mês', 'ranktop-pdf-flip' ),
				'type'    => 'checkbox',
				'section' => 'rpf_biblioteca_section',
			),
			array(
				'key'     => 'biblioteca_ordenacao',
				'label'   => __( 'Ordenar por', 'ranktop-pdf-flip' ),
				'type'    => 'select',
				'section' => 'rpf_biblioteca_section',
				'options' => array(
					'recentes' => __( 'Mais recentes primeiro', 'ranktop-pdf-flip' ),
					'numero'   => __( 'Número da edição (maior primeiro)', 'ranktop-pdf-flip' ),
				),
			),
		);
	}

	public function register_settings() {
		register_setting( 'rpf_settings_group', self::OPTION_KEY, array( $this, 'sanitize' ) );

		add_settings_section(
			'rpf_card_section',
			__( 'Card "Última edição" ([edicao_ultima])', 'ranktop-pdf-flip' ),
			'__return_false',
			'rpf-settings'
		);

		add_settings_section(
			'rpf_biblioteca_section',
			__( 'Biblioteca de edições ([edicoes])', 'ranktop-pdf-flip' ),
			'__return_false',
			'rpf-settings'
		);

		foreach ( $this->fields() as $field ) {
			add_settings_field(
				'rpf_field_' . $field['key'],
				$field['label'],
				array( $this, 'render_field' ),
				'rpf-settings',
				$field['section'],
				$field
			);
		}
	}

	public function render_field( $field ) {
		$value = self::get( $field['key'] );
		$name  = self::OPTION_KEY . '[' . $field['key'] . ']';

		switch ( $field['type'] ) {
			case 'color':
				printf(
					'<input type="text" class="rpf-color-field" name="%1$s" value="%2$s">',
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'number':
				printf(
					'<input type="number" class="small-text" name="%1$s" value="%2$s" min="%3$s" max="%4$s">',
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $field['min'] ),
					esc_attr( $field['max'] )
				);
				break;

			case 'select':
				echo '<select name="' . esc_attr( $name ) . '">';
				foreach ( $field['options'] as $opt_value => $opt_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $opt_value ),
						selected( $value, $opt_value, false ),
						esc_html( $opt_label )
					);
				}
				echo '</select>';
				break;

			case 'checkbox':
				printf(
					'<label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label>',
					esc_attr( $name ),
					checked( $value, 1, false ),
					esc_html__( 'Exibir', 'ranktop-pdf-flip' )
				);
				break;
		}
	}

	public function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$out      = $defaults;

		$out['card_bg_color']     = $this->sanitize_color( $input['card_bg_color'] ?? '', $defaults['card_bg_color'] );
		$out['card_border_color'] = $this->sanitize_color( $input['card_border_color'] ?? '', $defaults['card_border_color'] );
		$out['card_title_color']  = $this->sanitize_color( $input['card_title_color'] ?? '', $defaults['card_title_color'] );
		$out['card_text_color']   = $this->sanitize_color( $input['card_text_color'] ?? '', $defaults['card_text_color'] );
		$out['card_cta_color']    = $this->sanitize_color( $input['card_cta_color'] ?? '', $defaults['card_cta_color'] );

		$out['card_border_width']  = min( 10, max( 0, absint( $input['card_border_width'] ?? $defaults['card_border_width'] ) ) );
		$out['card_border_radius'] = min( 60, max( 0, absint( $input['card_border_radius'] ?? $defaults['card_border_radius'] ) ) );

		$out['card_image_shape']   = in_array( $input['card_image_shape'] ?? '', array( 'retrato', 'quadrado', 'paisagem', 'circular' ), true )
			? $input['card_image_shape']
			: $defaults['card_image_shape'];

		$out['card_default_style'] = in_array( $input['card_default_style'] ?? '', array( 'card', 'banner' ), true )
			? $input['card_default_style']
			: $defaults['card_default_style'];

		$out['biblioteca_colunas']        = min( 8, max( 2, absint( $input['biblioteca_colunas'] ?? $defaults['biblioteca_colunas'] ) ) );
		$out['biblioteca_quantidade']     = min( 100, max( 0, absint( $input['biblioteca_quantidade'] ?? $defaults['biblioteca_quantidade'] ) ) );
		$out['biblioteca_mostrar_filtro'] = empty( $input['biblioteca_mostrar_filtro'] ) ? 0 : 1;

		$out['biblioteca_ordenacao'] = in_array( $input['biblioteca_ordenacao'] ?? '', array( 'recentes', 'numero' ), true )
			? $input['biblioteca_ordenacao']
			: $defaults['biblioteca_ordenacao'];

		return $out;
	}

	private function sanitize_color( $value, $fallback ) {
		$sanitized = sanitize_hex_color( $value );
		return $sanitized ? $sanitized : $fallback;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap rpf-settings-wrap">
			<h1><?php esc_html_e( 'Ranktop PDF Flip — Configurações', 'ranktop-pdf-flip' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'rpf_settings_group' );
				do_settings_sections( 'rpf-settings' );
				submit_button();
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Pré-visualização do card', 'ranktop-pdf-flip' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Reflete as configurações salvas. Salve o formulário acima para atualizar.', 'ranktop-pdf-flip' ); ?></p>
			<?php
			$preview_data = RPF_Helpers::get_edicao_destaque();
			if ( $preview_data ) {
				echo RPF_Helpers::render_card_html( $preview_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<p>' . esc_html__( 'Cadastre ao menos uma edição para ver a pré-visualização.', 'ranktop-pdf-flip' ) . '</p>';
			}
			?>
		</div>
		<?php
	}
}
