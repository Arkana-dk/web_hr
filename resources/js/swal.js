// resources/js/swal.js
import Swal from 'sweetalert2'

// Toast siap pakai
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
})

// API publik
export function toastSuccess(title = 'Berhasil') { return Toast.fire({ icon: 'success', title }) }
export function toastError(title = 'Terjadi kesalahan') { return Toast.fire({ icon: 'error', title }) }
export function alertSuccess(title = 'Berhasil', text = '') { return Swal.fire({ icon: 'success', title, text }) }
export function alertError(title = 'Gagal', text = '') { return Swal.fire({ icon: 'error', title, text }) }
export function confirm(
  title = 'Yakin?',
  text = 'Tindakan ini tidak bisa dibatalkan.',
  confirmButtonText = 'Ya, lanjut',
  cancelButtonText = 'Batal'
) {
  return Swal.fire({
    title, text, icon: 'question',
    showCancelButton: true,
    confirmButtonText, cancelButtonText,
    reverseButtons: true, focusCancel: true,
  })
}

// optional: expose ke window untuk dipanggil inline
window.alerts = { toastSuccess, toastError, alertSuccess, alertError, confirm }
export default window.alerts

// --- Delegasi global untuk [data-confirm] ---
document.addEventListener('click', async (e) => {
  const el = e.target.closest('[data-confirm]')
  if (!el) return

  e.preventDefault()

  const title  = el.dataset.title  || 'Yakin?'
  const text   = el.dataset.text   || 'Lanjutkan tindakan ini?'
  const ok     = el.dataset.confirm || 'Ya'
  const cancel = el.dataset.cancel  || 'Batal'

  const res = await confirm(title, text, ok, cancel)
  if (!res.isConfirmed) return

  // 1) submit form tertentu (pakai selector)
  if (el.dataset.form) {
    const form = document.querySelector(el.dataset.form)
    if (form) form.submit()
    return
  }

  // 2) kalau tombol ada di dalam form → submit form terdekat
  const nearestForm = el.closest('form')
  if (nearestForm) { nearestForm.submit(); return }

  // 3) tanpa form → buat form dinamis (support _method)
  const action = el.dataset.action || el.getAttribute('href')
  if (!action) return

  const method = (el.dataset.method || 'POST').toUpperCase()
  const token =
    el.dataset.token ||
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
    ''

  const f = document.createElement('form')
  f.method = (method === 'GET') ? 'GET' : 'POST'
  f.action = action
  f.style.display = 'none'
  f.innerHTML = `
    <input type="hidden" name="_token" value="${token}">
    ${['PUT','PATCH','DELETE'].includes(method) ? `<input type="hidden" name="_method" value="${method}">` : ''}
  `
  document.body.appendChild(f)
  f.submit()
})
