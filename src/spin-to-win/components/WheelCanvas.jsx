import React, { useEffect, useRef } from 'react';
import { Wheel } from 'spin-wheel';
import { readThemeColors } from '../utils/themeColors.js';
import styles from './WheelCanvas.module.css';

/**
 * Ensure the theme heading family’s 700 face is ready before the first canvas
 * paint. Actual bold weight is applied by the vite `spin-wheel-bold-labels`
 * transform (`ctx.font = '700 ' + size + 'px ' + family`).
 */
async function ensureHeadingBold(fontHeading) {
  if (typeof document === 'undefined' || !document.fonts?.load) {
    return;
  }
  const raw = String(fontHeading || '').trim();
  const first = (raw.split(',')[0] || 'Poppins').trim().replace(/^['"]|['"]$/g, '') || 'Poppins';
  try {
    await document.fonts.load(`700 48px "${first}"`);
    if (document.fonts.ready) {
      await document.fonts.ready;
    }
  } catch {
    // Proceed with system fallback if the face isn’t available yet.
  }
}

export default function WheelCanvas({ items, spinRequest, onSpinEnd }) {
  const hostRef = useRef(null);
  const wheelRef = useRef(null);

  useEffect(() => {
    if (!hostRef.current || items.length === 0) {
      return undefined;
    }

    let cancelled = false;
    const host = hostRef.current;

    const onResize = () => {
      if (wheelRef.current && typeof wheelRef.current.resize === 'function') {
        wheelRef.current.resize();
      }
    };

    (async () => {
      const theme = readThemeColors();
      await ensureHeadingBold(theme.fontHeading);
      if (cancelled || !hostRef.current) {
        return;
      }

      const wheel = new Wheel(host, {
        items,
        isInteractive: false,
        // Radial labels: text runs along each slice's radius (rim → center), scales to any segment count.
        itemLabelAlign: 'right',
        itemLabelRadius: 0.85,
        itemLabelRadiusMax: 0.25,
        itemLabelFontSizeMax: 18,
        itemLabelRotation: 0,
        itemLabelFont: theme.fontHeading,
        itemLabelStrokeWidth: 1,
        itemLabelStrokeColor: 'rgba(0,0,0,0.18)',
        rotation: 0,
        pointerAngle: 0,
        borderWidth: 6,
        borderColor: theme.wheelRim,
        lineWidth: 2,
        lineColor: 'rgba(255,255,255,0.38)',
        pixelRatio: 0,
      });

      if (cancelled) {
        if (typeof wheel.remove === 'function') {
          wheel.remove();
        }
        return;
      }

      wheel.resize();
      wheelRef.current = wheel;
      window.addEventListener('resize', onResize);
    })();

    return () => {
      cancelled = true;
      window.removeEventListener('resize', onResize);
      if (wheelRef.current && typeof wheelRef.current.remove === 'function') {
        wheelRef.current.remove();
      }
      wheelRef.current = null;
    };
  }, [items]);

  useEffect(() => {
    if (!spinRequest || !wheelRef.current) {
      return undefined;
    }

    const wheel = wheelRef.current;
    let active = true;

    wheel.onRest = () => {
      if (active) {
        onSpinEnd(spinRequest.id);
      }
    };

    wheel.spinToItem(
      spinRequest.index,
      spinRequest.duration,
      true,
      spinRequest.revolutions,
      1,
      null,
    );

    return () => {
      active = false;
      if (wheelRef.current) {
        wheelRef.current.onRest = null;
      }
    };
  }, [spinRequest, onSpinEnd]);

  return <div id="nera-stw-wheel" ref={hostRef} className={styles.host} />;
}
