import React from 'react';
import { cx } from '../utils/cx.js';
import styles from './SpinControls.module.css';

export default function SpinControls({
  strings,
  turbo,
  spinning,
  onTurboSpin,
  onFullSpin,
  onViewPrizes,
}) {
  return (
    <div className={styles.wrap}>
      <div className={styles.divider} aria-hidden />
      <div className={styles.buttons}>
        <button
          type="button"
          className={cx(
            styles.turboBtn,
            turbo ? styles.turboBtnActive : styles.turboBtnInactive,
          )}
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
          className={styles.spinBtn}
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
      <div className={styles.viewRow}>
        <button
          type="button"
          className={styles.viewBtn}
          title={strings.tooltipViewAllPrizes || ''}
          aria-label={[strings.viewAllPrizes || 'View all prizes', strings.tooltipViewAllPrizes]
            .filter(Boolean)
            .join(' — ')}
          onClick={onViewPrizes}
        >
          <svg className={styles.viewBtnIcon} viewBox="0 0 24 24" fill="currentColor" aria-hidden>
            <path d="M12 2l2.09 6.26L20 9.27l-5.45 3.97L16.18 20 12 16.77 7.82 20 9.45 13.24 4 9.27l5.91-.01L12 2z" />
          </svg>
          {strings.viewAllPrizes || 'View all prizes'}
        </button>
      </div>
    </div>
  );
}
