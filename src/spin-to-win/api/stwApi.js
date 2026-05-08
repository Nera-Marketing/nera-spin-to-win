function buildRestUrl(cfg, path) {
  return `${cfg.restUrl.replace(/\/$/, '')}/${path}`;
}

function restHeaders(cfg) {
  return {
    'X-WP-Nonce': cfg.nonce || '',
    Accept: 'application/json',
  };
}

export async function fetchState(cfg) {
  const url = buildRestUrl(cfg, `product/${cfg.productId}/state`);
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: restHeaders(cfg),
  });
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }
  return response.json();
}

export async function postSpin(cfg) {
  const url = buildRestUrl(cfg, `product/${cfg.productId}/spin`);
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      ...restHeaders(cfg),
      'Content-Type': 'application/json',
    },
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.message || data.code || `HTTP ${response.status}`);
  }

  return data;
}

export async function postAckSpin(cfg) {
  const url = buildRestUrl(cfg, `product/${cfg.productId}/spin/ack`);
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      ...restHeaders(cfg),
      'Content-Type': 'application/json',
    },
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.message || data.code || `HTTP ${response.status}`);
  }

  return data;
}

export async function postTurboSpin(cfg) {
  const url = buildRestUrl(cfg, `product/${cfg.productId}/spin/turbo`);
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      ...restHeaders(cfg),
      'Content-Type': 'application/json',
    },
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.message || data.code || `HTTP ${response.status}`);
  }

  return data;
}
