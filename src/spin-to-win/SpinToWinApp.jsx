import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { fetchState, postAckSpin, postSpin } from './api/stwApi.js';
import { formatHistoryRow } from './utils/history.js';
import HistoryList from './components/HistoryList.jsx';
import PrizeList from './components/PrizeList.jsx';
import SpinControls from './components/SpinControls.jsx';
import SpinModal from './components/SpinModal.jsx';
import WheelCanvas from './components/WheelCanvas.jsx';
import WheelChrome from './components/WheelChrome.jsx';

const SLICE_SIZE = 10;

function pickDisplaySlice(total, size = SLICE_SIZE, mustInclude = null) {
  if (total <= size) {
    return Array.from({ length: total }, (_, i) => i);
  }
  const arr = Array.from({ length: total }, (_, i) => i);
  for (let i = total - 1; i > 0; i -= 1) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  const slice = arr.slice(0, size);
  if (mustInclude !== null && !slice.includes(mustInclude)) {
    slice[0] = mustInclude;
  }
  return slice;
}

export default function SpinToWinApp({ cfg }) {
  const strings = cfg.strings || {};
  const [state, setState] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [spinning, setSpinning] = useState(false);
  const [turbo, setTurbo] = useState(false);
  const [spinRequest, setSpinRequest] = useState(null);
  const [modal, setModal] = useState({
    open: false,
    title: '',
    body: '',
    variant: 'default',
  });
  const [displayIndices, setDisplayIndices] = useState(null);

  const spinRequestId = useRef(0);
  const pendingResultRef = useRef(null);
  const resumeFromActiveSpinRef = useRef(false);
  const bootEffectIdRef = useRef(0);
  const displayIndicesRef = useRef(null);
  const stateRef = useRef(null);

  useEffect(() => {
    stateRef.current = state;
  }, [state]);

  useEffect(() => {
    displayIndicesRef.current = displayIndices;
  }, [displayIndices]);

  useEffect(() => {
    const bootId = ++bootEffectIdRef.current;
    let active = true;

    async function boot() {
      try {
        const data = await fetchState(cfg);
        if (!active || bootId !== bootEffectIdRef.current) {
          return;
        }
        setState(data);

        const totalItems = data.wheel_items?.length ?? 0;
        const mustIncludeRaw = data.active_spin
          ? Number.parseInt(data.active_spin.winning_index, 10)
          : null;
        const mustInclude =
          mustIncludeRaw != null && !Number.isNaN(mustIncludeRaw) ? mustIncludeRaw : null;
        const indices = pickDisplaySlice(totalItems, SLICE_SIZE, mustInclude);
        setDisplayIndices(indices);
        displayIndicesRef.current = indices;

        if (data.active_spin) {
          const as = data.active_spin;
          const serverIndex = Number.parseInt(as.winning_index, 10);
          if (!Number.isNaN(serverIndex)) {
            const localIndex = indices.indexOf(serverIndex);
            if (localIndex !== -1) {
              pendingResultRef.current = {
                winning_index: as.winning_index,
                prize_type: as.prize_type,
                prize_label: as.prize_label,
                remaining_spins: as.remaining_spins,
                details: as.details || {},
              };
              resumeFromActiveSpinRef.current = true;
              spinRequestId.current += 1;
              setSpinRequest({
                id: spinRequestId.current,
                index: localIndex,
                duration: 2600,
                revolutions: 5,
              });
              setSpinning(true);
              setTurbo(false);
            }
          }
        }
      } catch (err) {
        if (active && bootId === bootEffectIdRef.current) {
          setError(String(err.message || err));
        }
      } finally {
        if (active && bootId === bootEffectIdRef.current) {
          setLoading(false);
        }
      }
    }

    boot();
    return () => {
      active = false;
    };
  }, [cfg]);

  const wheelItems = useMemo(() => {
    if (!state?.wheel_items || !displayIndices) {
      return [];
    }
    return displayIndices.map((origIdx, pos) => {
      const item = state.wheel_items[origIdx];
      return {
        label: item.label,
        backgroundColor: pos % 2 === 0 ? '#c0172e' : '#e8950a',
        labelColor: '#ffffff',
      };
    });
  }, [state?.wheel_items, displayIndices]);

  const allWheelItems = useMemo(() => {
    if (!state?.wheel_items) {
      return [];
    }
    return state.wheel_items
      .filter((item) => {
        const normalizedType = String(item?.type || '')
          .toLowerCase()
          .replace(/-/g, '_');
        return normalizedType !== 'no_win';
      })
      .map((item, index) => ({
        label: item.label,
        imageUrl: item.image_url || item.image || '',
        backgroundColor: index % 2 === 0 ? '#c0172e' : '#e8950a',
        labelColor: '#ffffff',
      }));
  }, [state?.wheel_items]);

  const historyRows = useMemo(() => {
    if (!state || !Array.isArray(state.history)) {
      return [];
    }
    return state.history.map(formatHistoryRow);
  }, [state]);

  const closeModal = useCallback(() => {
    setModal({ open: false, title: '', body: '', variant: 'default' });
  }, []);

  const openModal = useCallback((title, body, variant = 'default') => {
    setModal({ open: true, title, body, variant });
  }, []);

  const triggerSpin = useCallback(
    async (turboMode) => {
      if (spinning || wheelItems.length === 0) {
        return;
      }

      if (state?.active_spin) {
        return;
      }

      const remaining = state?.remaining_spins ?? 0;
      if (remaining < 1) {
        openModal(
          strings.noSpins || 'No spins left',
          strings.noSpinsBody || 'Purchase more tickets to earn spins.',
          'noSpins',
        );
        return;
      }

      setSpinning(true);
      setTurbo(turboMode);

      try {
        const result = await postSpin(cfg);
        const serverIndex = Number.parseInt(result.winning_index, 10);
        if (Number.isNaN(serverIndex)) {
          throw new Error(strings.error || 'Invalid winning index');
        }

        const totalItems = state?.wheel_items?.length ?? 0;
        let currentIndices = displayIndicesRef.current;
        if (!currentIndices || totalItems === 0) {
          throw new Error(strings.error || 'Invalid wheel state');
        }

        if (totalItems > SLICE_SIZE && !currentIndices.includes(serverIndex)) {
          currentIndices = pickDisplaySlice(totalItems, SLICE_SIZE, serverIndex);
          setDisplayIndices(currentIndices);
          displayIndicesRef.current = currentIndices;
        }

        const localIndex = currentIndices.indexOf(serverIndex);
        if (localIndex === -1) {
          throw new Error(strings.error || 'Invalid winning index');
        }

        pendingResultRef.current = result;
        spinRequestId.current += 1;
        setSpinRequest({
          id: spinRequestId.current,
          index: localIndex,
          duration: turboMode ? 900 : 2600,
          revolutions: turboMode ? 2 : 5,
        });
      } catch (err) {
        const technical = String(err.message || err);
        const customBody =
          strings.errorBody && String(strings.errorBody).trim() !== ''
            ? strings.errorBody
            : technical;
        openModal(strings.error || 'Something went wrong', customBody);
        setSpinning(false);
        setTurbo(false);
      }
    },
    [cfg, openModal, spinning, state, strings, wheelItems.length],
  );

  const onSpinEnd = useCallback(
    async (requestId) => {
      if (!spinRequest || requestId !== spinRequest.id) {
        return;
      }

      const result = pendingResultRef.current;
      const isResume = resumeFromActiveSpinRef.current;
      if (isResume) {
        resumeFromActiveSpinRef.current = false;
      }

      pendingResultRef.current = null;
      setSpinRequest(null);

      if (result) {
        if (!isResume) {
          setState((prev) => {
            if (!prev) {
              return prev;
            }
            const nextHistory = [
              {
                prize_label: result.prize_label || '',
                created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
              },
              ...(Array.isArray(prev.history) ? prev.history : []),
            ].slice(0, 20);

            return {
              ...prev,
              remaining_spins: result.remaining_spins,
              history: nextHistory,
            };
          });
        }

        if (result.prize_type === 'no_win') {
          openModal(
            strings.tryAgain || 'Close, but not this time.',
            strings.tryAgainBody || "The fun's not over... Give it another spin!",
          );
        } else if (result.prize_type === 'woo_wallet') {
          const amount =
            result.details && result.details.amount != null
              ? result.details.amount
              : '';
          openModal(
            strings.youWon || 'You won!',
            (strings.wonWallet || 'Site credit added: {amount}').replace(
              '{amount}',
              String(amount),
            ),
          );
        } else if (result.prize_type === 'physical') {
          openModal(
            strings.youWon || 'You won!',
            strings.wonPhysical || 'We will contact you about your physical prize.',
          );
        }
      }

      try {
        await postAckSpin(cfg);
      } catch {
        // Ack failure: next load will still have active_spin and can resume.
      }

      setState((prev) => (prev ? { ...prev, active_spin: null } : prev));

      setSpinning(false);
      setTurbo(false);

      const totalWheel = stateRef.current?.wheel_items?.length ?? 0;
      if (totalWheel > SLICE_SIZE) {
        const fresh = pickDisplaySlice(totalWheel, SLICE_SIZE);
        setDisplayIndices(fresh);
        displayIndicesRef.current = fresh;
      }
    },
    [cfg, openModal, spinRequest, strings],
  );

  if (loading) {
    return (
      <div className="relative z-[1] flex min-h-[12rem] flex-col items-center justify-center gap-4 py-16">
        <span
          className="inline-block h-10 w-10 animate-spin rounded-full border-[3px] border-[#c0172e]/20 border-t-[#c0172e]"
          aria-hidden
        />
        <p className="text-sm font-medium text-text-secondary">Loading wheel…</p>
      </div>
    );
  }

  if (error) {
    return (
      <p className="relative z-[1] flex min-h-full items-center justify-center px-4 py-12 text-center text-sm font-medium text-danger">
        {error}
      </p>
    );
  }

  if (!state?.feature_enabled) {
    return (
      <p className="relative z-[1] flex min-h-full items-center justify-center px-4 py-12 text-center text-sm text-text-secondary">
        {strings.disabled || 'Spin To Win is temporarily unavailable.'}
      </p>
    );
  }

  return (
    <>
      <div className="relative z-[1] h-full min-h-0 rounded-2xl border border-white/60 bg-gradient-to-br from-white/90 via-secondary/40 to-amber-50/20 px-0 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.85)] sm:p-5 lg:p-6">
        <div
          className="pointer-events-none absolute inset-0 overflow-hidden rounded-2xl"
          aria-hidden
        >
          <div className="absolute -right-20 top-1/4 h-72 w-72 rounded-full bg-[#c0172e]/[0.05] blur-3xl" />
          <div className="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-amber-300/10 blur-3xl" />
        </div>
        <div className="relative z-[1] grid h-full min-h-0 grid-cols-1 items-stretch gap-8 lg:gap-10 xl:grid-cols-12">
        <PrizeList items={allWheelItems} title={strings.prizesTitle || 'All prizes'} />

        <div className="stw-wheel-col xl:col-span-6 order-1 xl:order-2 flex flex-col items-center md:justify-center pt-6">
          <div
            className="stw-wheel-shell relative w-full max-w-[min(100%,546px)] aspect-square mx-auto mb-6 rounded-full bg-[radial-gradient(circle_at_50%_50%,rgba(192,23,46,0.22)_0%,transparent_70%)] p-[2px] shadow-[0_0_0_4px_rgba(251,191,36,0.25),0_0_60px_-10px_rgba(192,23,46,0.6),0_32px_80px_-20px_rgba(60,0,10,0.5)]"
          >
            <div className="relative h-full w-full overflow-hidden rounded-full bg-[#7b0d1e]">
              <WheelCanvas
                items={wheelItems}
                spinRequest={spinRequest}
                onSpinEnd={onSpinEnd}
              />
              <WheelChrome segmentCount={wheelItems.length || 10} />
            </div>
          </div>

          <SpinControls
            strings={strings}
            turbo={turbo}
            spinning={spinning}
            onViewPrizes={() => {
              setModal({
                open: true,
                title: strings.prizesTitle || 'All prizes',
                body: '',
                variant: 'prizes',
              });
            }}
            onTurboSpin={() => {
              triggerSpin(true);
            }}
            onFullSpin={() => {
              triggerSpin(false);
            }}
          />
        </div>

        <HistoryList
          rows={historyRows}
          title={strings.historyTitle || 'History'}
          emptyMessage={
            strings.emptyHistory ||
            'No spin history yet. Start spinning to discover your next prize!'
          }
          remainingSpins={state.remaining_spins ?? 0}
          spinsLeftLabel={strings.spinsLeft || 'spins left'}
        />
        </div>
      </div>

      <SpinModal
        open={modal.open}
        strings={strings}
        title={modal.title}
        body={modal.body}
        variant={modal.variant}
        prizeItems={allWheelItems}
        competitionsUrl={cfg.competitionsUrl || '/'}
        onClose={closeModal}
        onSpin={() => {
          closeModal();
          triggerSpin(false);
        }}
        onTurbo={() => {
          closeModal();
          triggerSpin(true);
        }}
      />
    </>
  );
}
