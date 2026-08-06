import React, { useId } from 'react';
import styles from './WheelChrome.module.css';

/**
 * Fixed DOM overlay: rim lights, center cap, pointer — does not rotate with the canvas wheel.
 */
export default function WheelChrome({
  segmentCount = 10,
  rim = '#f59e0b',
  bulb = '#fbbf24',
  bulbMid = '#fde68a',
  bulbHot = '#fffde7',
  bulbSpecular = '#ffffff',
  pointer = '#dc2626',
}) {
  const n = Math.max(2, segmentCount);
  const lightCount = n * 2;
  const pointerGradId = useId().replace(/:/g, '');

  return (
    <>
      <svg
        className={styles.svg}
        viewBox="0 0 100 100"
        aria-hidden
      >
        <circle
          cx="50"
          cy="50"
          r="47.5"
          fill="none"
          stroke={rim}
          strokeWidth="1.2"
        />
        {Array.from({ length: lightCount }, (_, i) => {
          if (i === 0) {
            return null;
          }
          const a = (i / lightCount) * 2 * Math.PI - Math.PI / 2;
          const r = 46.5;
          const x = 50 + r * Math.cos(a);
          const y = 50 + r * Math.sin(a);
          const delay = `${(i * 180) % 2700}ms`;
          return (
            <g key={i}>
              <circle cx={x} cy={y} r={4.64} fill={rim} opacity="0.22" />
              <circle
                cx={x}
                cy={y}
                r={3.04}
                fill={bulb}
                style={{
                  animation: `nera-bulb-pulse 2.7s ease-in-out ${delay} infinite`,
                  opacity: 0.55,
                }}
              />
              <circle cx={x} cy={y} r={1.76} fill={bulbMid} />
              <circle
                cx={x}
                cy={y}
                r={1.0}
                fill={bulbHot}
                opacity="0.9"
                style={{
                  animation: `nera-bulb-pulse 2.7s ease-in-out ${delay} infinite`,
                }}
              />
              <circle cx={x - 0.52} cy={y - 0.52} r={0.34} fill={bulbSpecular} opacity="0.75" />
            </g>
          );
        })}
      </svg>

      <div className={styles.cap} aria-hidden>
        <div className={styles.capGlow} aria-hidden />
        <span className={styles.capLabel}>Spin</span>
      </div>

      <div className={styles.pointer} aria-hidden>
        <svg
          width="44"
          height="48"
          viewBox="0 0 44 48"
          className={styles.pointerSvg}
          role="presentation"
        >
          <defs>
            <linearGradient id={pointerGradId} x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stopColor={pointer} />
              <stop offset="55%" stopColor={pointer} />
              <stop offset="100%" stopColor={pointer} />
            </linearGradient>
          </defs>
          {/* Tip at bottom points toward wheel center; base along the top */}
          <path
            d="M4 6 L40 6 L22 42 Z"
            fill={`url(#${pointerGradId})`}
            stroke={rim}
            strokeWidth="1.25"
            strokeLinejoin="round"
          />
        </svg>
      </div>
    </>
  );
}
