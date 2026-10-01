<!-- resources/views/kasir/struk.blade.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran</title>
    <style>
        body { font-family: monospace; width: 58mm; padding: 10px; margin: 0 auto; }
        .text-center { text-align: center; }
        .flex { display: flex; justify-content: space-between; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="text-center">
        <h3>TOKO RETAIL MAKMUR</h3>
        <p>Jl. Contoh No. 123 - Jember</p>
        <hr>
    </div>

    <!-- Detail item transaksi disesuaikan -->
    <div class="flex"><span>Indomie Goreng x1</span><span>3.500</span></div>
    <hr>
    <div class="flex"><strong>Total:</strong><strong>3.500</strong></div>

    <div class="no-print" style="margin-top: 15px; text-align:center;">
        <button onclick="window.print()">Cetak Ulang</button>
        <a href="{{ route('kasir.index') }}">Kembali ke Kasir</a>
    </div>

    <script>
        // Otomatis buka dialog cetak saat halaman struk dimuat
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
