import React from 'react';
import { cx } from '../utils/cx.js';
import styles from './HistoryList.module.css';

export default function HistoryList({
  rows,
  title,
  emptyMessage,
  remainingSpins,
  spinsLeftLabel,
}) {
  return (
    <aside className={styles.aside}>
      <div className={styles.header}>
        <div className={styles.headerLeft}>
          <span className={styles.icon} aria-hidden>
            <svg className={styles.iconSvg} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </span>
          <h2 className={styles.title}>
            {title}
          </h2>
        </div>
        <span
          className={styles.badge}
          aria-label={`${remainingSpins} ${spinsLeftLabel}`}
        >
          {remainingSpins} {spinsLeftLabel}
        </span>
      </div>
      <ul className={cx(styles.list, 'stw-history-scroll', 'stw-side-scrollbar')}>
        {rows.length === 0 ? (
          <li className={styles.empty}>
            {emptyMessage}
          </li>
        ) : (
          rows.map((row, index) => (
            <li
              key={`${row.prize_label}-${row.created_at}-${index}`}
              className={styles.row}
              style={{ animationDelay: `${Math.min(index, 8) * 45}ms` }}
            >
              <span className={styles.rowLabel}>{row.prize_label}</span>
              <span className={styles.rowDate}>
                {row.created_at}
              </span>
            </li>
          ))
        )}
      </ul>
    </aside>
  );
}
