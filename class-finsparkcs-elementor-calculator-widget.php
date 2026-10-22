<?php
/**
 * Elementor widget bridge for FinSpark calculators.
 *
 * @package FinSparkCalculatorSuite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
    return;
}

class FinSparkCS_Elementor_Calculator_Widget extends \Elementor\Widget_Base {

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

    /**
     * Resolve the saved Elementor widget type.
     *
     * @return string
     */
    private function get_saved_widget_type() {
        if ( method_exists( $this, 'get_data' ) ) {
            $widget_type = $this->get_data( 'widgetType' );

            if ( is_string( $widget_type ) ) {
                return $widget_type;
            }
        }

        return '';
    }

    /**
     * Resolve the calculator key for this widget instance.
     *
     * @return string
     */
    private function get_calculator_key() {
        if ( ! empty( $this->calculator['key'] ) ) {
            return sanitize_key( $this->calculator['key'] );
        }

        $widget_type = $this->get_saved_widget_type();

        if ( 0 === strpos( $widget_type, 'finsparkcs-' ) ) {
            return sanitize_key( substr( $widget_type, strlen( 'finsparkcs-' ) ) );
        }

        return '';
    }

    /**
     * Resolve the calculator definition from the plugin registry.
     *
     * @return array<string, mixed>
     */
    private function get_calculator_definition() {
        $key = $this->get_calculator_key();

        if ( ! empty( $this->calculator ) && ! empty( $this->calculator['key'] ) ) {
            return $this->calculator;
        }

        $plugin = function_exists( 'finsparkcs_get_plugin_instance' ) ? finsparkcs_get_plugin_instance() : null;

        if ( empty( $key ) || ! $plugin || ! method_exists( $plugin, 'get_calculator_definitions' ) ) {
            return $this->calculator;
        }

        $definitions = $plugin->get_calculator_definitions();

        if ( empty( $definitions[ $key ] ) || ! is_array( $definitions[ $key ] ) ) {
            return $this->calculator;
        }

        $definitions[ $key ]['key'] = $key;

        return $definitions[ $key ];
    }

    public function get_name() {
        $key = $this->get_calculator_key();

        return 'finsparkcs-' . ( $key ? $key : 'calculator' );
    }

    public function get_title() {
        $calculator = $this->get_calculator_definition();

        return isset( $calculator['widget_title'] ) ? $calculator['widget_title'] : esc_html__( 'FinSpark Calculator', 'finspark-calculator-suite' );
    }

    public function get_icon() {
        return 'eicon-calculator';
    }

    public function get_categories() {
        return array( 'finsparkcs' );
    }

    public function get_keywords() {
        $calculator = $this->get_calculator_definition();

        return isset( $calculator['keywords'] ) && is_array( $calculator['keywords'] ) ? $calculator['keywords'] : array( 'finspark', 'finance', 'calculator' );
    }

    public function get_style_depends() {
        return array( 'finsparkcs-style' );
    }

    public function get_script_depends() {
        return array( 'finsparkcs-main' );
    }

    protected function register_controls() {
        $calculator = $this->get_calculator_definition();
        $shortcode  = isset( $calculator['shortcode'] ) ? $calculator['shortcode'] : '';

        $this->start_controls_section(
            'section_finsparkcs_info',
            array(
                'label' => esc_html__( 'Widget Info', 'finspark-calculator-suite' ),
            )
        );

        $this->add_control(
            'finsparkcs_shortcode_notice',
            array(
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw'  => wp_kses(
                    sprintf(
                        /* translators: %s: shortcode tag. */
                        __( 'This widget renders the same calculator as the <code>[%s]</code> shortcode.', 'finspark-calculator-suite' ),
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
        $calculator = $this->get_calculator_definition();
        $key        = isset( $calculator['key'] ) ? sanitize_key( $calculator['key'] ) : '';
        $plugin = function_exists( 'finsparkcs_get_plugin_instance' ) ? finsparkcs_get_plugin_instance() : null;

        if ( empty( $key ) || ! $plugin || ! method_exists( $plugin, 'render_calculator' ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is rendered from trusted plugin templates.
        echo $plugin->render_calculator( $key );
    }
}
