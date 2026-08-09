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
