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

<div
  class="relative overflow-hidden bg-gradient-to-b from-secondary via-white to-background-light/80"
>
  <div
    class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(192,23,46,0.12),transparent)]"
    aria-hidden="true"
  ></div>
  <div
    class="pointer-events-none absolute right-0 top-1/4 h-[min(60vw,28rem)] w-[min(60vw,28rem)] translate-x-1/3 rounded-full bg-amber-400/10 blur-3xl"
    aria-hidden="true"
  ></div>
  <div
    class="pointer-events-none absolute bottom-0 left-0 h-48 w-48 -translate-x-1/4 translate-y-1/4 rounded-full bg-primary/5 blur-3xl"
    aria-hidden="true"
  ></div>

  <div class="container relative mx-auto px-4 py-10 lg:py-14">
    <header class="mb-8 text-center lg:mb-10">
      <p
        class="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-400/35 bg-gradient-to-r from-[#c0172e]/[0.07] via-amber-50/90 to-[#c0172e]/[0.07] px-4 py-1.5 text-xs font-bold uppercase tracking-[0.2em] text-[#c0172e] shadow-[0_4px_24px_-8px_rgba(192,23,46,0.25)]"
      >
        <span
          class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,0.85)]"
          aria-hidden="true"
        ></span>
        <?php echo esc_html( $hero_badge ); ?>
      </p>
      <h1
        class="font-heading text-3xl font-extrabold tracking-tight text-text-primary [text-wrap:balance] sm:text-4xl lg:text-[2.5rem] lg:leading-tight"
      >
        <?php echo esc_html( $hero_heading ); ?>
      </h1>
      <div
        class="mx-auto mt-5 h-1 w-28 rounded-full bg-gradient-to-r from-transparent via-[#e8950a] to-transparent opacity-90"
        aria-hidden="true"
      ></div>
      <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-text-secondary">
        <?php echo esc_html( $hero_intro ); ?>
      </p>
    </header>
    <?php if ( ! is_user_logged_in() ) : ?>
      <div
        class="mx-auto max-w-xl rounded-3xl border border-gray-100 bg-white/95 p-8 text-center shadow-[0_24px_80px_-24px_rgba(192,23,46,0.2)] ring-1 ring-black/[0.04] backdrop-blur-sm"
      >
        <svg class="mx-auto mb-3 h-10 w-10 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
        </svg>
        <p class="mb-4 font-semibold text-text-primary"><?php esc_html_e(
          'Log in to use your spins from ticket purchases.',
          'nera-spin-to-win',
        ); ?></p>
        <a
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 font-semibold text-white shadow-[0_12px_32px_-12px_rgba(19,19,236,0.45)] transition-[transform,opacity] hover:opacity-95 active:scale-[0.98]"
          href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"
        ><?php esc_html_e( 'My account', 'nera-spin-to-win' ); ?></a>
      </div>
    <?php else : ?>
      <div
        id="nera-spin-root"
        class="nera-spin-to-win relative rounded-[1.75rem] border border-amber-400/20 bg-gradient-to-br from-white via-secondary/80 to-amber-50/30 p-5 shadow-[0_32px_90px_-28px_rgba(192,23,46,0.18)] ring-1 ring-black/[0.05] sm:p-7 lg:p-8"
      >
        <div
          class="pointer-events-none absolute -right-16 top-0 h-56 w-56 rounded-full bg-[#c0172e]/[0.06] blur-3xl"
          aria-hidden="true"
        ></div>
        <div
          class="pointer-events-none absolute -bottom-12 -left-10 h-48 w-48 rounded-full bg-amber-400/15 blur-3xl"
          aria-hidden="true"
        ></div>
        <div
          class="min-h-full flex items-center justify-center py-24 text-sm text-text-secondary"
        >
          <?php esc_html_e( 'Loading wheel\u2026', 'nera-spin-to-win' ); ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
get_footer();
