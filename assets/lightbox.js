document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.createElement('div');
  overlay.className = 'lightbox-overlay';
  overlay.innerHTML = '<div class="lightbox-panel"><button type="button" class="lightbox-close" aria-label="Close">×</button><div class="lightbox-grid"></div></div>';
  document.body.appendChild(overlay);
  const grid = overlay.querySelector('.lightbox-grid');
  const close = () => overlay.classList.remove('open');
  overlay.addEventListener('click', (event) => { if (event.target === overlay) close(); });
  overlay.querySelector('.lightbox-close').addEventListener('click', close);

  document.querySelectorAll('[data-view-photos]').forEach((button) => {
    button.addEventListener('click', async () => {
      const sessionId = button.getAttribute('data-view-photos');
      grid.innerHTML = '<p class="muted">Loading photos…</p>';
      overlay.classList.add('open');
      try {
        const response = await fetch(`session_photos.php?session_id=${sessionId}`);
        const data = await response.json();
        if (!data.photos.length) { grid.innerHTML = '<p class="muted">No photos for this visit.</p>'; return; }
        grid.innerHTML = data.photos.map((photo) => `<figure><img src="${photo.file_path}" alt="${photo.phase} photo"><figcaption>${photo.phase}</figcaption></figure>`).join('');
      } catch (error) { grid.innerHTML = '<p class="muted">Could not load photos.</p>'; }
    });
  });
});
