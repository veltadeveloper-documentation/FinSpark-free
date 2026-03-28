=== FinSpark: Calculator Suite ===
Contributors: veltadeveloper-documentation
Donate link: https://github.com/veltadeveloper-documentation
Tags: finance, calculators, loan, investment, mortgage, savings, debt
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A collection of modern financial calculators with shortcodes, Elementor widgets, and CSV export files.

== Description ==

FinSpark: Calculator Suite provides a set of useful financial calculators that can be embedded in posts and pages using shortcodes or native Elementor widgets.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings → FinSpark to configure options (currency, decimals, chart theme).
4. Add any calculator to a post, page, or Elementor layout using the shortcodes or widgets below.

== Elementor Widgets ==

If Elementor is active, every calculator is available as its own widget inside the `FinSpark` category in the Elementor panel.

== Shortcodes ==
[fc_investment]      — Investment Return Calculator
[fc_position_size]   — Trading Position Size Calculator
[fc_loan]            — Loan / Mortgage Calculator
[fc_compound]        — Compound Interest Calculator
[fc_savings_goal]    — Savings Goal Calculator
[fc_sip]             — SIP Calculator
[fc_debt_payoff]     — Debt Payoff Calculator
[fc_rent_vs_buy]     — Rent vs Buy Calculator
[fc_inflation]       — Inflation Calculator
[fc_car_loan]        — Car Loan Calculator
[fc_credit_card]     — Credit Card Payoff Calculator

== Frequently Asked Questions ==

= Why are charts not showing? =
FinSpark does not load remote CDN assets. Bundle a local copy of Chart.js at `assets/js/vendor/chart.umd.min.js` to enable charts; the calculators will still work without it.

= Why are CSV export buttons not visible? =
CSV export appears only for amortization-producing calculators after running the calculation (Loan, Debt, Car, Credit Card).

== Changelog ==
= 2.0.0 =
* Added Elementor widgets for every calculator.
* Rebranded the plugin as FinSpark: Calculator Suite.
* Kept calculator output compatible with existing shortcodes and CSV export.

== Upgrade Notice ==
= 2.0.0 =
Major release. Please check settings after upgrading.

