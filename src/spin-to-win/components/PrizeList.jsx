import React from 'react';

export default function PrizeList({ items, title }) {
  return (
    <aside className="hidden xl:col-span-3 order-2 xl:order-1 xl:flex xl:h-full xl:min-h-0 xl:flex-col">
      <div className="mb-4 flex items-center gap-3">
        <span
          className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#c0172e] to-[#9f1239] text-white shadow-[0_8px_24px_-8px_rgba(192,23,46,0.45)] ring-2 ring-amber-400/25"
          aria-hidden
        >
          <svg className="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
            <path d="M12 2l2.09 6.26L20 9.27l-5.45 3.97L16.18 20 12 16.77 7.82 20 9.45 13.24 4 9.27l5.91-.01L12 2z" />
          </svg>
        </span>
        <div>
          <h2 className="font-heading text-base font-extrabold uppercase leading-tight tracking-[0.12em] text-text-primary">
            {title}
          </h2>
        </div>
      </div>
      <ul className="stw-prize-scroll stw-side-scrollbar max-h-[min(420px,50vh)] space-y-3 overflow-y-auto pr-1 xl:max-h-none xl:min-h-0 xl:flex-1">
        {items.map((item, index) => (
          <li
            key={`${item.label}-${index}`}
            className="group flex overflow-hidden rounded-2xl border border-gray-100/90 bg-white shadow-[0_8px_30px_-12px_rgba(15,23,42,0.12)] ring-1 ring-black/[0.04] transition-[transform,box-shadow] duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_40px_-16px_rgba(192,23,46,0.18)]"
          >
            <span
              className="w-2 shrink-0 self-stretch min-h-[4.5rem] transition-opacity group-hover:opacity-90"
              style={{ backgroundColor: item.backgroundColor }}
              aria-hidden
            />
            <div className="relative aspect-[4/3] min-h-[4.5rem] flex-1 overflow-hidden rounded-r-2xl bg-secondary">
              {item.imageUrl ? (
                <>
                  <img
                    src={item.imageUrl}
                    alt=""
                    className="absolute inset-0 !h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                    loading="lazy"
                  />
                  <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/35 via-transparent to-transparent opacity-80" />
                  <span className="absolute bottom-3 left-3 max-w-[calc(100%-1.5rem)] rounded-lg bg-white/95 px-2.5 py-1 text-left text-xs font-bold leading-tight text-text-primary shadow-[0_2px_12px_rgba(0,0,0,0.12)] ring-1 ring-black/5 backdrop-blur-[2px]">
                    {item.label}
                  </span>
                </>
              ) : (
                <div className="flex h-full w-full items-center justify-center p-4 text-center text-xs font-semibold text-text-secondary">
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
