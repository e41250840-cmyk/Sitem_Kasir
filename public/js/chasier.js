// Data lokal sebagai Fallback jika API belum dibuat/error
const products = [
  { id: 1, code: "BRG001", barcode: "899123456001", name: "Indomie Goreng", price: 3500, stock: 100 },
  { id: 2, code: "BRG002", barcode: "899123456002", name: "Aqua 600ml", price: 4000, stock: 50 },
  { id: 3, code: "BRG003", barcode: "899123456003", name: "Teh Botol Sosro", price: 5000, stock: 45 },
  { id: 4, code: "BRG004", barcode: "899123456004", name: "Beras 5 Kg", price: 75000, stock: 20 },
  { id: 5, code: "BRG005", barcode: "899123456005", name: "Minyak Goreng 1 Liter", price: 18000, stock: 30 },
  { id: 6, code: "BRG006", barcode: "899123456006", name: "Gula Pasir 1 Kg", price: 17000, stock: 25 }
];

let found = [];
let cart = [];
let held = [];
let method = "Tunai";
let searchSeq = 0;

const $ = id => document.getElementById(id);
const num = id => parseFloat($(id)?.value) || 0;

const formatRupiah = v =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 })
    .format(v).replace(/\s/g, " ");

const esc = v =>
  String(v ?? "").replace(/[&<>"']/g, c => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[c]));

const newTransaction = no => { if ($("transactionNumber")) $("transactionNumber").innerText = no; };
const nextNo = no => no.replace(/(\d+)$/, m => String(+m + 1).padStart(3, "0"));

// PENCARIAN PRODUK
async function searchProduct() {
  const input = $("searchProduct");
  const box = $("productResults");
  if (!input || !box) return;

  const keyword = input.value.trim().toLowerCase();
  const seq = ++searchSeq;

  if (!keyword) {
    found = [];
    box.innerHTML = "";
    box.style.display = "none";
    return;
  }

  try {
    if (window.KASIR && window.KASIR.searchUrl) {
      const res = await fetch(`${window.KASIR.searchUrl}?q=${encodeURIComponent(keyword)}`, {
        headers: { Accept: "application/json" }
      });
      if (!res.ok) throw new Error("HTTP " + res.status);
      const data = await res.json();
      if (seq !== searchSeq) return;

      // Mendukung format API array langsung [...] atau { data: [...] }
      const apiResults = Array.isArray(data) ? data : (data.data || []);

      // Jika hasil API kosong, gunakan fallback pencarian lokal
      found = apiResults.length ? apiResults : filterLocalProducts(keyword);
    } else {
      found = filterLocalProducts(keyword);
    }
  } catch (err) {
    console.warn("API Error, fallback ke data produk lokal:", err);
    found = filterLocalProducts(keyword);
  }

  renderSearchResults();
}

function filterLocalProducts(keyword) {
  return products.filter(p =>
    p.name.toLowerCase().includes(keyword) ||
    p.code.toLowerCase().includes(keyword) ||
    String(p.barcode).includes(keyword)
  );
}

function renderSearchResults() {
  const box = $("productResults");
  if (!box) return;

  if (!found.length) {
    box.innerHTML = `<div class="product-item" style="cursor:default">Barang tidak ditemukan</div>`;
  } else {
    box.innerHTML = found.map(p => `
      <div class="product-item" onclick="addToCart(${p.id})">
        <div>
          <div class="product-name">${esc(p.name)}</div>
          <div class="product-code">${esc(p.code)} • ${esc(p.barcode)} • Stok ${p.stock ?? '-'}</div>
        </div>
        <div class="product-price">${formatRupiah(p.price)}</div>
      </div>
    `).join("");
  }
  box.style.display = "block";
}

// OPERASI KERANJANG & TRANSAKSI
function addToCart(id) {
  const p = found.find(item => item.id === id) || products.find(item => item.id === id);
  const ex = cart.find(item => item.id === id);

  if (!p && !ex) return;

  if (ex) {
    ex.qty++;
  } else {
    cart.push({ ...p, qty: 1 });
  }

  const input = $("searchProduct");
  if (input) input.value = "";
  if ($("productResults")) $("productResults").style.display = "none";

  renderCart();
  if (input) input.focus();
}

function renderCart() {
  const body = $("cartBody");
  if (!body) return;

  if (!cart.length) {
    body.innerHTML = `<tr><td colspan="6" class="empty-cart">Keranjang masih kosong.<br>Silakan cari atau scan barang.</td></tr>`;
    calculateTotal();
    return;
  }

  body.innerHTML = cart.map((it, i) => `
    <tr>
      <td>${i + 1}</td>
      <td>
        <strong>${esc(it.name)}</strong>
        <div style="font-size:11px;color:var(--mut)">${esc(it.code)}</div>
      </td>
      <td>${formatRupiah(it.price)}</td>
      <td>
        <div class="qty-control">
          <button onclick="changeQty(${it.id}, -1)">−</button>
          <input type="number" value="${it.qty}" min="1" onchange="updateQty(${it.id}, this.value)">
          <button onclick="changeQty(${it.id}, 1)">+</button>
        </div>
      </td>
      <td class="text-right"><strong>${formatRupiah(it.price * it.qty)}</strong></td>
      <td class="text-center">
        <button class="remove-btn" onclick="removeItem(${it.id})">×</button>
      </td>
    </tr>
  `).join("");

  calculateTotal();
}

function changeQty(id, a) {
  const it = cart.find(i => i.id === id);
  if (!it) return;
  it.qty += a;
  if (it.qty <= 0) return removeItem(id);
  renderCart();
}

function updateQty(id, q) {
  const it = cart.find(i => i.id === id);
  if (!it) return;
  it.qty = Math.max(1, parseInt(q) || 1);
  renderCart();
}

function removeItem(id) {
  cart = cart.filter(i => i.id !== id);
  renderCart();
}

function getSubtotal() { return cart.reduce((s, i) => s + i.price * i.qty, 0); }

function getGrandTotal() {
  const sub = getSubtotal();
  const disc = sub * (num("discountPercent") / 100) + num("discountAmount");
  return Math.max(0, sub - disc + num("tax") + num("otherFee"));
}

function calculateTotal() {
  const qty = cart.reduce((s, i) => s + i.qty, 0);
  if ($("totalQty")) $("totalQty").innerText = qty;
  if ($("itemCount")) $("itemCount").innerText = qty + " Item";
  if ($("subtotal")) $("subtotal").innerText = formatRupiah(getSubtotal());
  if ($("grandTotal")) $("grandTotal").innerText = formatRupiah(getGrandTotal());
  renderQuick();
  calculateChange();
}

function calculateChange() {
  const ch = num("payment") - getGrandTotal();
  const box = $("changeBox");
  if (box) box.classList.toggle("short-payment", ch < 0);
  if ($("changeLabel")) $("changeLabel").innerText = ch < 0 ? "UANG KURANG" : "KEMBALIAN";
  if ($("change")) $("change").innerText = formatRupiah(Math.abs(ch));
}

function renderQuick() {
  const t = getGrandTotal(), q = $("quick");
  if (!q) return;
  if (!t) { q.innerHTML = ""; return; }
  const opts = [...new Set([50000, 100000, 200000].filter(n => n >= t))].slice(0, 2);
  q.innerHTML = `<button onclick="setPay(${t})">Uang Pas</button>` + opts.map(n => `<button onclick="setPay(${n})">${formatRupiah(n)}</button>`).join("");
}

function setPay(n) { if ($("payment")) $("payment").value = n; calculateChange(); }

function selectPayment(b) {
  document.querySelectorAll(".payment-method button").forEach(x => x.classList.remove("active"));
  b.classList.add("active");
  method = b.innerText;
}

function resetForm() {
  cart = [];
  if ($("payment")) $("payment").value = "";
  ["discountPercent", "discountAmount", "tax", "otherFee"].forEach(i => { if ($(i))$(i).value = 0; });
  if ($("cust")) $("cust").selectedIndex = 0;
  if ($("memberNo")) $("memberNo").value = "";
  renderCart();
}

function holdTransaction() {
  if (!cart.length) return alert("Tidak ada transaksi untuk ditahan.");
  const cur = $("transactionNumber") ? $("transactionNumber").innerText : "";
  held.push({ no: cur, cart: JSON.parse(JSON.stringify(cart)), total: getGrandTotal() });
  resetForm();
  newTransaction(nextNo(cur));
  renderHeld();
}

function renderHeld() {
  const card = $("heldCard"), list = $("heldList");
  if (card) card.style.display = held.length ? "block" : "none";
  if (!list) return;
  list.innerHTML = held.map((h, i) => `
    <div class="product-item" style="cursor:default">
      <div>
        <div class="product-name">${esc(h.no)}</div>
        <div class="product-code">${h.cart.reduce((s, c) => s + c.qty, 0)} Item • ${formatRupiah(h.total)}</div>
      </div>
      <button class="btn btn-primary" onclick="resumeHeld(${i})">Lanjutkan</button>
    </div>
  `).join("");
}

function resumeHeld(i) {
  if (cart.length && !confirm("Keranjang saat ini akan diganti. Lanjutkan?")) return;
  const h = held.splice(i, 1)[0];
  cart = h.cart;
  if ($("transactionNumber")) $("transactionNumber").innerText = h.no;
  renderCart();
  renderHeld();
}

async function processPayment() {
  if (!cart.length) return alert("Keranjang masih kosong.");
  const total = getGrandTotal(), pay = num("payment");
  if (pay < total) return alert("Uang pembayaran masih kurang.");

  // Jika API backend belum siap, jalankan cetak simulasi browser
  if (!window.KASIR || !window.KASIR.storeUrl) {
    alert("Transaksi Berhasil! Membuka dialog cetak...");
    window.print(); // Otomatis cetak tampilan kasir saat ini
    resetForm();
    return;
  }

  try {
    const res = await fetch(window.KASIR.storeUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": window.KASIR.csrf
      },
      body: JSON.stringify({
        customer: $("cust")?.value,
        member_no: $("memberNo")?.value,
        items: cart.map(i => ({ id: i.id, qty: i.qty })),
        discount_percent: num("discountPercent"),
        discount_amount: num("discountAmount"),
        tax: num("tax"),
        other_fee: num("otherFee"),
        paid_amount: pay,
        payment_method: method
      })
    });

    const d = await res.json();
    if (!res.ok) return alert(d.message || "Transaksi gagal disimpan.");

    if (d.receipt_url) {
      location.href = d.receipt_url;
    } else {
      alert("Transaksi berhasil disimpan!");
      window.print();
      resetForm();
    }
  } catch (err) {
    console.error(err);
    alert("Gagal terhubung ke server Laravel. Membuka mode cetak langsung...");
    window.print();
  }
}

