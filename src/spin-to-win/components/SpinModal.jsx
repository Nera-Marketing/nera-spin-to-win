import React, { useEffect, useRef, useState } from 'react';

const PHASE_HIDDEN = 'hidden';
const PHASE_ENTERING = 'entering';
const PHASE_ENTERED = 'entered';
const PHASE_EXITING = 'exiting';

export default function SpinModal({
  open,
  strings,
  title,
  body,
  variant = 'default',
  prizeItems = [],
  competitionsUrl = '/',
  onClose,
  onSpin,
  onTurbo,
}) {
  const [phase, setPhase] = useState(open ? PHASE_ENTERED : PHASE_HIDDEN);
  const phaseRef = useRef(phase);
  const enterSecondFrameRef = useRef(null);

  phaseRef.current = phase;

  useEffect(() => {
    if (!open) {
      return;
    }

    if (phaseRef.current === PHASE_EXITING) {
      setPhase(PHASE_ENTERED);
      return;
    }

    if (phaseRef.current !== PHASE_HIDDEN) {
      return;
    }

    setPhase(PHASE_ENTERING);

    const id1 = requestAnimationFrame(() => {
      enterSecondFrameRef.current = requestAnimationFrame(() => {
        enterSecondFrameRef.current = null;
        setPhase(PHASE_ENTERED);
      });
    });

    return () => {
      cancelAnimationFrame(id1);
      if (enterSecondFrameRef.current != null) {
        cancelAnimationFrame(enterSecondFrameRef.current);
        enterSecondFrameRef.current = null;
      }
    };
  }, [open]);

  useEffect(() => {
    if (open) {
      return;
    }
    setPhase((p) => {
      if (p === PHASE_ENTERED || p === PHASE_ENTERING) {
        return PHASE_EXITING;
      }
      return p;
    });
  }, [open]);

  useEffect(() => {
    if (phase === PHASE_HIDDEN) {
      return undefined;
    }

    const html = document.documentElement;
    const bodyEl = document.body;
    const prevHtmlOverflow = html.style.overflow;
    const prevBodyOverflow = bodyEl.style.overflow;

    html.style.overflow = 'hidden';
    bodyEl.style.overflow = 'hidden';

    return () => {
      html.style.overflow = prevHtmlOverflow;
      bodyEl.style.overflow = prevBodyOverflow;
    };
  }, [phase]);

  if (phase === PHASE_HIDDEN) {
    return null;
  }
  const isVisible = phase === PHASE_ENTERED;
  const isExiting = phase === PHASE_EXITING;

  return (
    <div
      id="nera-stw-modal"
      className={`fixed inset-0 z-[100] flex items-center justify-center overflow-hidden overscroll-contain bg-[#0d0d1b]/65 p-4 backdrop-blur-md transition-opacity duration-[240ms] ease-out motion-reduce:transition-none ${
        isVisible ? 'opacity-100' : 'opacity-0'
      } ${isExiting ? 'pointer-events-none' : ''}`}
      role="dialog"
      aria-modal="true"
      onClick={(event) => {
        if (event.target === event.currentTarget) {
          onClose();
        }
      }}
    >
      <div
        className={`relative w-full overflow-hidden rounded-3xl border-2 border-amber-400/25 bg-white shadow-[0_40px_100px_-30px_rgba(192,23,46,0.35)] transition-all duration-[240ms] ease-out motion-reduce:transition-none motion-reduce:transform-none ${
          isVisible
            ? 'opacity-100 scale-100 translate-y-0'
            : 'opacity-0 scale-[0.98] translate-y-1'
        } ${
          variant === 'prizes'
            ? 'max-w-2xl text-left'
            : variant === 'turbo-results'
              ? 'max-w-2xl text-center'
              : 'max-w-md text-center'
        }`}
        onTransitionEnd={(event) => {
          if (event.target !== event.currentTarget) {
            return;
          }
          if (phase === PHASE_EXITING && !open) {
            setPhase(PHASE_HIDDEN);
          }
        }}
      >
        <div
          className="h-1.5 w-full bg-gradient-to-r from-[#c0172e] via-amber-400 to-[#c0172e]"
          aria-hidden
        />
        <div className="relative p-6 pt-5">
          <button
            type="button"
            className="absolute right-4 top-4 z-[1] cursor-help rounded-xl p-1.5 text-text-secondary transition-colors hover:bg-secondary hover:text-text-primary"
            title={strings.tooltipClose || strings.close || 'Close'}
            aria-label={strings.tooltipClose || strings.close || 'Close'}
            onClick={onClose}
          >
            &times;
          </button>
          <h3 className={`mb-4 font-heading font-extrabold text-text-primary ${variant === 'turbo-results' ? 'text-3xl' : 'text-xl'}`}>{title}</h3>
          {body ? <p className="mt-2 text-sm leading-relaxed text-text-secondary">{body}</p> : null}
          {variant === 'prizes' ? (
            <ul className="mt-6 grid max-h-[min(60vh,520px)] grid-cols-2 gap-3 overflow-y-auto pr-1">
              {prizeItems.map((item, index) => (
                <li
                  key={`${item.label}-${index}`}
                  className="group flex overflow-hidden rounded-2xl border border-gray-100/90 bg-white shadow-[0_8px_24px_-12px_rgba(15,23,42,0.1)] ring-1 ring-black/[0.04] transition-[transform,box-shadow] hover:-translate-y-0.5 hover:shadow-[0_14px_36px_-14px_rgba(192,23,46,0.15)]"
                >
                  <span
                    className="w-2 shrink-0 self-stretch min-h-[4.5rem]"
                    style={{ backgroundColor: item.backgroundColor }}
                    aria-hidden
                  />
                  <div className="relative aspect-[4/3] min-h-[4.5rem] flex-1 overflow-hidden rounded-r-2xl bg-secondary">
                    {item.imageUrl ? (
                      <>
                        <img
                          src={item.imageUrl}
                          alt=""
                          className="absolute inset-0 !h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                          loading="lazy"
                        />
                        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-80" />
                        <span className="absolute bottom-2 left-2 max-w-[calc(100%-1rem)] rounded-lg bg-white/95 px-2 py-1 text-left text-[11px] font-bold leading-tight text-text-primary shadow-[0_2px_8px_rgba(0,0,0,0.1)] ring-1 ring-black/5 sm:bottom-3 sm:left-3 sm:max-w-[calc(100%-1.5rem)] sm:px-2.5 sm:text-xs">
                          {item.label}
                        </span>
                      </>
                    ) : (
                      <div className="flex h-full w-full items-center justify-center p-4 text-center text-xs font-semibold text-text-secondary">
                        {item.label}
                      </div>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          ) : variant === 'noSpins' ? (
            <div className="mt-6 flex flex-wrap justify-center gap-3">
              <a
                href={competitionsUrl}
                className="inline-flex min-h-[44px] cursor-pointer items-center justify-center rounded-2xl bg-gradient-to-b from-[#d41f35] to-[#9f1239] px-8 font-bold leading-none text-white shadow-[0_4px_0_0_#6b0f1c,0_12px_32px_-8px_rgba(192,23,46,0.5)] transition-[transform,opacity] hover:opacity-95 active:translate-y-0.5"
                title={strings.tooltipCompetitions || ''}
                aria-label={[strings.competitions || 'Competitions', strings.tooltipCompetitions]
                  .filter(Boolean)
                  .join(' — ')}
              >
                {strings.competitions || 'Competitions'}
              </a>
            </div>
          ) : variant === 'turbo-confirm' ? (
            <div className="mt-6 flex flex-wrap justify-center gap-3">
              <button
                type="button"
                className="inline-flex min-h-[44px] cursor-pointer items-center justify-center rounded-2xl bg-gradient-to-b from-[#fff7ed] to-[#ffedd5] px-5 font-bold leading-none text-[#c0172e] shadow-[inset_0_1px_0_rgba(255,255,255,0.85),0_4px_0_0_#b45309,0_12px_28px_-8px_rgba(217,119,6,0.35)] ring-1 ring-amber-400/25 transition-[transform,box-shadow] hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.85),0_3px_0_0_#b45309] active:translate-y-0.5"
                onClick={onClose}
              >
                {strings.cancel || 'Cancel'}
              </button>
              <button
                type="button"
                className="inline-flex min-h-[44px] cursor-pointer items-center justify-center rounded-2xl bg-gradient-to-b from-[#d41f35] to-[#9f1239] px-6 font-bold leading-none text-white shadow-[0_4px_0_0_#6b0f1c,0_12px_32px_-8px_rgba(192,23,46,0.5)] transition-[transform,opacity] hover:opacity-95 active:translate-y-0.5"
                onClick={onTurbo}
              >
                {strings.turboConfirm || 'Confirm'}
              </button>
            </div>
          ) : variant === 'turbo-results' ? (
            <ul className="mt-6 max-h-[min(70vh,560px)] divide-y divide-gray-100 overflow-y-auto text-left">
              {(prizeItems || []).map((item, index) => (
                <li key={`${item.label}-${index}`} className="flex items-center gap-4 py-4 pr-4">
                  <span
                    className="h-3.5 w-3.5 shrink-0 rounded-full"
                    style={{ backgroundColor: item.backgroundColor || '#c0172e' }}
                    aria-hidden
                  />
                  <span className="flex-1 text-base font-semibold text-text-primary">{item.label}</span>
                  {item.kind === 'wallet' && item.amount ? (
                    <span className="text-base font-bold text-success">{item.amount}</span>
                  ) : null}
                  {item.kind === 'no_win' ? (
                    <span className="text-sm text-text-secondary">{strings.tryAgain || 'No win'}</span>
                  ) : null}
                </li>
              ))}
            </ul>
          ) : (
            <div className="mt-6 flex flex-wrap justify-center gap-3">
              <button
                type="button"
                className="inline-flex min-h-[44px] cursor-help items-center justify-center rounded-2xl bg-gradient-to-b from-[#fff7ed] to-[#ffedd5] px-4 font-bold leading-none text-[#c0172e] shadow-[inset_0_1px_0_rgba(255,255,255,0.85),0_4px_0_0_#b45309,0_12px_28px_-8px_rgba(217,119,6,0.35)] ring-1 ring-amber-400/25 transition-[transform,box-shadow] hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.85),0_3px_0_0_#b45309,0_14px_32px_-6px_rgba(217,119,6,0.32)] active:translate-y-0.5 active:shadow-[inset_0_1px_0_rgba(255,255,255,0.7),0_2px_0_0_#b45309,0_8px_22px_-8px_rgba(217,119,6,0.28)]"
                title={strings.tooltipTurbo || ''}
                aria-label={[strings.turbo || 'Turbo mode', strings.tooltipTurbo]
                  .filter(Boolean)
                  .join(' — ')}
                onClick={onTurbo}
              >
                {strings.turbo || 'Turbo mode'}
              </button>
              <button
                type="button"
                className="inline-flex min-h-[44px] cursor-help items-center justify-center rounded-2xl bg-gradient-to-b from-[#d41f35] to-[#9f1239] px-6 font-bold leading-none text-white shadow-[0_4px_0_0_#6b0f1c,0_12px_32px_-8px_rgba(192,23,46,0.5)] transition-[transform,opacity] hover:opacity-95 active:translate-y-0.5"
                title={strings.tooltipModalSpin || strings.tooltipSpin || ''}
                aria-label={[
                  strings.spinAgain || 'Spin now',
                  strings.tooltipModalSpin || strings.tooltipSpin,
                ]
                  .filter(Boolean)
                  .join(' — ')}
                onClick={onSpin}
              >
                {strings.spinAgain || 'Spin now'}
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
