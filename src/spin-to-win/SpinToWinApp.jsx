import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { fetchState, postAckSpin, postSpin, postTurboSpin } from './api/stwApi.js';
import { formatHistoryRow } from './utils/history.js';
import { readThemeColors } from './utils/themeColors.js';
import { cx } from './utils/cx.js';
import HistoryList from './components/HistoryList.jsx';
import PrizeList from './components/PrizeList.jsx';
import SpinControls from './components/SpinControls.jsx';
import SpinModal from './components/SpinModal.jsx';
import WheelCanvas from './components/WheelCanvas.jsx';
import WheelChrome from './components/WheelChrome.jsx';
import styles from './SpinToWinApp.module.css';

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
  const [turboRunning, setTurboRunning] = useState(false);
  const [turboResultItems, setTurboResultItems] = useState([]);
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

  const palette = useMemo(() => readThemeColors(), []);

  const wheelItems = useMemo(() => {
    if (!state?.wheel_items || !displayIndices) {
      return [];
    }
    return displayIndices.map((origIdx, pos) => {
      const item = state.wheel_items[origIdx];
      return {
        label: item.label,
        // Wheel slices use the fixed-default --stw-wheel-* tokens (red/gold),
        // independent of brand; overridable per site for special cases.
        backgroundColor: pos % 2 === 0 ? palette.wheelA : palette.wheelB,
        labelColor: palette.wheelLabel,
      };
    });
  }, [state?.wheel_items, displayIndices, palette]);

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
        backgroundColor: index % 2 === 0 ? palette.brand : palette.accent,
        labelColor: '#ffffff',
      }));
  }, [state?.wheel_items, palette]);

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

  const triggerTurboAll = useCallback(async () => {
    if (spinning || turboRunning) {
      return;
    }
    if ((state?.remaining_spins ?? 0) < 1) {
      openModal(
        strings.noSpins || 'No spins left',
        strings.noSpinsBody || 'Purchase more tickets to earn spins.',
        'noSpins',
      );
      return;
    }

    setTurboRunning(true);
    try {
      const batch = await postTurboSpin(cfg);
      const currency = cfg.currencySymbol || '£';

      const resultItems = (batch.results || []).map((r) => {
        const kind = (r.details && r.details.kind) || r.prize_type;
        const amount = r.details && r.details.amount != null ? `${currency}${r.details.amount}` : '';
        return {
          label: r.prize_label || '',
          kind,
          amount,
          backgroundColor: r.prize_type === 'no_win' ? '#94a3b8' : palette.brand,
        };
      });

      const wonCount = resultItems.filter((i) => i.kind !== 'no_win').length;
      const wonTemplate = strings.turboResultsWon || 'You won {count} prize(s)!';
      const wonTitle = wonTemplate
        .replace('{count}', String(wonCount))
        .replace('{plural}', wonCount === 1 ? '' : 's');
      const title = wonCount > 0 ? wonTitle : (strings.turboResultsNone || 'Better luck next time!');

      setState((prev) => {
        if (!prev) {
          return prev;
        }
        const newRows = (batch.results || []).map((r) => ({
          prize_label: r.prize_label || '',
          prize_type: r.prize_type,
          created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
        }));
        const nextHistory = [...newRows, ...(Array.isArray(prev.history) ? prev.history : [])].slice(0, 20);
        return {
          ...prev,
          remaining_spins: batch.remaining_spins ?? 0,
          history: nextHistory,
        };
      });

      setTurboResultItems(resultItems);
      openModal(title, '', 'turbo-results');
    } catch (err) {
      const technical = String(err.message || err);
      const customBody =
        strings.errorBody && String(strings.errorBody).trim() !== ''
          ? strings.errorBody
          : technical;
      openModal(strings.error || 'Something went wrong', customBody);
    } finally {
      setTurboRunning(false);
    }
  }, [cfg, openModal, spinning, state, strings, turboRunning, palette]);

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
        } else {
          // Match the wheel slice / history / Turbo results — show the operator's label.
          openModal(
            strings.youWon || 'You won!',
            result.prize_label || '',
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
      <div className={styles.loading}>
        <span className={styles.spinner} aria-hidden />
        <p className={styles.loadingText}>Loading wheel…</p>
      </div>
    );
  }

  if (error) {
    return (
      <p className={styles.error}>
        {error}
      </p>
    );
  }

  if (!state?.feature_enabled) {
    return (
      <p className={styles.disabled}>
        {strings.disabled || 'Spin To Win is temporarily unavailable.'}
      </p>
    );
  }

  return (
    <>
      <div className={styles.card}>
        <div className={styles.blobs} aria-hidden>
          <div className={styles.blobRed} />
          <div className={styles.blobAmber} />
        </div>
        <div className={styles.grid}>
          <PrizeList items={allWheelItems} title={strings.prizesTitle || 'All prizes'} />

          <div className={cx(styles.wheelCol, 'stw-wheel-col')}>
            <div
              className={cx(styles.wheelShell, 'stw-wheel-shell')}
            >
              <div className={styles.wheelInner}>
                <WheelCanvas
                  items={wheelItems}
                  spinRequest={spinRequest}
                  onSpinEnd={onSpinEnd}
                />
                <WheelChrome
                segmentCount={wheelItems.length || 10}
                rim={palette.wheelRim}
                bulb={palette.wheelBulb}
                pointer={palette.wheelPointer}
              />
              </div>
            </div>

            <SpinControls
              strings={strings}
              turbo={turbo}
              spinning={spinning || turboRunning}
              onViewPrizes={() => {
                setModal({
                  open: true,
                  title: strings.prizesTitle || 'All prizes',
                  body: '',
                  variant: 'prizes',
                });
              }}
              onTurboSpin={() => {
                if ((state?.remaining_spins ?? 0) < 1) {
                  openModal(
                    strings.noSpins || 'No spins left',
                    strings.noSpinsBody || 'Purchase more tickets to earn spins.',
                    'noSpins',
                  );
                  return;
                }
                openModal(
                  strings.turboConfirmTitle || 'Turbo Spin',
                  strings.turboConfirmBody ||
                    'Are you sure you want to use turbo spin, it will reveal all prizes instantly',
                  'turbo-confirm',
                );
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
        prizeItems={modal.variant === 'turbo-results' ? turboResultItems : allWheelItems}
        competitionsUrl={cfg.competitionsUrl || '/'}
        onClose={closeModal}
        onSpin={() => {
          closeModal();
          triggerSpin(false);
        }}
        onTurbo={() => {
          closeModal();
          triggerTurboAll();
        }}
      />
    </>
  );
}
