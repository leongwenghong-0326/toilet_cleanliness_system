document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-camera-form]').forEach((form) => {
    const camera = form.querySelector('[data-camera]');
    const video = camera.querySelector('video');
    const canvas = camera.querySelector('canvas');
    const placeholder = camera.querySelector('.camera-placeholder');
    const start = camera.querySelector('[data-start-camera]');
    const capture = camera.querySelector('[data-capture]');
    const payload = form.querySelector('[data-photo-payload]');
    const strip = form.querySelector('[data-photo-strip]');
    const submit = form.querySelector('[data-submit]');
    const photos = [];
    let stream;
    const render = () => {
      payload.value = JSON.stringify(photos);
      submit.disabled = photos.length === 0;
      capture.disabled = photos.length >= 3 || !camera.classList.contains('ready');
      strip.innerHTML = photos.map((photo, index) => `<div class="photo-thumb"><img src="${photo}" alt="Captured evidence ${index + 1}"><span>${index + 1}</span><button type="button" class="photo-remove" data-remove="${index}" aria-label="Remove photo ${index + 1}">×</button></div>`).join('');
    };
    strip.addEventListener('click', (event) => {
      const button = event.target.closest('[data-remove]');
      if (!button) return;
      photos.splice(Number(button.dataset.remove), 1);
      render();
    });
    start.addEventListener('click', async () => { try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false }); video.srcObject = stream; camera.classList.add('ready'); capture.disabled = photos.length >= 3; start.textContent = 'Camera ready'; } catch (error) { start.textContent = 'Camera permission needed'; start.classList.add('danger'); } });
    capture.addEventListener('click', () => { if (photos.length >= 3) return; canvas.width = video.videoWidth; canvas.height = video.videoHeight; canvas.getContext('2d').drawImage(video, 0, 0); photos.push(canvas.toDataURL('image/jpeg', 0.82)); render(); });
    placeholder.addEventListener('click', () => start.click());
    form.addEventListener('submit', () => { if (stream) stream.getTracks().forEach((track) => track.stop()); });
  });
});
