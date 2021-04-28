export function r404(path) {
  window.location.href = `/404.php?p=${encodeURIComponent(path)}`;
}