/**
 * Spin To Win — React app entrypoint.
 */
import './spin-to-win.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import SpinToWinApp from './spin-to-win/SpinToWinApp.jsx';

(function () {
  'use strict';

  function boot() {
    const cfg = typeof window !== 'undefined' ? window.neraSpinToWin : null;
    const rootEl = document.getElementById('nera-spin-root');

    if (!cfg || !rootEl) {
      return;
    }

    const root = createRoot(rootEl);
    root.render(React.createElement(SpinToWinApp, { cfg }));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
