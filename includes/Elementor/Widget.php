<?php
/**
 * Elementor Widget Integration
 * 
 * @package    Nafas_Chatbot_Pro
 * @subpackage Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor Widget
 */
class NCP_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ncp_chatbot';
	}

	public function get_title() {
		return __( 'Nafas Chatbot', 'nafas-chatbot-pro' );
	}

	public function get_icon() {
		return 'eicon-comments';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_keywords() {
		return [ 'chat', 'chatbot', 'ai', 'nafas' ];
	}

	public function get_script_depends() {
		return [ 'ncp-chatbot' ];
	}

	public function get_style_depends() {
		return [ 'ncp-chatbot' ];
	}

	protected function register_controls() {
		// Content Tab
		$this->start_controls_section(
			'ncp_content_section',
			[
				'label' => __( 'Chatbot Settings', 'nafas-chatbot-pro' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ncp_provider',
			[
				'label'   => __( 'AI Provider', 'nafas-chatbot-pro' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'avalai' => 'AvalAI',
					'openai' => 'OpenAI',
					'custom' => 'Custom API',
				],
				'default' => 'avalai',
			]
		);

		$this->add_control(
			'ncp_floating_mode',
			[
				'label'        => __( 'Floating Mode', 'nafas-chatbot-pro' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'Yes',
				'label_off'    => 'No',
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'ncp_launcher_position',
			[
				'label'   => __( 'Launcher Position', 'nafas-chatbot-pro' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'left'  => __( 'Left', 'nafas-chatbot-pro' ),
					'right' => __( 'Right', 'nafas-chatbot-pro' ),
				],
				'default' => 'right',
				'condition' => [
					'ncp_floating_mode' => 'yes',
				],
			]
		);

		$this->add_control(
			'ncp_open_by_default',
			[
				'label'        => __( 'Open by Default', 'nafas-chatbot-pro' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'Yes',
				'label_off'    => 'No',
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->add_control(
			'ncp_temperature',
			[
				'label'       => __( 'Temperature', 'nafas-chatbot-pro' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'range'       => [
					'min' => 0,
					'max' => 2,
					'step' => 0.1,
				],
				'default'     => [ 'size' => 0.7 ],
				'description' => __( 'Controls randomness: 0 = deterministic, 2 = creative', 'nafas-chatbot-pro' ),
			]
		);

		$this->add_control(
			'ncp_max_tokens',
			[
				'label'       => __( 'Max Tokens', 'nafas-chatbot-pro' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'range'       => [
					'min' => 64,
					'max' => 4096,
					'step' => 64,
				],
				'default'     => [ 'size' => 512 ],
				'description' => __( 'Maximum response length', 'nafas-chatbot-pro' ),
			]
		);

		$this->end_controls_section();

		// Style Tab
		$this->start_controls_section(
			'ncp_style_section',
			[
				'label' => __( 'Styling', 'nafas-chatbot-pro' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'ncp_primary_color',
			[
				'label'     => __( 'Primary Color', 'nafas-chatbot-pro' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#b01618',
				'selectors' => [
					'{{WRAPPER}} .ncp-mount' => '--ncp-primary: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ncp_bg_color',
			[
				'label'     => __( 'Background Color', 'nafas-chatbot-pro' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f7f5f2',
				'selectors' => [
					'{{WRAPPER}} .ncp-mount' => '--ncp-bg-base: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ncp_panel_width',
			[
				'label'      => __( 'Panel Width', 'nafas-chatbot-pro' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'range'      => [
					'min' => 300,
					'max' => 600,
					'step' => 10,
				],
				'default'    => [ 'size' => 380 ],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .ncp-mount' => '--ncp-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ncp_panel_height',
			[
				'label'      => __( 'Panel Height', 'nafas-chatbot-pro' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'range'      => [
					'min' => 400,
					'max' => 800,
					'step' => 10,
				],
				'default'    => [ 'size' => 600 ],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .ncp-mount' => '--ncp-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		
		$config = [
			'provider'        => $settings['ncp_provider'],
			'floating_mode'   => 'yes' === $settings['ncp_floating_mode'],
			'launcher_position' => $settings['ncp_launcher_position'],
			'open_by_default' => 'yes' === $settings['ncp_open_by_default'],
			'temperature'     => (float) $settings['ncp_temperature']['size'],
			'max_tokens'      => (int) $settings['ncp_max_tokens']['size'],
		];
		
		NCP_Frontend::enqueue_assets( $config );
		echo wp_kses_post( NCP_Frontend::render_mount( NCP_Frontend::build_config( $config ) ) );
	}

	protected function content_template() {
		?>
		<div class="elementor-panel-alert elementor-panel-alert-info">
			<?php echo esc_html( __( 'Nafas Chatbot will appear here on the frontend.', 'nafas-chatbot-pro' ) ); ?>
		</div>
		<?php
	}
}
