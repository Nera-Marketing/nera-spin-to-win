import React from 'react';

export default function SpinControls({
  strings,
  turbo,
  spinning,
  onTurboSpin,
  onFullSpin,
  onViewPrizes,
}) {
  return (
    <div className="w-full max-w-md">
      <div
        className="mx-auto mb-4 h-px w-24 max-w-full bg-gradient-to-r from-transparent via-amber-400/50 to-transparent"
        aria-hidden
      />
      <div className="flex flex-wrap items-center justify-center gap-3">
        <button
          type="button"
          className={`inline-flex min-h-[48px] cursor-help items-center justify-center rounded-2xl bg-gradient-to-b from-[#fff7ed] to-[#ffedd5] px-5 font-bold text-[#c0172e] shadow-[inset_0_1px_0_rgba(255,255,255,0.85),0_4px_0_0_#b45309,0_12px_28px_-8px_rgba(217,119,6,0.35)] transition-[transform,box-shadow] hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.85),0_3px_0_0_#b45309,0_14px_32px_-6px_rgba(217,119,6,0.32)] active:translate-y-0.5 active:shadow-[inset_0_1px_0_rgba(255,255,255,0.7),0_2px_0_0_#b45309,0_8px_22px_-8px_rgba(217,119,6,0.28)] disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none ${
            turbo
              ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-white'
              : 'ring-1 ring-amber-400/25'
          }`}
          title={strings.tooltipTurbo || ''}
          aria-label={[strings.turbo || 'Turbo mode', strings.tooltipTurbo]
            .filter(Boolean)
            .join(' — ')}
          disabled={spinning}
          onClick={onTurboSpin}
        >
          {strings.turbo || 'Turbo mode'}
        </button>
        <button
          type="button"
          className="inline-flex min-h-[48px] cursor-help items-center justify-center rounded-2xl bg-gradient-to-b from-[#d41f35] to-[#9f1239] px-8 font-bold text-white shadow-[0_4px_0_0_#6b0f1c,0_12px_32px_-8px_rgba(192,23,46,0.55)] transition-[transform,box-shadow] hover:shadow-[0_3px_0_0_#6b0f1c,0_14px_36px_-6px_rgba(192,23,46,0.5)] active:translate-y-0.5 active:shadow-[0_2px_0_0_#6b0f1c,0_8px_24px_-8px_rgba(192,23,46,0.45)] disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
          title={strings.tooltipSpin || ''}
          aria-label={[strings.spinNow || 'Spin now', strings.tooltipSpin]
            .filter(Boolean)
            .join(' — ')}
          disabled={spinning}
          onClick={onFullSpin}
        >
          {strings.spinNow || 'Spin now'}
        </button>
      </div>
      <div className="mt-3 flex justify-center xl:hidden">
        <button
          type="button"
          className="inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-semibold text-[#c0172e]/70 transition-colors hover:text-[#c0172e] active:scale-95"
          title={strings.tooltipViewAllPrizes || ''}
          aria-label={[strings.viewAllPrizes || 'View all prizes', strings.tooltipViewAllPrizes]
            .filter(Boolean)
            .join(' — ')}
          onClick={onViewPrizes}
        >
          <svg className="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
            <path d="M12 2l2.09 6.26L20 9.27l-5.45 3.97L16.18 20 12 16.77 7.82 20 9.45 13.24 4 9.27l5.91-.01L12 2z" />
          </svg>
          {strings.viewAllPrizes || 'View all prizes'}
        </button>
      </div>
    </div>
  );
}
