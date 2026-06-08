<?php
/**
 * Spin To Win screen — plugin-bundled fallback template.
 *
 * Override this template by placing page-templates/spin-to-win.php in your theme.
 *
 * @package Nera_Spin_To_Win
 */

defined( 'ABSPATH' ) || exit;

$product_id = absint( get_query_var( 'nera_spin_product' ) );
$product    = $product_id ? wc_get_product( $product_id ) : null;

if (
	! $product ||
	! function_exists( 'lty_is_lottery_product' ) ||
	! lty_is_lottery_product( $product )
) {
	status_header( 404 );
	nocache_headers();
	get_header();
	echo '<div class="nera-stw-wrap" style="padding:5rem 1rem"><p>' .
		esc_html__( 'Competition not found.', 'nera-spin-to-win' ) .
		'</p></div>';
	get_footer();
	exit;
}

if ( ! class_exists( 'Nera_STW_Product_Meta' ) || ! Nera_STW_Product_Meta::is_enabled( $product_id ) ) {
	status_header( 404 );
	nocache_headers();
	get_header();
	echo '<div class="nera-stw-wrap" style="padding:5rem 1rem"><p>' .
		esc_html__( 'Spin To Win is not available for this competition.', 'nera-spin-to-win' ) .
		'</p></div>';
	get_footer();
	exit;
}

$hero_heading = $product->get_name();
$hero_badge   = Nera_STW_ACF_Copy_Settings::get_hero_badge();
$hero_intro   = Nera_STW_ACF_Copy_Settings::get_hero_intro();
$override     = Nera_STW_ACF_Copy_Settings::get_hero_heading_override();
if ( '' !== $override ) {
	$hero_heading = $override;
}
get_header();
?>

<div class="stw-page">
  <div class="stw-page-bg" aria-hidden="true"></div>
  <div class="stw-page-blob-right" aria-hidden="true"></div>
  <div class="stw-page-blob-left" aria-hidden="true"></div>

  <div class="stw-hero-container">
    <header class="stw-hero-header">
      <p class="stw-hero-badge">
        <span class="stw-hero-badge-dot" aria-hidden="true"></span>
        <?php echo esc_html( $hero_badge ); ?>
      </p>
      <h1 class="stw-hero-heading">
        <?php echo esc_html( $hero_heading ); ?>
      </h1>
      <div class="stw-hero-divider" aria-hidden="true"></div>
      <p class="stw-hero-intro">
        <?php echo esc_html( $hero_intro ); ?>
      </p>
    </header>
    <?php if ( ! is_user_logged_in() ) : ?>
      <div class="stw-login-card">
        <svg class="stw-login-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
        </svg>
        <p class="stw-login-text"><?php esc_html_e(
          'Log in to use your spins from ticket purchases.',
          'nera-spin-to-win',
        ); ?></p>
        <a
          class="stw-login-btn"
          href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"
        ><?php esc_html_e( 'My account', 'nera-spin-to-win' ); ?></a>
      </div>
    <?php else : ?>
      <div
        id="nera-spin-root"
        class="nera-spin-to-win stw-root-wrap"
      >
        <div class="stw-root-blob-right" aria-hidden="true"></div>
        <div class="stw-root-blob-left" aria-hidden="true"></div>
        <div class="stw-loading-placeholder">
          <?php esc_html_e( 'Loading wheel…', 'nera-spin-to-win' ); ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
get_footer();
