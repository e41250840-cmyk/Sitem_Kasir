<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Retail POS</title>
    <link rel="stylesheet" href="{{ asset('css/chasier.css') }}">
</head>
<body>
<div class="pos-container">
    <!-- HEADER -->
    <header class="header">
        <div>
            <div class="store-name">TOKO MUEZA</div>
            <div class="store-info">
                Jl. Bulaktal - Lumajang Telp. 0812-345-678
            </div>
        </div>
        <div class="transaction-info">
            <div>No. Transaksi</div>
            <div class="transaction-number" id="transactionNumber">{{ $trxNumber ?? 'TRX-001' }}</div>
            <div id="currentDate"></div>
        </div>
    </header>

    <!-- MAIN POS LAYOUT -->
    <main class="main">
        <!-- LEFT AREA -->
        <section>
            <!-- SEARCH & CUSTOMER -->
            <div class="card">
                <div class="card-header">Tambah Barang</div>
                <div class="card-body">
                    <div class="search-area">
                        <div class="search-input" style="position: relative; flex: 1;">
                            <input type="text" id="searchProduct" placeholder="Cari nama barang / kode / barcode..." autocomplete="off">
                            <div class="product-results" id="productResults"></div>
                        </div>
                        <button class="btn btn-primary" onclick="searchProduct()">Cari</button>
                    </div>

                    <!-- CUSTOMER -->
                    <div class="customer-area">
                        <div class="form-group">
                            <label>Pelanggan</label>
                            <select class="form-control" id="cust">
                                <option value="Umum">Umum</option>
                                <option value="Pelanggan Member">Pelanggan Member</option>
                                <option value="Member VIP">Member VIP</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No. Member / HP</label>
                            <input type="text" class="form-control" id="memberNo" placeholder="Opsional">
                        </div>
                    </div>
                </div>
            </div>

            <!-- CART -->
            <div class="card" style="margin-top:15px">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items:center;">
                        <span>Keranjang Belanja</span>
                        <span id="itemCount">0 Item</span>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Barang</th>
                                <th width="100">Harga</th>
                                <th width="120" class="text-center">Qty</th>
                                <th width="120" class="text-right">Subtotal</th>
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <tr id="emptyRow">
                                <td colspan="6" class="empty-cart">
                                    Keranjang masih kosong.<br>Silakan cari atau scan barang.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- RIGHT AREA -->
        <aside>
            <div class="card">
                <div class="card-header">Ringkasan Pembayaran</div>
                <div class="card-body">
                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Total Item</span>
                            <strong id="totalQty">0</strong>
                        </div>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <strong id="subtotal">Rp 0</strong>
                        </div>
                        <div class="summary-row">
                            <span>Diskon (%)</span>
                            <input type="number" id="discountPercent" value="0" min="0" max="100" onchange="calculateTotal()">
                        </div>
                        <div class="summary-row">
                            <span>Diskon (Rp)</span>
                            <input type="number" id="discountAmount" value="0" min="0" onchange="calculateTotal()">
                        </div>
                        <div class="summary-row">
                            <span>Pajak / PPN</span>
                            <input type="number" id="tax" value="0" min="0" onchange="calculateTotal()">
                        </div>
                        <div class="summary-row">
                            <span>Biaya Lain</span>
                            <input type="number" id="otherFee" value="0" min="0" onchange="calculateTotal()">
                        </div>
                        <div class="total-box">
                            <div class="total-label">TOTAL AKHIR</div>
                            <div class="total-value" id="grandTotal">Rp 0</div>
                        </div>

                        <!-- PAYMENT -->
                        <div class="payment-box">
                            <div class="payment-label">Uang Dibayar</div>
                            <input type="number" id="payment" class="payment-input" placeholder="0" oninput="calculateChange()">

                            <!-- QUICK NOMINAL -->
                            <div class="quick-pay" id="quick" style="margin-top: 10px; display: flex; gap: 5px;"></div>

                            <div class="payment-label" style="margin-top:15px">Metode Pembayaran</div>
                            <div class="payment-method">
                                <button class="active" onclick="selectPayment(this)">Tunai</button>
                                <button onclick="selectPayment(this)">QRIS</button>
                                <button onclick="selectPayment(this)">Debit</button>
                                <button onclick="selectPayment(this)">Kredit</button>
                                <button onclick="selectPayment(this)">E-Wallet</button>
                                <button onclick="selectPayment(this)">Transfer</button>
                            </div>

                            <div class="change-box" id="changeBox">
                                <div class="change-label" id="changeLabel">KEMBALIAN</div>
                                <div class="change-value" id="change">Rp 0</div>
                            </div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="action-area" style="margin-top:15px; display:flex; gap:5px;">
                            <button class="btn btn-warning" onclick="holdTransaction()">Tahan</button>
                            <button class="btn btn-danger" onclick="cancelTransaction()">Batal</button>
                            <button class="btn btn-success btn-pay" style="flex:1" onclick="processPayment()">BAYAR & CETAK</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card" id="heldCard" style="display:none;margin-top:15px">
                <div class="card-header">Transaksi Ditahan</div>
                <div class="card-body" id="heldList"></div>
            </div>
        </aside>
    </main>
</div>

<!-- INISIALISASI URL UNTUK JAVASCRIPT -->
<script>
    window.KASIR = {
        searchUrl: "{{ route('kasir.produk') }}",
        storeUrl: "{{ route('kasir.store') }}",
        csrf: "{{ csrf_token() }}"
    };
</script>
<script src="{{ asset('js/chasier.js') }}"></script>
</body>
</html>
