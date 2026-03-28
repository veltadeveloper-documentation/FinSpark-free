<?php
/**
 * Elementor widget bridge for FinSpark calculators.
 *
 * @package finance-calculators
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
    return;
}

class FC_Elementor_Calculator_Widget extends \Elementor\Widget_Base {

    /**
     * Calculator definition.
     *
     * @var array<string, mixed>
     */
    private $calculator = array();

    /**
     * Constructor.
     *
     * @param array<string, mixed> $data       Widget data.
     * @param array<string, mixed>|null $args  Widget arguments.
     * @param array<string, mixed> $calculator Calculator configuration.
     */
    public function __construct( $data = array(), $args = null, $calculator = array() ) {
        $this->calculator = is_array( $calculator ) ? $calculator : array();
        parent::__construct( $data, $args );
    }

    public function get_name() {
        $key = isset( $this->calculator['key'] ) ? sanitize_key( $this->calculator['key'] ) : 'calculator';

        return 'finspark-' . $key;
    }

    public function get_title() {
        return isset( $this->calculator['widget_title'] ) ? $this->calculator['widget_title'] : esc_html__( 'FinSpark Calculator', 'finance-calculators' );
    }

    public function get_icon() {
        return 'eicon-calculator';
    }

    public function get_categories() {
        return array( 'finspark' );
    }

    public function get_keywords() {
        return isset( $this->calculator['keywords'] ) && is_array( $this->calculator['keywords'] ) ? $this->calculator['keywords'] : array( 'finspark', 'finance', 'calculator' );
    }

    public function get_style_depends() {
        return array( 'fc-style' );
    }

    public function get_script_depends() {
        return array( 'fc-main' );
    }

    protected function register_controls() {
        $shortcode = isset( $this->calculator['shortcode'] ) ? $this->calculator['shortcode'] : '';

        $this->start_controls_section(
            'section_finspark_info',
            array(
                'label' => esc_html__( 'Widget Info', 'finance-calculators' ),
            )
        );

        $this->add_control(
            'finspark_shortcode_notice',
            array(
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw'  => wp_kses(
                    sprintf(
                        /* translators: %s: shortcode tag. */
                        __( 'This widget renders the same calculator as the <code>[%s]</code> shortcode.', 'finance-calculators' ),
                        esc_html( $shortcode )
                    ),
                    array(
                        'code' => array(),
                    )
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $key    = isset( $this->calculator['key'] ) ? sanitize_key( $this->calculator['key'] ) : '';
        $plugin = function_exists( 'fc_get_plugin_instance' ) ? fc_get_plugin_instance() : null;

        if ( empty( $key ) || ! $plugin || ! method_exists( $plugin, 'render_calculator' ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is rendered from trusted plugin templates.
        echo $plugin->render_calculator( $key );
    }
}
