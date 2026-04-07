export function formatHistoryRow(row) {
  return {
    prize_label: row.prize_label || '',
    created_at: row.created_at || '',
  };
}
