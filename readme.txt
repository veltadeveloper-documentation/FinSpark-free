=== FinSpark: Calculator Suite ===
Contributors: veltadeveloper
Donate link: https://github.com/veltadeveloper-documentation
Tags: finance, calculators, loan, investment, mortgage
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A collection of modern financial calculators with shortcodes, Elementor widgets, CSV export files, and bundled local Chart.js assets.

== Description ==

FinSpark: Calculator Suite provides a set of useful financial calculators that can be embedded in posts and pages using shortcodes or native Elementor widgets.
Chart.js 4.5.1 is bundled locally inside the plugin, so no CDN or manual asset installation is required for charts to render.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings → FinSpark to configure options (currency, decimals, chart theme).
4. Add any calculator to a post, page, or Elementor layout using the shortcodes or widgets below.

== Elementor Widgets ==

If Elementor is active, every calculator is available as its own widget inside the `FinSpark` category in the Elementor panel.

== Shortcodes ==
[finsparkcs_investment]      — Investment Return Calculator
[finsparkcs_position_size]   — Trading Position Size Calculator
[finsparkcs_loan]            — Loan / Mortgage Calculator
[finsparkcs_compound]        — Compound Interest Calculator
[finsparkcs_savings_goal]    — Savings Goal Calculator
[finsparkcs_sip]             — SIP Calculator
[finsparkcs_debt_payoff]     — Debt Payoff Calculator
[finsparkcs_rent_vs_buy]     — Rent vs Buy Calculator
[finsparkcs_inflation]       — Inflation Calculator
[finsparkcs_car_loan]        — Car Loan Calculator
[finsparkcs_credit_card]     — Credit Card Payoff Calculator

== Frequently Asked Questions ==

= Why are charts not showing? =
FinSpark bundles Chart.js locally and does not load remote CDN assets. If charts do not appear, reinstall the packaged plugin to restore the bundled vendor files; calculators still work and show text/table results even if chart rendering fails.

= Why are CSV export buttons not visible? =
CSV export appears only for amortization-producing calculators after running the calculation (Loan, Debt, Car, Credit Card).

== Changelog ==
= 2.0.0 =
* Added Elementor widgets for every calculator.
* Rebranded the plugin as FinSpark: Calculator Suite.
* Uses plugin-specific shortcode names and keeps CSV export support.

== Upgrade Notice ==
= 2.0.0 =
Major release. Please check settings after upgrading.

