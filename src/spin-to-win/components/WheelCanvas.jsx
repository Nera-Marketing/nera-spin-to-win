import React, { useEffect, useRef } from 'react';
import { Wheel } from 'spin-wheel';
import { readThemeColors } from '../utils/themeColors.js';
import styles from './WheelCanvas.module.css';

export default function WheelCanvas({ items, spinRequest, onSpinEnd }) {
  const hostRef = useRef(null);
  const wheelRef = useRef(null);

  useEffect(() => {
    if (!hostRef.current || items.length === 0) {
      return undefined;
    }

    const theme = readThemeColors();

    const wheel = new Wheel(hostRef.current, {
      items,
      isInteractive: false,
      // Radial labels: text runs along each slice's radius (rim → center), scales to any segment count.
      itemLabelAlign: 'right',
      itemLabelRadius: 0.85,
      itemLabelRadiusMax: 0.25,
      itemLabelFontSizeMax: 14,
      itemLabelRotation: 0,
      itemLabelFont: theme.fontHeading,
      itemLabelStrokeWidth: 2,
      itemLabelStrokeColor: 'rgba(0,0,0,0.42)',
      rotation: 0,
      pointerAngle: 0,
      borderWidth: 6,
      borderColor: theme.wheelRim,
      lineWidth: 2,
      lineColor: 'rgba(255,255,255,0.38)',
      pixelRatio: 0,
    });

    wheel.resize();
    wheelRef.current = wheel;

    const onResize = () => {
      if (wheelRef.current && typeof wheelRef.current.resize === 'function') {
        wheelRef.current.resize();
      }
    };

    window.addEventListener('resize', onResize);

    return () => {
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
