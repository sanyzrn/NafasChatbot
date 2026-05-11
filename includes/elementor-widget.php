<?php
/**
 * NCP Elementor Widget
 * Full Elementor integration with live preview support.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class NCP_Elementor_Widget extends Widget_Base {

	public function get_name(): string    { return 'ncp_chatbot'; }
	public function get_title(): string   { return __( 'Nafas Chatbot Pro', 'nafas-chatbot-pro' ); }
	public function get_icon(): string    { return 'eicon-chat'; }
	public function get_categories(): array { return [ 'general' ]; }
	public function get_keywords(): array { return [ 'chatbot', 'nafas', 'ai', 'support', 'دستیار', 'چت' ]; }

	protected function register_controls(): void {
		$plugin = Nafas_Chatbot_Pro::instance();

		/* ── Content Tab ── */
		$this->start_controls_section( 'sec_identity', [ 'label' => __( '🏢 هویت شرکت', 'nafas-chatbot-pro' ) ] );

		$this->add_control( 'company_name', [
			'label'   => __( 'نام شرکت', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => get_option( 'ncp_company_name', 'شرکت نفس زیست فارمد' ),
		] );
		$this->add_control( 'company_id', [
			'label'   => __( 'شناسه شرکت', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => get_option( 'ncp_company_id', 'nafas' ),
		] );
		$this->add_control( 'products_json', [
			'label'       => __( 'محصولات (JSON)', 'nafas-chatbot-pro' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 8,
			'default'     => $plugin->default_products_json(),
			'description' => __( 'هر محصول باید دارای id و name باشد.', 'nafas-chatbot-pro' ),
		] );

		$this->end_controls_section();

		/* ── AI Settings ── */
		$this->start_controls_section( 'sec_ai', [ 'label' => __( '🤖 تنظیمات هوش مصنوعی', 'nafas-chatbot-pro' ) ] );

		$this->add_control( 'provider', [
			'label'   => __( 'سرویس‌دهنده', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => get_option( 'ncp_default_provider', 'avalai' ),
			'options' => [
				'avalai' => 'AvalAI',
				'openai' => 'OpenAI',
				'custom' => __( 'Custom', 'nafas-chatbot-pro' ),
			],
		] );
		$this->add_control( 'model', [
			'label'   => __( 'مدل', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'gpt-4o-mini',
		] );
		$this->add_control( 'system_prompt', [
			'label'   => __( 'System Prompt', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 6,
			'default' => get_option( 'ncp_system_prompt', '' ),
		] );
		$this->add_control( 'temperature', [
			'label'   => __( 'Temperature', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 0.7, 'min' => 0, 'max' => 2, 'step' => 0.1,
		] );
		$this->add_control( 'max_tokens', [
			'label'   => __( 'Max Tokens', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 512, 'min' => 64, 'max' => 4096,
		] );
		$this->add_control( 'history_length', [
			'label'   => __( 'حافظه مکالمه (جفت)', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 6, 'min' => 0, 'max' => 12,
		] );

		$this->end_controls_section();

		/* ── Features ── */
		$this->start_controls_section( 'sec_features', [ 'label' => __( '⚙️ قابلیت‌ها', 'nafas-chatbot-pro' ) ] );

		foreach ( [
			'show_launcher'  => __( 'نمایش دکمه باز‌کننده', 'nafas-chatbot-pro' ),
			'show_company'   => __( 'منوی سوال درباره شرکت', 'nafas-chatbot-pro' ),
			'show_products'  => __( 'منوی سوال درباره محصولات', 'nafas-chatbot-pro' ),
			'show_adr'       => __( 'منوی ثبت عوارض', 'nafas-chatbot-pro' ),
			'show_consult'   => __( 'منوی درخواست مشاوره', 'nafas-chatbot-pro' ),
		] as $key => $label ) {
			$this->add_control( $key, [
				'label'        => $label,
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'nafas-chatbot-pro' ),
				'label_off'    => __( 'خیر', 'nafas-chatbot-pro' ),
				'return_value' => 'yes',
				'default'      => get_option( "ncp_{$key}", '1' ) === '1' ? 'yes' : '',
			] );
		}

		$this->end_controls_section();

		/* ── Behavior ── */
		$this->start_controls_section( 'sec_behavior', [ 'label' => __( '🎛️ رفتار', 'nafas-chatbot-pro' ) ] );

		$this->add_control( 'floating_mode', [
			'label' => __( 'حالت شناور (Floating)', 'nafas-chatbot-pro' ),
			'type'  => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'open_by_default', [
			'label' => __( 'باز به صورت پیش‌فرض', 'nafas-chatbot-pro' ),
			'type'  => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => '',
		] );
		$this->add_control( 'launcher_position', [
			'label'   => __( 'موقعیت دکمه', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'right',
			'options' => [ 'right' => __( 'راست', 'nafas-chatbot-pro' ), 'left' => __( 'چپ', 'nafas-chatbot-pro' ) ],
			'condition' => [ 'floating_mode' => 'yes' ],
		] );
		$this->add_control( 'text_direction', [
			'label'   => __( 'جهت متن', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::CHOOSE,
			'default' => 'rtl',
			'options' => [
				'rtl' => [ 'title' => 'RTL', 'icon' => 'eicon-h-align-right' ],
				'ltr' => [ 'title' => 'LTR', 'icon' => 'eicon-h-align-left' ],
			],
			'toggle' => false,
		] );
		$this->add_control( 'theme_mode', [
			'label'   => __( 'تم', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'auto',
			'options' => [
				'auto'  => __( 'خودکار', 'nafas-chatbot-pro' ),
				'light' => __( 'روشن', 'nafas-chatbot-pro' ),
				'dark'  => __( 'تاریک', 'nafas-chatbot-pro' ),
			],
		] );
		$this->add_control( 'persist_history', [
			'label' => __( 'ذخیره تاریخچه', 'nafas-chatbot-pro' ),
			'type'  => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'enable_markdown', [
			'label' => __( 'پشتیبانی Markdown', 'nafas-chatbot-pro' ),
			'type'  => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'enable_emoji', [
			'label' => __( 'انتخابگر ایموجی', 'nafas-chatbot-pro' ),
			'type'  => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		] );
		$this->add_control( 'enable_notifications', [
			'label' => __( 'اعلان مرورگر', 'nafas-chatbot-pro' ),
			'type'  => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		] );

		$this->end_controls_section();

		/* ── Style Tab ── */
		$this->start_controls_section( 'sec_layout', [
			'label' => __( '📐 ابعاد', 'nafas-chatbot-pro' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'panel_width', [
			'label'       => __( 'عرض پنل', 'nafas-chatbot-pro' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => get_option( 'ncp_panel_width', '380px' ),
			'description' => 'px, rem, % — e.g. 380px',
		] );
		$this->add_control( 'panel_height', [
			'label'       => __( 'ارتفاع پنل', 'nafas-chatbot-pro' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => get_option( 'ncp_panel_height', '600px' ),
			'description' => 'px, vh — e.g. 600px',
		] );

		$this->end_controls_section();

		/* ── Colors ── */
		$this->start_controls_section( 'sec_colors', [
			'label' => __( '🎨 رنگ‌ها', 'nafas-chatbot-pro' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		foreach ( [
			'theme_primary'       => [ __( 'رنگ اصلی', 'nafas-chatbot-pro' ),         '#b01618' ],
			'theme_primary_hover' => [ __( 'رنگ اصلی (hover)', 'nafas-chatbot-pro' ),  '#8c0f11' ],
			'theme_bg_base'       => [ __( 'پس‌زمینه اصلی', 'nafas-chatbot-pro' ),     '#f7f5f2' ],
			'theme_bg_card'       => [ __( 'پس‌زمینه کارت', 'nafas-chatbot-pro' ),     '#ffffff' ],
			'theme_border'        => [ __( 'رنگ حاشیه', 'nafas-chatbot-pro' ),         '#e6e1da' ],
			'theme_text_base'     => [ __( 'رنگ متن اصلی', 'nafas-chatbot-pro' ),      '#1c1a18' ],
			'theme_text_muted'    => [ __( 'رنگ متن کم‌رنگ', 'nafas-chatbot-pro' ),    '#6a625a' ],
			'theme_control_bg'    => [ __( 'پس‌زمینه کنترل', 'nafas-chatbot-pro' ),    '#efe9e2' ],
			'theme_control_hover' => [ __( 'کنترل (hover)', 'nafas-chatbot-pro' ),     '#e4ddd6' ],
			'theme_control_text'  => [ __( 'متن کنترل', 'nafas-chatbot-pro' ),         '#3c352f' ],
		] as $key => [ $label, $default ] ) {
			$this->add_control( $key, [
				'label'   => $label,
				'type'    => Controls_Manager::COLOR,
				'default' => get_option( "ncp_{$key}", $default ),
			] );
		}

		$this->end_controls_section();

		/* ── Typography ── */
		$this->start_controls_section( 'sec_typo', [
			'label' => __( '🔤 تایپوگرافی', 'nafas-chatbot-pro' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'font_family', [
			'label'   => __( 'فونت', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => get_option( 'ncp_font_family', 'Vazirmatn, IRANSansX, Tahoma, sans-serif' ),
		] );
		$this->add_control( 'font_size_body', [
			'label'   => __( 'اندازه متن اصلی', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => get_option( 'ncp_font_size_body', '0.9375rem' ),
		] );
		$this->add_control( 'font_size_heading', [
			'label'   => __( 'اندازه عنوان', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => get_option( 'ncp_font_size_heading', '1.125rem' ),
		] );
		$this->add_control( 'font_size_caption', [
			'label'   => __( 'اندازه کپشن', 'nafas-chatbot-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => get_option( 'ncp_font_size_caption', '0.8125rem' ),
		] );

		$this->end_controls_section();
	}

	protected function render(): void {
		$plugin   = Nafas_Chatbot_Pro::instance();
		$s        = $this->get_settings_for_display();

		$overrides = [
			'company_name'        => $s['company_name'] ?? '',
			'company_id'          => $s['company_id'] ?? '',
			'products_json'       => $s['products_json'] ?? '',
			'provider'            => $s['provider'] ?? '',
			'model'               => $s['model'] ?? '',
			'system_prompt'       => $s['system_prompt'] ?? '',
			'temperature'         => isset( $s['temperature'] ) ? (float) $s['temperature'] : 0.7,
			'max_tokens'          => isset( $s['max_tokens'] ) ? (int) $s['max_tokens'] : 512,
			'history_length'      => isset( $s['history_length'] ) ? (int) $s['history_length'] : 6,
			'show_launcher'       => ( $s['show_launcher'] ?? '' ) === 'yes',
			'show_company'        => ( $s['show_company'] ?? '' ) === 'yes',
			'show_products'       => ( $s['show_products'] ?? '' ) === 'yes',
			'show_adr'            => ( $s['show_adr'] ?? '' ) === 'yes',
			'show_consult'        => ( $s['show_consult'] ?? '' ) === 'yes',
			'floating_mode'       => ( $s['floating_mode'] ?? 'yes' ) === 'yes',
			'open_by_default'     => ( $s['open_by_default'] ?? '' ) === 'yes',
			'launcher_position'   => $s['launcher_position'] ?? 'right',
			'text_direction'      => $s['text_direction'] ?? 'rtl',
			'theme_mode'          => $s['theme_mode'] ?? 'auto',
			'persist_history'     => ( $s['persist_history'] ?? 'yes' ) === 'yes',
			'enable_markdown'     => ( $s['enable_markdown'] ?? 'yes' ) === 'yes',
			'enable_emoji'        => ( $s['enable_emoji'] ?? 'yes' ) === 'yes',
			'enable_notifications'=> ( $s['enable_notifications'] ?? 'yes' ) === 'yes',
			'panel_width'         => $s['panel_width'] ?? '',
			'panel_height'        => $s['panel_height'] ?? '',
			'font_family'         => $s['font_family'] ?? '',
			'font_size_body'      => $s['font_size_body'] ?? '',
			'font_size_heading'   => $s['font_size_heading'] ?? '',
			'font_size_caption'   => $s['font_size_caption'] ?? '',
			'theme_primary'       => $s['theme_primary'] ?? '',
			'theme_primary_hover' => $s['theme_primary_hover'] ?? '',
			'theme_bg_base'       => $s['theme_bg_base'] ?? '',
			'theme_bg_card'       => $s['theme_bg_card'] ?? '',
			'theme_border'        => $s['theme_border'] ?? '',
			'theme_text_base'     => $s['theme_text_base'] ?? '',
			'theme_text_muted'    => $s['theme_text_muted'] ?? '',
			'theme_control_bg'    => $s['theme_control_bg'] ?? '',
			'theme_control_hover' => $s['theme_control_hover'] ?? '',
			'theme_control_text'  => $s['theme_control_text'] ?? '',
		];

		$config = $plugin->build_config( $overrides );
		$plugin->enqueue_frontend_assets( $config );
		echo $plugin->render_mount( $config );
	}
}
