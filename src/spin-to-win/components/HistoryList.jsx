import React from 'react';

export default function HistoryList({
  rows,
  title,
  emptyMessage,
  remainingSpins,
  spinsLeftLabel,
}) {
  return (
    <aside className="order-3 xl:col-span-3 xl:flex xl:h-full xl:min-h-0 xl:flex-col">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-2.5">
          <span
            className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-secondary text-[#c0172e] ring-1 ring-amber-400/20"
            aria-hidden
          >
            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </span>
          <h2 className="font-heading text-base font-extrabold uppercase tracking-[0.12em] text-text-primary">
            {title}
          </h2>
        </div>
        <span
          className="inline-flex shrink-0 items-center rounded-full bg-gradient-to-r from-[#c0172e] via-[#b91c1c] to-[#9f1239] px-3.5 py-1.5 text-xs font-black uppercase tracking-wide text-white shadow-[0_6px_20px_-4px_rgba(192,23,46,0.55)] ring-2 ring-amber-400/35"
          aria-label={`${remainingSpins} ${spinsLeftLabel}`}
        >
          {remainingSpins} {spinsLeftLabel}
        </span>
      </div>
      <ul className="stw-side-scrollbar max-h-[min(420px,50vh)] space-y-2 overflow-y-auto pr-1 text-sm text-text-secondary xl:max-h-none xl:min-h-0 xl:flex-1">
        {rows.length === 0 ? (
          <li className="rounded-2xl border-2 border-dashed border-amber-400/25 bg-white/60 px-4 py-8 text-center text-sm italic leading-relaxed text-text-secondary">
            {emptyMessage}
          </li>
        ) : (
          rows.map((row, index) => (
            <li
              key={`${row.prize_label}-${row.created_at}-${index}`}
              className="rounded-xl border border-gray-100/90 bg-white/95 px-3.5 py-2.5 shadow-sm ring-1 ring-black/[0.03] [animation:fadeInUp_0.45s_ease-out_both]"
              style={{ animationDelay: `${Math.min(index, 8) * 45}ms` }}
            >
              <span className="font-bold text-text-primary">{row.prize_label}</span>
              <span className="mt-0.5 block text-xs font-medium text-text-secondary/90">
                {row.created_at}
              </span>
            </li>
          ))
        )}
      </ul>
    </aside>
  );
}
