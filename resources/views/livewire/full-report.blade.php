<div>
    <x-pagehead title="Laporan Penuh" sub="PnL, Bajet, ROI & sasaran — sedia untuk cetak/simpan" :withMonth="true" />

    <div class="noprint" style="margin-bottom:12px">
        <button class="btn sec sm" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    </div>

    <div class="card">
        <h2 class="sec" style="margin-top:0">Penyata Untung Rugi (PnL) — {{ $month }}</h2>
        <div class="table-wrap">
            <table>
                <tr><td style="font-weight:700">JUALAN</td><td class="num" style="font-weight:700">@rm($c['sales'])</td></tr>
                <tr><td style="font-weight:600;padding-top:12px">Kos Bahan (COGS)</td><td class="num">@rm($c['cogs'])</td></tr>
                <tr><td>Untung Kasar</td><td class="num pos">@rm($c['sales'] - $c['cogs'])</td></tr>

                <tr><td style="font-weight:600;padding-top:12px">KOS BERUBAH</td><td></td></tr>
                @forelse ($varRows as $r)
                    <tr><td>&nbsp;&nbsp;{{ $r['label'] }}</td><td class="num">@rm($r['amount'])</td></tr>
                @empty
                    <tr><td style="color:var(--muted)">&nbsp;&nbsp;Tiada</td><td class="num">RM0.00</td></tr>
                @endforelse
                <tr><td>Jumlah Kos Berubah</td><td class="num">@rm($c['varTotal'])</td></tr>

                <tr><td style="font-weight:600;padding-top:12px">KOS TETAP</td><td></td></tr>
                <tr><td>&nbsp;&nbsp;☺ Kos Staff (gaji+EPF+SOCSO)</td><td class="num">@rm($c['staffCost'])</td></tr>
                <tr><td>&nbsp;&nbsp;🏠 Sewa & lain-lain tetap</td><td class="num">@rm($c['otherFixed'])</td></tr>
                <tr><td>Jumlah Kos Tetap</td><td class="num">@rm($c['fixedTotal'])</td></tr>
                <tr><td>Jumlah Perbelanjaan</td><td class="num">@rm($c['expenses'])</td></tr>

                <tr style="border-top:2px solid var(--line)">
                    <td style="font-weight:800;font-size:15px">UNTUNG BERSIH</td>
                    <td class="num {{ $c['net'] >= 0 ? 'pos' : 'neg' }}" style="font-weight:800;font-size:15px">@rm($c['net'])</td>
                </tr>
                <tr><td style="color:var(--muted)">Margin Untung</td><td class="num">{{ number_format($c['marginPct'], 1) }}%</td></tr>
            </table>
        </div>
    </div>

    <div class="split" style="margin-top:14px">
        <div class="card">
            <h2 class="sec" style="margin-top:0">Metrik Prestasi</h2>
            <table>
                <tr><td>Food Cost %</td><td class="num">{{ number_format($c['foodCostPct'], 1) }}%</td></tr>
                <tr><td>Break-even (jualan/bulan)</td><td class="num">@rm($c['beSales'])</td></tr>
                <tr><td>Break-even (jualan/hari)</td><td class="num">@rm($c['beSales'] / 30)</td></tr>
                <tr><td>Purata jualan harian</td><td class="num">@rm($c['sales'] / 30)</td></tr>
            </table>
        </div>
        <div class="card">
            <h2 class="sec" style="margin-top:0">Bajet & ROI</h2>
            <table>
                <tr><td>Modal Buka (Capex)</td><td class="num">@rm($c['capex'])</td></tr>
                <tr><td>ROI sebenar</td><td class="num">{{ is_infinite($c['roiMonths']) ? '—' : number_format($c['roiMonths'], 1).' bulan' }}</td></tr>
                <tr><td>Target jualan bulanan</td><td class="num">@rm($outlet->target_sales)</td></tr>
                <tr><td>Target untung bersih</td><td class="num">@rm($c['targetNet'])</td></tr>
                <tr><td>Target balik modal</td><td class="num">{{ (int) $c['targetRoiMonths'] }} bulan</td></tr>
                <tr><td>Pencapaian sales vs target</td><td class="num">{{ $outlet->target_sales > 0 ? number_format($c['sales'] / $outlet->target_sales * 100, 0) : 0 }}%</td></tr>
            </table>
        </div>
    </div>
</div>
