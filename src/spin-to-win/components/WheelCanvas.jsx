import React, { useEffect, useRef } from 'react';
import { Wheel } from 'spin-wheel';
import { readThemeColors } from '../utils/themeColors.js';
import { resolveBoldWheelLabelFont } from '../utils/wheelLabelFont.js';
import styles from './WheelCanvas.module.css';

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
      // Ensure Poppins 700 (and dedicated face) are ready before first paint;
      // vite also prefixes ctx.font with 700 for true canvas bold.
      const itemLabelFont = await resolveBoldWheelLabelFont(theme.fontHeading);
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
        itemLabelFontSizeMax: 16,
        itemLabelRotation: 0,
        itemLabelFont,
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

      if (typeof document !== 'undefined' && document.fonts?.ready) {
        try {
          await document.fonts.ready;
        } catch {
          // ignore
        }
      }
      if (cancelled) {
        if (typeof wheel.remove === 'function') {
          wheel.remove();
        }
        return;
      }

      wheel.itemLabelFont = itemLabelFont;
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