function cancelTransaction() {
  if (!cart.length || !confirm("Batalkan transaksi ini?")) return;
  resetForm();
}

// EVENT LISTENERS
document.addEventListener("DOMContentLoaded", () => {
  const input = $("searchProduct");

  if (input) {
    input.addEventListener("input", searchProduct);
    input.addEventListener("keydown", async e => {
      if (e.key !== "Enter") return;
      e.preventDefault();
      await searchProduct();

      const k = input.value.trim().toLowerCase();
      const p = found.find(item => String(item.barcode) === k || item.code.toLowerCase() === k) || (found.length === 1 ? found[0] : null);

      if (p) addToCart(p.id);
    });
  }

  // Klik luar untuk menutup popup hasil pencarian
  document.addEventListener("click", e => {
    const box = $("productResults");
    if (!box) return;
    if (!e.target.closest(".search-input")) {
      box.style.display = "none";
    }
  });

  // Shortcut Keyboard (F2: Cari, F4: Bayar, Esc: Batal)
  document.addEventListener("keydown", e => {
    if (e.key === "F2") { e.preventDefault(); $("searchProduct")?.focus(); }
    if (e.key === "F4") { e.preventDefault(); $("payment")?.focus(); }
    if (e.key === "Escape") cancelTransaction();
  });

  if ($("currentDate")) $("currentDate").innerText = new Date().toLocaleString("id-ID");
  renderCart();
});
