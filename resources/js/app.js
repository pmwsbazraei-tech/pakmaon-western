import './bootstrap';

import Chart from 'chart.js/auto';
import Tesseract from 'tesseract.js';

// Sediakan untuk komponen Alpine (carta & OCR resit)
window.Chart = Chart;
window.Tesseract = Tesseract;

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
