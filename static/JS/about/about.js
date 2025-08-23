// Simple About page search filter
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('aboutSearch');
  const articles = document.querySelectorAll('.file-content article');
  if (input) {
    input.addEventListener('input', () => {
      const val = input.value.toLowerCase();
      articles.forEach(article => {
        article.style.display = article.textContent.toLowerCase().includes(val) ? '' : 'none';
      });
    });
  }
});