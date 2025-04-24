<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Arsip</title>
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 14px;
            color: #2c3e50;
            line-height: 1.5;
            margin: 20px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }
        h1, h2, h3 {
            color: #2ecc71;
            margin: 0.5em 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2ecc71;
            padding-bottom: 10px;
        }
        .summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        .summary-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .charts {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
        }
        canvas {
            max-width: 100%;
            height: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #2ecc71;
            color: white;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .resto-ranking {
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            margin-top: 20px;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
        }
        .chart-error {
            color: #e74c3c;
            font-size: 12px;
            text-align: center;
        }
        @media print {
            body {
                margin: 0;
                font-size: 10pt;
            }
            .charts {
                display: block;
            }
            .chart {
                break-inside: avoid;
                page-break-inside: avoid;
                margin-bottom: 20px;
            }
            table {
                font-size: 9pt;
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .resto-ranking {
                break-before: page;
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Stok Arsip</h1>
        <p>Periode: {{ $start_date }} - {{ $end_date }}</p>
        <p>Dibuat pada: {{ $generated_at }}</p>
    </div>

    <div class="summary">
        <button onclick="shareToWhatsApp()">Share ke WhatsApp</button>
        <h2>Ringkasan</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <strong>Total Stok Akhir:</strong>
                <p>{{ number_format($highlights['total_stock'], 2) }} unit</p>
            </div>
            <div class="summary-item">
                <strong>Total Transaksi OUT:</strong>
                <p>{{ number_format($highlights['total_out'], 2) }} unit</p>
            </div>
            <div class="summary-item">
                <strong>Item Terlaris:</strong>
                <p>{{ $highlights['top_item']['nama'] ?? '-' }} ({{ number_format($highlights['top_item']['stok_akhir'] ?? 0, 2) }})</p>
            </div>
            <div class="summary-item">
                <strong>Resto Paling Aktif:</strong>
                <p>{{ $highlights['top_resto']['resto'] ?? '-' }} ({{ number_format($highlights['top_resto']['total_out'] ?? 0, 2) }})</p>
            </div>
        </div>
    </div>

    <div class="charts">
        <div class="chart">
            <h3>Tren Stok</h3>
            <canvas id="stockTrendChart"></canvas>
            <p class="chart-error" id="stockTrendError" style="display: none;">Gagal memuat grafik tren stok.</p>
        </div>
        <div class="chart">
            <h3>Distribusi OUT per Resto</h3>
            <canvas id="restoOutChart"></canvas>
            <p class="chart-error" id="restoOutError" style="display: none;">Gagal memuat grafik distribusi resto.</p>
        </div>
    </div>

    @foreach($report_data as $tanggal => $items)
        <h2>Stok pada {{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Item</th>
                    <th>Satuan</th>
                    <th>Stok Awal</th>
                    <th>IN</th>
                    <th>OUT</th>
                    <th>Stok Akhir</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item['kode'] }}</td>
                        <td>{{ $item['nama'] }}</td>
                        <td>{{ $item['satuan'] }}</td>
                        <td>{{ number_format($item['stok_awal'], 2) }}</td>
                        <td>{{ number_format($item['in'], 2) }}</td>
                        <td>{{ number_format($item['out'], 2) }}</td>
                        <td>{{ number_format($item['stok_akhir'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="resto-ranking">
        <h2>Ranking Resto (Top 10 Transaksi OUT)</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Nama Resto</th>
                    <th>Total OUT</th>
                    <th>Jumlah Transaksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resto_ranking as $index => $resto)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $resto['resto'] ?? 'Tidak Diketahui' }}</td>
                        <td>{{ number_format($resto['total_out'], 2) }}</td>
                        <td>{{ $resto['transaction_count'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Tidak ada transaksi OUT.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Laporan ini dihasilkan secara otomatis oleh sistem stok.</p>
        <p>© {{ date('Y') }} Stokis Banjarmasin</p>
    </div>

    <script defer>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                document.getElementById('stockTrendError').style.display = 'block';
                document.getElementById('restoOutError').style.display = 'block';
                console.error('Chart.js gagal dimuat.');
                return;
            }

            // Tren Stok
            const stockTrendCtx = document.getElementById('stockTrendChart').getContext('2d');
            new Chart(stockTrendCtx, {
                type: 'line',
                data: {
                    labels: [@foreach($stock_trend as $trend)'{{ \Carbon\Carbon::parse($trend['tanggal'])->format('d M') }}', @endforeach],
                    datasets: [{
                        label: 'Total Stok (juta)',
                        data: [@foreach($stock_trend as $trend){{ $trend['total_stock'] / 1000000 }}, @endforeach],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.2)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: { display: true, text: 'Stok (M)' },
                            ticks: { callback: function(value) { return value.toFixed(1); } }
                        },
                        x: { title: { display: true, text: 'Tanggal' } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) { return `${ctx.raw.toFixed(1)}M`; }
                            }
                        }
                    }
                }
            });

            // Distribusi OUT per Resto
            const restoOutCtx = document.getElementById('restoOutChart').getContext('2d');
            new Chart(restoOutCtx, {
                type: 'pie',
                data: {
                    labels: [@foreach($resto_out as $resto)'{{ Str::limit($resto['resto'], 15) }}', @endforeach],
                    datasets: [{
                        data: [@foreach($resto_out as $resto){{ $resto['total_out'] }}, @endforeach],
                        backgroundColor: ['#2ecc71', '#3498db', '#e74c3c', '#f1c40f', '#9b59b6', '#7f8c8d']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) { return `${ctx.label}: ${(ctx.raw / 1000).toFixed(1)}K`; }
                            }
                        }
                    }
                }
            });
        });
        function shareToWhatsApp() {
            const text = `Laporan Stok ${new Date().toLocaleDateString()}:\n` +
                        `Total Stok: ${{{ $highlights['total_stock'] }}}\n` +
                        `Top Resto: ${{{ $highlights['top_resto']['resto'] }}} (${{{ $highlights['top_resto']['total_out'] }}})\n` +
                        `Cek detail: ${window.location.href}`;
            const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank');
        }
    </script>
</body>
</html>