import './bootstrap';

import Chart from 'chart.js/auto';
import Sortable from 'sortablejs';
import Tesseract from 'tesseract.js';

// Sediakan untuk komponen Alpine (carta & OCR resit)
window.Chart = Chart;
window.Tesseract = Tesseract;

// Tipografi carta ikut tema (warna dataset kekal dari server: makna untung/kos/jualan)
Chart.defaults.font.family = "'Barlow', 'Segoe UI', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#5c5245';
Chart.defaults.borderColor = 'rgba(120, 95, 60, 0.15)';

/*
 * Pengesahan dua kali untuk tindakan padam. Guna:
 *   x-on:click="confirmTwice('Padam X?') && $wire.deleteX()"
 * Mesej kedua (ikut bahasa) ditetapkan oleh layout dalam window.PMW_CONFIRM2.
 */
window.confirmTwice = (msg) =>
  window.confirm(msg) && window.confirm(window.PMW_CONFIRM2 || 'Anda pasti? Tindakan ini tak boleh dibatalkan.');

/* Helper format Ringgit di klien (padan dengan helper Blade rm()) */
window.RM = (n) =>
  'RM' +
  (Math.round((+n || 0) * 100) / 100).toLocaleString('en-MY', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

/*
 * Komponen Alpine untuk Scan Resit (OCR Tesseract.js).
 * Didaftar pada `alpine:init` supaya ia WUJUD sebelum Alpine memproses
 * x-data="receiptOcr()" — elak ralat "receiptOcr is not defined" yang
 * mematikan preview + OCR bila didaftar lewat melalui @script.
 */
document.addEventListener('alpine:init', () => {
  /*
   * Susun dengan drag (SortableJS). Guna:
   *   <div x-data="sortable('sortProducts', 5)"> <div data-sort-id="1"><span class="drag-handle">⠿</span>…</div> … </div>
   * Bila dilepaskan, $wire[method](...args, [id, id, …]) dipanggil dengan susunan baharu.
   */
  window.Alpine.data('sortable', (method, ...args) => ({
    init() {
      Sortable.create(this.$el, {
        handle: this.$el.dataset.handle || '.drag-handle',
        draggable: '[data-sort-id]',
        animation: 150,
        ghostClass: 'drag-ghost',
        chosenClass: 'drag-chosen',
        onEnd: (e) => {
          if (e.oldIndex === e.newIndex) return;
          const ids = [...this.$el.children].filter((c) => c.dataset.sortId).map((c) => +c.dataset.sortId);
          this.$wire.call(method, ...args, ids);
        },
      });
    },
  }));

  /* Buka/tutup kumpulan (kategori / variant group); diingat dalam pelayar ini */
  window.Alpine.data('collapsible', (key) => ({
    open: true,
    init() {
      try {
        this.open = localStorage.getItem('pmw-fold-' + key) !== '0';
      } catch (e) {}
      this.$watch('open', (v) => {
        try {
          localStorage.setItem('pmw-fold-' + key, v ? '1' : '0');
        } catch (e) {}
      });
    },
    toggle() {
      this.open = !this.open;
    },
  }));

  window.Alpine.data('receiptOcr', () => ({
    preview: null,
    progress: '',

    async handle(e) {
      const file = e.target.files[0];
      if (!file) return;

      // Preview segera (client-side)
      const reader = new FileReader();
      reader.onload = (ev) => {
        this.preview = ev.target.result;
      };
      reader.readAsDataURL(file);

      // OCR (Tesseract.js di pelayar)
      if (typeof window.Tesseract === 'undefined') {
        this.progress = 'OCR tidak dimuatkan — sila isi jumlah manual.';
        return;
      }

      this.progress = 'Membaca resit… 0%';
      try {
        const { data } = await window.Tesseract.recognize(file, 'eng', {
          logger: (m) => {
            if (m.status === 'recognizing text') {
              this.progress = 'Membaca resit… ' + Math.round(m.progress * 100) + '%';
            }
          },
        });

        const text = data.text;
        const found = [];
        text.split('\n').forEach((ln) => {
          const m = ln.match(/(.+?)\s+(?:RM)?\s*(\d{1,4}[.,]\d{2})\s*$/i);
          if (m) {
            const nm = m[1].replace(/[^A-Za-z0-9 &]/g, '').trim();
            const amt = parseFloat(m[2].replace(',', '.'));
            if (
              nm.length >= 2 &&
              amt > 0 &&
              !/total|jumlah|cash|tunai|change|baki|round|gst|sst|subtotal/i.test(nm)
            ) {
              found.push({ name: nm, amount: amt });
            }
          }
        });

        let total = 0;
        const tm = text.match(/(?:total|jumlah)[^\d]*(\d{1,5}[.,]\d{2})/i);
        if (tm) total = parseFloat(tm[1].replace(',', '.'));

        // Kesan no invois / no resit
        const inv = text.match(/(?:invoice|inv|resit|receipt|bil)\s*(?:no\.?|#|:|\.)*\s*([A-Za-z0-9][A-Za-z0-9\-/]{2,})/i);
        if (inv) {
          this.$wire.set('invoiceNo', inv[1].trim());
        }

        this.$wire.set('items', found);
        if (total > 0) {
          this.$wire.set('amount', total.toFixed(2));
        } else {
          this.$wire.call('sumItems');
        }

        this.progress = found.length
          ? 'Siap dibaca ✓ — sila semak jumlah.'
          : 'Tak jumpa item automatik — sila taip jumlah manual.';
        setTimeout(() => {
          this.progress = '';
        }, 3000);
      } catch (err) {
        this.progress = 'Gagal baca resit — sila isi manual.';
      }
    },
  }));
});
