import React, { useId } from 'react';

/**
 * Fixed DOM overlay: rim lights, center cap, pointer — does not rotate with the canvas wheel.
 */
export default function WheelChrome({ segmentCount = 10 }) {
  const n = Math.max(2, segmentCount);
  const lightCount = n * 2;
  const pointerGradId = useId().replace(/:/g, '');

  return (
    <>
      <svg
        className="pointer-events-none absolute inset-0 z-[15] h-full w-full"
        viewBox="0 0 100 100"
        aria-hidden
      >
        <circle
          cx="50"
          cy="50"
          r="47.5"
          fill="none"
          stroke="#f59e0b"
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
              <circle cx={x} cy={y} r={4.64} fill="#f59e0b" opacity="0.22" />
              <circle
                cx={x}
                cy={y}
                r={3.04}
                fill="#fbbf24"
                style={{
                  animation: `nera-bulb-pulse 2.7s ease-in-out ${delay} infinite`,
                  opacity: 0.55,
                }}
              />
              <circle cx={x} cy={y} r={1.76} fill="#fde68a" />
              <circle
                cx={x}
                cy={y}
                r={1.0}
                fill="#fffde7"
                opacity="0.9"
                style={{
                  animation: `nera-bulb-pulse 2.7s ease-in-out ${delay} infinite`,
                }}
              />
              <circle cx={x - 0.52} cy={y - 0.52} r={0.34} fill="white" opacity="0.75" />
            </g>
          );
        })}
      </svg>

      <div
        className="pointer-events-none absolute top-1/2 left-1/2 z-[18] flex h-[22%] min-h-[56px] w-[22%] min-w-[56px] -translate-x-1/2 -translate-y-1/2 items-center justify-center overflow-hidden rounded-full border-[3px] border-white/50 bg-gradient-to-br from-amber-300 via-yellow-400 to-amber-600 shadow-[0_10px_28px_-6px_rgba(0,0,0,0.5)] ring-1 ring-amber-900/20"
        aria-hidden
      >
        <div
          className="pointer-events-none absolute inset-[10%] rounded-full bg-[radial-gradient(circle_at_32%_28%,rgba(255,255,255,0.65)_0%,transparent_52%)]"
          aria-hidden
        />
        <span className="relative z-[1] select-none text-center text-[clamp(0.55rem,2.8vw,0.8rem)] font-bold uppercase leading-tight tracking-wide text-amber-950/90 drop-shadow-[0_1px_0_rgba(255,255,255,0.45)]">
          Spin
        </span>
      </div>

      <div
        className="pointer-events-none absolute top-0 left-1/2 z-[25] -translate-x-1/2"
        aria-hidden
      >
        <svg
          width="44"
          height="48"
          viewBox="0 0 44 48"
          className="h-12 w-11 drop-shadow-[0_4px_14px_rgba(239,68,68,0.6)]"
          role="presentation"
        >
          <defs>
            <linearGradient id={pointerGradId} x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stopColor="#ef4444" />
              <stop offset="55%" stopColor="#dc2626" />
              <stop offset="100%" stopColor="#b91c1c" />
            </linearGradient>
          </defs>
          {/* Tip at bottom points toward wheel center; base along the top */}
          <path
            d="M4 6 L40 6 L22 42 Z"
            fill={`url(#${pointerGradId})`}
            stroke="#f59e0b"
            strokeWidth="1.25"
            strokeLinejoin="round"
          />
        </svg>
      </div>
    </>
  );
}
