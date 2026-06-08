import React, { useEffect, useRef, useState } from 'react';
import { cx } from '../utils/cx.js';
import styles from './SpinModal.module.css';

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

  const panelVariantClass =
    variant === 'prizes'
      ? styles.panelPrizes
      : variant === 'turbo-results'
        ? styles.panelTurboResults
        : null;

  return (
    <div
      id="nera-stw-modal"
      className={cx(
        styles.overlay,
        isVisible ? styles.overlayVisible : styles.overlayHidden,
        isExiting && styles.overlayExiting,
      )}
      role="dialog"
      aria-modal="true"
      onClick={(event) => {
        if (event.target === event.currentTarget) {
          onClose();
        }
      }}
    >
      <div
        className={cx(
          styles.panel,
          isVisible ? styles.panelVisible : styles.panelHidden,
          panelVariantClass,
        )}
        onTransitionEnd={(event) => {
          if (event.target !== event.currentTarget) {
            return;
          }
          if (phase === PHASE_EXITING && !open) {
            setPhase(PHASE_HIDDEN);
          }
        }}
      >
        <div className={styles.stripe} aria-hidden />
        <div className={styles.body}>
          <button
            type="button"
            className={styles.closeBtn}
            title={strings.tooltipClose || strings.close || 'Close'}
            aria-label={strings.tooltipClose || strings.close || 'Close'}
            onClick={onClose}
          >
            &times;
          </button>
          <h3 className={cx(styles.heading, variant === 'turbo-results' && styles.headingLg)}>
            {title}
          </h3>
          {body ? <p className={styles.bodyText}>{body}</p> : null}
          {variant === 'prizes' ? (
            <ul className={styles.prizeGrid}>
              {prizeItems.map((item, index) => (
                <li
                  key={`${item.label}-${index}`}
                  className={styles.prizeCard}
                >
                  <span
                    className={styles.prizeStripe}
                    style={{ backgroundColor: item.backgroundColor }}
                    aria-hidden
                  />
                  <div className={styles.prizeImageWrap}>
                    {item.imageUrl ? (
                      <>
                        <img
                          src={item.imageUrl}
                          alt=""
                          className={styles.prizeImg}
                          loading="lazy"
                        />
                        <div className={styles.prizeGradient} />
                        <span className={styles.prizeLabel}>
                          {item.label}
                        </span>
                      </>
                    ) : (
                      <div className={styles.prizeNoImage}>
                        {item.label}
                      </div>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          ) : variant === 'noSpins' ? (
            <div className={styles.actionRow}>
              <a
                href={competitionsUrl}
                className={styles.btnPrimary}
                title={strings.tooltipCompetitions || ''}
                aria-label={[strings.competitions || 'Competitions', strings.tooltipCompetitions]
                  .filter(Boolean)
                  .join(' — ')}
              >
                {strings.competitions || 'Competitions'}
              </a>
            </div>
          ) : variant === 'turbo-confirm' ? (
            <div className={styles.actionRow}>
              <button
                type="button"
                className={styles.btnSecondary}
                onClick={onClose}
              >
                {strings.cancel || 'Cancel'}
              </button>
              <button
                type="button"
                className={styles.btnPrimary}
                onClick={onTurbo}
              >
                {strings.turboConfirm || 'Confirm'}
              </button>
            </div>
          ) : variant === 'turbo-results' ? (
            <ul className={styles.turboList}>
              {(prizeItems || []).map((item, index) => (
                <li key={`${item.label}-${index}`} className={styles.turboItem}>
                  <span
                    className={styles.turboDot}
                    style={{ backgroundColor: item.backgroundColor || '#c0172e' }}
                    aria-hidden
                  />
                  <span className={styles.turboLabel}>{item.label}</span>
                  {item.kind === 'wallet' && item.amount ? (
                    <span className={styles.turboAmount}>{item.amount}</span>
                  ) : null}
                  {item.kind === 'no_win' ? (
                    <span className={styles.turboNoWin}>{strings.tryAgain || 'No win'}</span>
                  ) : null}
                </li>
              ))}
            </ul>
          ) : (
            <div className={styles.actionRow}>
              <button
                type="button"
                className={styles.btnSecondary}
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
                className={styles.btnPrimary}
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
