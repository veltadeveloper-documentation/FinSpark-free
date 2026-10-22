<?php
/*
Plugin Name: FinSpark: Calculator Suite
Plugin URI: https://github.com/veltadeveloper-documentation/finance-calculators_documentation/blob/main/README.md
Description: FinSpark provides investment, loan, savings, debt payoff, and other financial calculators with shortcodes and Elementor widgets.
Version: 2.0.0
Requires at least: 5.0
Requires PHP: 7.4
Author: Velta Developer
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: finspark-calculator-suite
*/

/*
 * Bundled vendor library notice:
 * FinSpark includes Chart.js 4.5.1.
 * Copyright (c) Chart.js Contributors.
 * Licensed under the MIT License.
 * See assets/js/vendor/LICENSE-chartjs.txt.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'FINSPARKCS_PLUGIN_FILE' ) ) {
    define( 'FINSPARKCS_PLUGIN_FILE', __FILE__ );
    define( 'FINSPARKCS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'FINSPARKCS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    define( 'FINSPARKCS_PLUGIN_VERSION', '2.0.0' );
    define( 'FINSPARKCS_CHARTJS_VERSION', '4.5.1' );
    define( 'FINSPARKCS_BRAND_NAME', 'FinSpark' );
    define( 'FINSPARKCS_PLUGIN_LABEL', 'FinSpark: Calculator Suite' );
}

class FinSparkCS_Calculators {

    private $option_name = 'finsparkcs_settings';

    public function __construct() {
        register_activation_hook( FINSPARKCS_PLUGIN_FILE, array( $this, 'on_activate' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
        add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
        add_action( 'elementor/editor/before_enqueue_styles', array( $this, 'register_styles' ) );
        add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'register_scripts' ) );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );
        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );
    }

    public function on_activate() {
        wp_mkdir_p( FINSPARKCS_PLUGIN_DIR . 'assets/css/' );
        wp_mkdir_p( FINSPARKCS_PLUGIN_DIR . 'assets/js/' );
        wp_mkdir_p( FINSPARKCS_PLUGIN_DIR . 'assets/js/vendor/' );
        if ( false === get_option( $this->option_name ) ) {
            add_option( $this->option_name, $this->default_settings() );
        }
    }

    private function default_settings() {
        return array(
            'enabled' => array(
                'investment'    => 1,
                'position_size' => 1,
                'loan'          => 1,
                'compound'      => 1,
                'savings'       => 1,
                'sip'           => 1,
                'debt'          => 1,
                'rentbuy'       => 1,
                'inflation'     => 1,
                'car'           => 1,
                'creditcard'    => 1,
            ),
            'currency_symbol'   => '$',
            'currency_position' => 'before',
            'decimals'          => 2,
            'locale'            => get_locale(),
            'chart_theme'       => 'light',
        );
    }

    private function get_chartjs_path() {
        return FINSPARKCS_PLUGIN_DIR . 'assets/js/vendor/chart.umd.min.js';
    }

    private function get_chartjs_url() {
        return FINSPARKCS_PLUGIN_URL . 'assets/js/vendor/chart.umd.min.js';
    }

    private function has_local_chart_library() {
        return file_exists( $this->get_chartjs_path() );
    }

    public function get_calculator_definitions() {
        return array(
            'investment'    => array(
                'shortcode'    => 'finsparkcs_investment',
                'method'       => 'sc_investment',
                'label'        => esc_html__( 'Investment Return', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Investment Return Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'investment', 'returns' ),
            ),
            'position_size' => array(
                'shortcode'    => 'finsparkcs_position_size',
                'method'       => 'sc_position_size',
                'label'        => esc_html__( 'Trading Position Size', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Trading Position Size Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'trading', 'risk' ),
            ),
            'loan'          => array(
                'shortcode'    => 'finsparkcs_loan',
                'method'       => 'sc_loan',
                'label'        => esc_html__( 'Loan / Mortgage', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Loan / Mortgage Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'loan', 'mortgage' ),
            ),
            'compound'      => array(
                'shortcode'    => 'finsparkcs_compound',
                'method'       => 'sc_compound',
                'label'        => esc_html__( 'Compound Interest', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Compound Interest Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'compound', 'interest' ),
            ),
            'savings'       => array(
                'shortcode'    => 'finsparkcs_savings_goal',
                'method'       => 'sc_savings',
                'label'        => esc_html__( 'Savings Goal', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Savings Goal Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'savings', 'goal' ),
            ),
            'sip'           => array(
                'shortcode'    => 'finsparkcs_sip',
                'method'       => 'sc_sip',
                'label'        => esc_html__( 'SIP (Systematic Investment Plan)', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'SIP (Systematic Investment Plan) Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'sip', 'investment' ),
            ),
            'debt'          => array(
                'shortcode'    => 'finsparkcs_debt_payoff',
                'method'       => 'sc_debt',
                'label'        => esc_html__( 'Debt Payoff (Snowball / Avalanche)', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Debt Payoff / Snowball Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'debt', 'payoff' ),
            ),
            'rentbuy'       => array(
                'shortcode'    => 'finsparkcs_rent_vs_buy',
                'method'       => 'sc_rentbuy',
                'label'        => esc_html__( 'Rent vs Buy', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Rent vs Buy Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'rent', 'buy' ),
            ),
            'inflation'     => array(
                'shortcode'    => 'finsparkcs_inflation',
                'method'       => 'sc_inflation',
                'label'        => esc_html__( 'Inflation Calculator', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Inflation Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'inflation', 'value' ),
            ),
            'car'           => array(
                'shortcode'    => 'finsparkcs_car_loan',
                'method'       => 'sc_car',
                'label'        => esc_html__( 'Car Loan / Auto Lease', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Car Loan / Auto Lease Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'car', 'loan' ),
            ),
            'creditcard'    => array(
                'shortcode'    => 'finsparkcs_credit_card',
                'method'       => 'sc_creditcard',
                'label'        => esc_html__( 'Credit Card Payoff', 'finspark-calculator-suite' ),
                'widget_title' => esc_html__( 'Credit Card Payoff Calculator', 'finspark-calculator-suite' ),
                'keywords'     => array( 'finspark', 'finance', 'credit', 'card' ),
            ),
        );
    }

    public function render_calculator( $key, $atts = array() ) {
        $definitions = $this->get_calculator_definitions();

        if ( empty( $definitions[ $key ]['method'] ) ) {
            return '';
        }

        $method = $definitions[ $key ]['method'];

        if ( ! method_exists( $this, $method ) ) {
            return '';
        }

        return $this->$method( $atts );
    }

    private function get_frontend_script_settings() {
        $settings = get_option( $this->option_name, $this->default_settings() );

        return array(
            'currency_symbol'   => sanitize_text_field( $settings['currency_symbol'] ?? '$' ),
            'currency_position' => in_array( $settings['currency_position'] ?? 'before', array( 'before', 'after' ), true ) ? $settings['currency_position'] : 'before',
            'decimals'          => intval( $settings['decimals'] ?? 2 ),
            'locale'            => sanitize_text_field( $settings['locale'] ?? get_locale() ),
            'chart_theme'       => in_array( $settings['chart_theme'] ?? 'light', array( 'light', 'dark' ), true ) ? $settings['chart_theme'] : 'light',
            'export_csv_label'  => esc_html__( 'Export CSV', 'finspark-calculator-suite' ),
        );
    }

    public function register_styles() {
        if ( wp_style_is( 'finsparkcs-style', 'registered' ) ) {
            return;
        }

        $css_path = FINSPARKCS_PLUGIN_DIR . 'assets/css/finance-calculators.css';
        $css_url  = FINSPARKCS_PLUGIN_URL . 'assets/css/finance-calculators.css';

        if ( file_exists( $css_path ) ) {
            wp_register_style( 'finsparkcs-style', $css_url, array(), filemtime( $css_path ) );
        } else {
            wp_register_style( 'finsparkcs-style', false, array(), FINSPARKCS_PLUGIN_VERSION );
            wp_add_inline_style( 'finsparkcs-style', '.fc-calculator{padding:12px;border:1px solid #eee;margin:12px 0;} .fc-chart-wrap{height:300px;}' );
        }
    }

    public function register_scripts() {
        if ( ! wp_script_is( 'finsparkcs-chartjs', 'registered' ) ) {
            if ( $this->has_local_chart_library() ) {
                wp_register_script( 'finsparkcs-chartjs', $this->get_chartjs_url(), array(), FINSPARKCS_CHARTJS_VERSION, true );
            } else {
                /*
                 * Keep the dependency handle registered so calculator JS still loads
                 * and can fall back to text/table results if the bundled asset is
                 * unexpectedly missing from an installation.
                 */
                wp_register_script( 'finsparkcs-chartjs', false, array(), FINSPARKCS_CHARTJS_VERSION, true );
            }
        }

        if ( wp_script_is( 'finsparkcs-main', 'registered' ) ) {
            return;
        }

        $js_path = FINSPARKCS_PLUGIN_DIR . 'assets/js/finance-calculators.js';
        $js_url  = FINSPARKCS_PLUGIN_URL . 'assets/js/finance-calculators.js';

        if ( file_exists( $js_path ) ) {
            wp_register_script( 'finsparkcs-main', $js_url, array( 'finsparkcs-chartjs' ), filemtime( $js_path ), true );
            wp_localize_script( 'finsparkcs-main', 'finsparkcsSettings', $this->get_frontend_script_settings() );
        }
    }

    public function register_assets() {
        $this->register_styles();
        $this->register_scripts();
    }

    public function enqueue_assets() {
        $this->register_assets();

        if ( wp_style_is( 'finsparkcs-style', 'registered' ) ) {
            wp_enqueue_style( 'finsparkcs-style' );
        }

        if ( wp_script_is( 'finsparkcs-main', 'registered' ) ) {
            wp_enqueue_script( 'finsparkcs-main' );
        }
    }

    public function admin_menu() {
        add_options_page( esc_html( FINSPARKCS_PLUGIN_LABEL ), esc_html( FINSPARKCS_BRAND_NAME ), 'manage_options', 'finsparkcs_settings', array( $this, 'settings_page' ) );
    }

    public function register_settings() {
        register_setting( 'finsparkcs_settings_group', $this->option_name, array( $this, 'sanitize_settings' ) );

        add_settings_section( 'finsparkcs_section_main', esc_html__( 'Calculator Settings', 'finspark-calculator-suite' ), null, 'finsparkcs_settings' );

        add_settings_field( 'finsparkcs_enabled', esc_html__( 'Enable Calculators', 'finspark-calculator-suite' ), array( $this, 'field_enabled' ), 'finsparkcs_settings', 'finsparkcs_section_main' );
        add_settings_field( 'finsparkcs_currency_symbol', esc_html__( 'Currency Symbol', 'finspark-calculator-suite' ), array( $this, 'field_currency_symbol' ), 'finsparkcs_settings', 'finsparkcs_section_main' );
        add_settings_field( 'finsparkcs_currency_position', esc_html__( 'Currency Position', 'finspark-calculator-suite' ), array( $this, 'field_currency_position' ), 'finsparkcs_settings', 'finsparkcs_section_main' );
        add_settings_field( 'finsparkcs_decimals', esc_html__( 'Decimals', 'finspark-calculator-suite' ), array( $this, 'field_decimals' ), 'finsparkcs_settings', 'finsparkcs_section_main' );
        add_settings_field( 'finsparkcs_locale', esc_html__( 'Locale (optional)', 'finspark-calculator-suite' ), array( $this, 'field_locale' ), 'finsparkcs_settings', 'finsparkcs_section_main' );
        add_settings_field( 'finsparkcs_chart_theme', esc_html__( 'Chart.js Theme', 'finspark-calculator-suite' ), array( $this, 'field_chart_theme' ), 'finsparkcs_settings', 'finsparkcs_section_main' );
    }

    public function sanitize_settings( $input ) {
        $defaults = $this->default_settings();
        $out      = $defaults;

        // enabled calculators
        $enabled = array();
        if ( ! empty( $input['enabled'] ) && is_array( $input['enabled'] ) ) {
            foreach ( $input['enabled'] as $k => $v ) {
                $key = sanitize_text_field( $v );
                if ( array_key_exists( $key, $defaults['enabled'] ) ) {
                    $enabled[ $key ] = 1;
                }
            }
        }
        foreach ( $defaults['enabled'] as $k => $v ) {
            $out['enabled'][ $k ] = ! empty( $enabled[ $k ] ) ? 1 : 0;
        }

        $out['currency_symbol']   = sanitize_text_field( $input['currency_symbol'] ?? $defaults['currency_symbol'] );
        $out['currency_position'] = in_array( $input['currency_position'] ?? 'before', array( 'before', 'after' ), true ) ? $input['currency_position'] : $defaults['currency_position'];
        $out['decimals']          = max( 0, min( 6, intval( $input['decimals'] ?? $defaults['decimals'] ) ) );
        $out['locale']            = sanitize_text_field( $input['locale'] ?? $defaults['locale'] );
        $out['chart_theme']       = in_array( $input['chart_theme'] ?? 'light', array( 'light', 'dark' ), true ) ? $input['chart_theme'] : $defaults['chart_theme'];

        return $out;
    }

    public function field_enabled() {
        $opts    = get_option( $this->option_name, $this->default_settings() );
        $enabled = $opts['enabled'] ?? $this->default_settings()['enabled'];

        foreach ( $this->get_calculator_definitions() as $key => $calculator ) {
            printf(
                '<label style="display:block;"><input type="checkbox" name="%1$s[enabled][]" value="%2$s" %3$s> %4$s</label>',
                esc_attr( $this->option_name ),
                esc_attr( $key ),
                checked( ! empty( $enabled[ $key ] ), true, false ),
                esc_html( $calculator['label'] )
            );
        }
    }

    public function field_currency_symbol() {
        $opts = get_option( $this->option_name, $this->default_settings() );
        printf(
            '<input name="%1$s[currency_symbol]" type="text" value="%2$s" maxlength="5" size="5">',
            esc_attr( $this->option_name ),
            esc_attr( $opts['currency_symbol'] ?? '$' )
        );
    }

    public function field_currency_position() {
        $opts = get_option( $this->option_name, $this->default_settings() );
        $pos  = $opts['currency_position'] ?? 'before';
        printf(
            '<select name="%1$s[currency_position]"><option value="before" %2$s>Before amount</option><option value="after" %3$s>After amount</option></select>',
            esc_attr( $this->option_name ),
            selected( $pos, 'before', false ),
            selected( $pos, 'after', false )
        );
    }

    public function field_decimals() {
        $opts = get_option( $this->option_name, $this->default_settings() );
        printf(
            '<input name="%1$s[decimals]" type="number" min="0" max="6" value="%2$s">',
            esc_attr( $this->option_name ),
            intval( $opts['decimals'] ?? 2 )
        );
    }

    public function field_locale() {
        $opts = get_option( $this->option_name, $this->default_settings() );
        printf(
            '<input name="%1$s[locale]" type="text" value="%2$s" class="regular-text"><p class="description">Optional (e.g. en-US). If empty, WP locale is used.</p>',
            esc_attr( $this->option_name ),
            esc_attr( $opts['locale'] ?? get_locale() )
        );
    }

    public function field_chart_theme() {
        $opts  = get_option( $this->option_name, $this->default_settings() );
        $theme = $opts['chart_theme'] ?? 'light';
        printf(
            '<select name="%1$s[chart_theme]"><option value="light" %2$s>Light</option><option value="dark" %3$s>Dark</option></select>',
            esc_attr( $this->option_name ),
            selected( $theme, 'light', false ),
            selected( $theme, 'dark', false )
        );
    }

    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( FINSPARKCS_PLUGIN_LABEL . ' Settings' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'finsparkcs_settings_group' );
                do_settings_sections( 'finsparkcs_settings' );
                submit_button();
                ?>
            </form>
            <hr/>
            <h2><?php echo esc_html__( 'Bundled Assets', 'finspark-calculator-suite' ); ?></h2>
            <p><?php echo esc_html__( 'FinSpark uses bundled local assets only. Core calculators and chart rendering rely on the files below.', 'finspark-calculator-suite' ); ?></p>
            <ul>
                <li><code>assets/css/finance-calculators.css</code></li>
                <li><code>assets/js/finance-calculators.js</code></li>
                <li><code>assets/js/vendor/chart.umd.min.js</code> <?php echo esc_html__( '(bundled local Chart.js runtime used on the frontend)', 'finspark-calculator-suite' ); ?></li>
                <li><code>assets/js/vendor/chart.js</code> <?php echo esc_html__( '(bundled readable Chart.js module reference copy for review)', 'finspark-calculator-suite' ); ?></li>
                <li><code>assets/js/vendor/LICENSE-chartjs.txt</code> <?php echo esc_html__( '(Chart.js MIT license and attribution)', 'finspark-calculator-suite' ); ?></li>
            </ul>
            <p><?php echo esc_html__( 'No CDN or manual asset setup is required. If any required CSS or JS file is missing, reinstall the packaged plugin or reactivate it to restore the bundled assets.', 'finspark-calculator-suite' ); ?></p>
            <?php if ( ! $this->has_local_chart_library() ) : ?>
                <p><strong><?php echo esc_html__( 'Chart.js bundle missing from this installation.', 'finspark-calculator-suite' ); ?></strong> <?php echo esc_html__( 'Calculators will continue to show text and table results, but chart visualizations stay disabled until the packaged vendor asset is restored.', 'finspark-calculator-suite' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function register_shortcodes() {
        foreach ( $this->get_calculator_definitions() as $calculator ) {
            add_shortcode( $calculator['shortcode'], array( $this, $calculator['method'] ) );
        }
    }

    public function register_elementor_category( $elements_manager ) {
        if ( ! class_exists( '\Elementor\Elements_Manager' ) || ! method_exists( $elements_manager, 'add_category' ) ) {
            return;
        }

        $elements_manager->add_category(
            'finsparkcs',
            array(
                'title' => esc_html( FINSPARKCS_BRAND_NAME ),
                'icon'  => 'fa fa-plug',
            )
        );
    }

    public function register_elementor_widgets( $widgets_manager ) {
        if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
            return;
        }

        $this->register_assets();

        if ( ! class_exists( 'FinSparkCS_Elementor_Calculator_Widget' ) ) {
            require_once FINSPARKCS_PLUGIN_DIR . 'includes/class-finsparkcs-elementor-calculator-widget.php';
        }

        if ( ! class_exists( 'FinSparkCS_Elementor_Calculator_Widget' ) ) {
            return;
        }

        foreach ( $this->get_calculator_definitions() as $key => $calculator ) {
            $calculator['key'] = $key;
            $widget = new FinSparkCS_Elementor_Calculator_Widget( array(), null, $calculator );

            if ( method_exists( $widgets_manager, 'register' ) ) {
                $widgets_manager->register( $widget );
            } elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
                $widgets_manager->register_widget_type( $widget );
            }
        }
    }

    private function is_enabled( $key ) {
        $opts = get_option( $this->option_name, $this->default_settings() );
        return ! empty( $opts['enabled'][ $key ] );
    }

    /* Shortcodes: render markup only (calculations handled in JS) */

    public function sc_investment( $atts = array() ) {
        if ( ! $this->is_enabled( 'investment' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Investment calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_inv_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-investment" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>" data-autorun="1">
  <h3><?php echo esc_html__( 'Investment Return Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Initial investment (one-time)', 'finspark-calculator-suite' ); ?>
        <input name="initial" type="number" step="0.01" value="1000" />
      </label>
      <label><?php echo esc_html__( 'Monthly contribution', 'finspark-calculator-suite' ); ?>
        <input name="monthly" type="number" step="0.01" value="100" />
      </label>
      <label><?php echo esc_html__( 'Annual return rate (%)', 'finspark-calculator-suite' ); ?>
        <input name="rate" type="number" step="0.01" value="7" />
      </label>
      <label><?php echo esc_html__( 'Years', 'finspark-calculator-suite' ); ?>
        <input name="years" type="number" step="1" value="10" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Calculate', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_position_size( $atts = array() ) {
        if ( ! $this->is_enabled( 'position_size' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Position Size calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_pos_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-position-size" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>">
  <h3><?php echo esc_html__( 'Trading Position Size Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Account size', 'finspark-calculator-suite' ); ?>
        <input name="account" type="number" step="0.01" value="10000" />
      </label>
      <label><?php echo esc_html__( 'Risk per trade (%) — OR fill Risk amount below', 'finspark-calculator-suite' ); ?>
        <input name="risk_pct" type="number" step="0.01" value="1" />
      </label>
      <label><?php echo esc_html__( 'Risk amount (overrides % if set)', 'finspark-calculator-suite' ); ?>
        <input name="risk_amount" type="number" step="0.01" value="" />
      </label>
      <label><?php echo esc_html__( 'Entry price', 'finspark-calculator-suite' ); ?>
        <input name="entry" type="number" step="0.0001" value="10" />
      </label>
      <label><?php echo esc_html__( 'Stop loss price', 'finspark-calculator-suite' ); ?>
        <input name="stop" type="number" step="0.0001" value="9" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Calculate', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_loan( $atts = array() ) {
        if ( ! $this->is_enabled( 'loan' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Loan calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_loan_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-loan" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>">
  <h3><?php echo esc_html__( 'Loan / Mortgage Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Loan amount', 'finspark-calculator-suite' ); ?>
        <input name="principal" type="number" step="0.01" value="200000" />
      </label>
      <label><?php echo esc_html__( 'Annual interest rate (%)', 'finspark-calculator-suite' ); ?>
        <input name="rate" type="number" step="0.01" value="3.5" />
      </label>
      <label><?php echo esc_html__( 'Term (years)', 'finspark-calculator-suite' ); ?>
        <input name="years" type="number" step="1" value="30" />
      </label>
      <label><?php echo esc_html__( 'Payments per year', 'finspark-calculator-suite' ); ?>
        <input name="payments_per_year" type="number" step="1" value="12" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Calculate', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_compound( $atts = array() ) {
        if ( ! $this->is_enabled( 'compound' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Compound calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_comp_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-compound" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>" data-autorun="1">
  <h3><?php echo esc_html__( 'Compound Interest Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Principal', 'finspark-calculator-suite' ); ?>
        <input name="principal" type="number" step="0.01" value="1000" />
      </label>
      <label><?php echo esc_html__( 'Annual rate (%)', 'finspark-calculator-suite' ); ?>
        <input name="rate" type="number" step="0.01" value="5" />
      </label>
      <label><?php echo esc_html__( 'Compounding frequency', 'finspark-calculator-suite' ); ?>
        <select name="freq">
          <option value="1"><?php echo esc_html__( 'Annually', 'finspark-calculator-suite' ); ?></option>
          <option value="2"><?php echo esc_html__( 'Semiannually', 'finspark-calculator-suite' ); ?></option>
          <option value="4"><?php echo esc_html__( 'Quarterly', 'finspark-calculator-suite' ); ?></option>
          <option value="12" selected><?php echo esc_html__( 'Monthly', 'finspark-calculator-suite' ); ?></option>
          <option value="365"><?php echo esc_html__( 'Daily', 'finspark-calculator-suite' ); ?></option>
        </select>
      </label>
      <label><?php echo esc_html__( 'Years', 'finspark-calculator-suite' ); ?>
        <input name="years" type="number" step="1" value="10" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Calculate', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_savings( $atts = array() ) {
        if ( ! $this->is_enabled( 'savings' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Savings Goal calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_sav_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-savings" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>" data-autorun="1">
  <h3><?php echo esc_html__( 'Savings Goal Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Target amount', 'finspark-calculator-suite' ); ?>
        <input name="target" type="number" step="0.01" value="50000" />
      </label>
      <label><?php echo esc_html__( 'Years', 'finspark-calculator-suite' ); ?>
        <input name="years" type="number" step="1" value="10" />
      </label>
      <label><?php echo esc_html__( 'Annual return rate (%)', 'finspark-calculator-suite' ); ?>
        <input name="rate" type="number" step="0.01" value="6" />
      </label>
      <label><?php echo esc_html__( 'Current savings (optional)', 'finspark-calculator-suite' ); ?>
        <input name="current" type="number" step="0.01" value="2000" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Calculate', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_sip( $atts = array() ) {
        if ( ! $this->is_enabled( 'sip' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'SIP calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_sip_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-sip" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>" data-autorun="1">
  <h3><?php echo esc_html__( 'SIP (Systematic Investment Plan) Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Monthly investment', 'finspark-calculator-suite' ); ?>
        <input name="monthly" type="number" step="0.01" value="1000" />
      </label>
      <label><?php echo esc_html__( 'Expected annual return (%)', 'finspark-calculator-suite' ); ?>
        <input name="rate" type="number" step="0.01" value="8" />
      </label>
      <label><?php echo esc_html__( 'Duration (years)', 'finspark-calculator-suite' ); ?>
        <input name="years" type="number" step="1" value="10" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Calculate', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_debt( $atts = array() ) {
        if ( ! $this->is_enabled( 'debt' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Debt Payoff calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_debt_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-debt" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>" data-autorun="0">
  <h3><?php echo esc_html__( 'Debt Payoff / Snowball Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Monthly budget for debt payments', 'finspark-calculator-suite' ); ?>
        <input name="monthly_budget" type="number" step="0.01" value="800" />
      </label>
      <label><?php echo esc_html__( 'Method', 'finspark-calculator-suite' ); ?>
        <select name="method">
          <option value="snowball"><?php echo esc_html__( 'Snowball (smallest balance)', 'finspark-calculator-suite' ); ?></option>
          <option value="avalanche"><?php echo esc_html__( 'Avalanche (highest rate)', 'finspark-calculator-suite' ); ?></option>
        </select>
      </label>
      <div style="grid-column:1 / -1; font-size:.9rem; color:var(--fc-muted);"><?php echo esc_html__( 'Add each debt below with balance, APR and minimum payment. Click + Add Debt to add more.', 'finspark-calculator-suite' ); ?></div>
    </div>
    <div class="fc-debt-list"></div>
    <div style="display:flex; gap:8px; margin-top:10px;">
      <button class="fc-add-debt" type="button">+ <?php echo esc_html__( 'Add Debt', 'finspark-calculator-suite' ); ?></button>
      <button class="fc-submit" type="submit"><?php echo esc_html__( 'Simulate Payoff', 'finspark-calculator-suite' ); ?></button>
    </div>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_rentbuy( $atts = array() ) {
        if ( ! $this->is_enabled( 'rentbuy' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Rent vs Buy calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_rent_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-rentbuy" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>" data-autorun="0">
  <h3><?php echo esc_html__( 'Rent vs Buy Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Home price', 'finspark-calculator-suite' ); ?>
        <input name="home_price" type="number" step="0.01" value="300000" />
      </label>
      <label><?php echo esc_html__( 'Down payment (%)', 'finspark-calculator-suite' ); ?>
        <input name="down_payment_pct" type="number" step="0.01" value="20" />
      </label>
      <label><?php echo esc_html__( 'Mortgage rate (annual %)', 'finspark-calculator-suite' ); ?>
        <input name="mortgage_rate" type="number" step="0.01" value="4.0" />
      </label>
      <label><?php echo esc_html__( 'Mortgage term (years)', 'finspark-calculator-suite' ); ?>
        <input name="mortgage_term" type="number" step="1" value="30" />
      </label>
      <label><?php echo esc_html__( 'Holding years (compare)', 'finspark-calculator-suite' ); ?>
        <input name="holding_years" type="number" step="1" value="5" />
      </label>
      <label><?php echo esc_html__( 'Monthly rent (current)', 'finspark-calculator-suite' ); ?>
        <input name="rent_month" type="number" step="0.01" value="1500" />
      </label>
      <label><?php echo esc_html__( 'Expected appreciation (%/yr)', 'finspark-calculator-suite' ); ?>
        <input name="appreciation" type="number" step="0.01" value="3" />
      </label>
      <label><?php echo esc_html__( 'Property tax rate (% of price/yr)', 'finspark-calculator-suite' ); ?>
        <input name="property_tax_rate" type="number" step="0.01" value="1" />
      </label>
      <label><?php echo esc_html__( 'Maintenance (% of price/yr)', 'finspark-calculator-suite' ); ?>
        <input name="maintenance_rate" type="number" step="0.01" value="1" />
      </label>
      <label><?php echo esc_html__( 'Selling cost (%)', 'finspark-calculator-suite' ); ?>
        <input name="selling_cost_pct" type="number" step="0.01" value="6" />
      </label>
      <label><?php echo esc_html__( 'Rent inflation (%/yr)', 'finspark-calculator-suite' ); ?>
        <input name="rent_inflation" type="number" step="0.01" value="2" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Compare', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_inflation( $atts = array() ) {
        if ( ! $this->is_enabled( 'inflation' ) ) { return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Inflation calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : ''; }
        $uid = uniqid( 'finsparkcs_infl_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-inflation" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>" data-autorun="0">
  <h3><?php echo esc_html__( 'Inflation Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Amount', 'finspark-calculator-suite' ); ?>
        <input name="amount" type="number" step="0.01" value="1000" />
      </label>
      <label><?php echo esc_html__( 'From year', 'finspark-calculator-suite' ); ?>
        <input name="from_year" type="number" step="1" value="<?php echo esc_attr( gmdate( 'Y' ) ); ?>" />
      </label>
      <label><?php echo esc_html__( 'To year', 'finspark-calculator-suite' ); ?>
        <input name="to_year" type="number" step="1" value="<?php echo esc_attr( gmdate( 'Y' ) ); ?>" />
      </label>
      <label><?php echo esc_html__( 'Average inflation rate (%)', 'finspark-calculator-suite' ); ?>
        <input name="inflation_rate" type="number" step="0.01" value="2" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Calculate', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_car( $atts = array() ) {
        if ( ! $this->is_enabled( 'car' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Car Loan calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_car_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-car" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>" data-autorun="0">
  <h3><?php echo esc_html__( 'Car Loan / Auto Lease Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Vehicle price', 'finspark-calculator-suite' ); ?>
        <input name="price" type="number" step="0.01" value="35000" />
      </label>
      <label><?php echo esc_html__( 'Down payment', 'finspark-calculator-suite' ); ?>
        <input name="down" type="number" step="0.01" value="5000" />
      </label>
      <label><?php echo esc_html__( 'Annual rate (%)', 'finspark-calculator-suite' ); ?>
        <input name="rate" type="number" step="0.01" value="6.5" />
      </label>
      <label><?php echo esc_html__( 'Term (years)', 'finspark-calculator-suite' ); ?>
        <input name="term_years" type="number" step="1" value="5" />
      </label>
      <label><?php echo esc_html__( 'Term (months) — optional, overrides years', 'finspark-calculator-suite' ); ?>
        <input name="term" type="number" step="1" value="" />
      </label>
      <label><?php echo esc_html__( 'Residual / Balloon value (end)', 'finspark-calculator-suite' ); ?>
        <input name="residual" type="number" step="0.01" value="5000" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Calculate', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }

    public function sc_creditcard( $atts = array() ) {
        if ( ! $this->is_enabled( 'creditcard' ) ) {
            return current_user_can( 'manage_options' ) ? '<div class="fc-calculator"><em>' . esc_html__( 'Credit Card Payoff calculator is disabled in plugin settings.', 'finspark-calculator-suite' ) . '</em></div>' : '';
        }
        $uid = uniqid( 'finsparkcs_cc_' );
        ob_start();
        ?>
<div id="<?php echo esc_attr( $uid ); ?>" class="fc-calculator fc-cc" data-chartid="<?php echo esc_attr( $uid . '_chart' ); ?>" data-autorun="0">
  <h3><?php echo esc_html__( 'Credit Card Payoff Calculator', 'finspark-calculator-suite' ); ?></h3>
  <form class="fc-form">
    <div class="fc-grid">
      <label><?php echo esc_html__( 'Current balance', 'finspark-calculator-suite' ); ?>
        <input name="balance" type="number" step="0.01" value="5000" />
      </label>
      <label><?php echo esc_html__( 'Annual interest rate (%)', 'finspark-calculator-suite' ); ?>
        <input name="rate" type="number" step="0.01" value="19.99" />
      </label>
      <label><?php echo esc_html__( 'Monthly payment (amount you’ll pay)', 'finspark-calculator-suite' ); ?>
        <input name="payment" type="number" step="0.01" value="150" />
      </label>
    </div>
    <button class="fc-submit" type="submit"><?php echo esc_html__( 'Simulate', 'finspark-calculator-suite' ); ?></button>
  </form>
  <div class="fc-results"></div>
</div>
        <?php
        return ob_get_clean();
    }
} // end class

function finsparkcs_get_plugin_instance() {
    static $plugin = null;

    if ( null === $plugin ) {
        $plugin = new FinSparkCS_Calculators();
    }

    return $plugin;
}

finsparkcs_get_plugin_instance();

/* ============================================================================
 * Add "Settings" link to Installed Plugins page (escaped and matching settings slug)
 * ============================================================================ */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'finsparkcs_add_settings_link' );
function finsparkcs_add_settings_link( $links ) {
    $url           = admin_url( 'options-general.php?page=finsparkcs_settings' );
    $settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'finspark-calculator-suite' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

/* EOF */
