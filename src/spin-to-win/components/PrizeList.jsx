import React from 'react';
import { cx } from '../utils/cx.js';
import styles from './PrizeList.module.css';

export default function PrizeList({ items, title }) {
  return (
    <aside className={styles.aside}>
      <div className={styles.header}>
        <span className={styles.icon} aria-hidden>
          <svg className={styles.iconSvg} viewBox="0 0 24 24" fill="currentColor" aria-hidden>
            <path d="M12 2l2.09 6.26L20 9.27l-5.45 3.97L16.18 20 12 16.77 7.82 20 9.45 13.24 4 9.27l5.91-.01L12 2z" />
          </svg>
        </span>
        <div>
          <h2 className={styles.title}>
            {title}
          </h2>
        </div>
      </div>
      <ul className={cx(styles.list, 'stw-prize-scroll', 'stw-side-scrollbar')}>
        {items.map((item, index) => (
          <li
            key={`${item.label}-${index}`}
            className={styles.card}
          >
            <span
              className={styles.stripe}
              style={{ backgroundColor: item.backgroundColor }}
              aria-hidden
            />
            <div className={styles.imageWrap}>
              {item.imageUrl ? (
                <>
                  <img
                    src={item.imageUrl}
                    alt=""
                    className={styles.img}
                    loading="lazy"
                  />
                  <div className={styles.gradient} />
                  <span className={styles.label}>
                    {item.label}
                  </span>
                </>
              ) : (
                <div className={styles.noImage}>
                  {item.label}
                </div>
              )}
            </div>
          </li>
        ))}
      </ul>
    </aside>
  );
}
