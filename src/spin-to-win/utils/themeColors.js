export function readThemeColors() {
  const style = getComputedStyle(document.documentElement);
  const pick = (name, fallback) => (style.getPropertyValue(name) || fallback).trim();

  return {
    primary: pick('--color-primary', '#1313ec'),
    secondary: pick('--color-secondary', '#f4f7ff'),
    text: pick('--color-text-primary', '#0d0d1b'),
    primaryDark: pick('--color-primary-dark', '#0d0db3'),
    backgroundDark: pick('--color-background-dark', '#101022'),
    danger: pick('--color-danger', '#dc2626'),
    fontHeading: pick(
      '--font-heading',
      "'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
    ),
  };
}
