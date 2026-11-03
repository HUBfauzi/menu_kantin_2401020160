<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

// Helper function statusHarga() sesuai spesifikasi Tipe E
if (!function_exists('statusHarga')) {
    function statusHarga(int $harga): string {
        return $harga >= 20000 ? 'Premium' : 'Reguler';
    }
}

// 1. READ: Indeks data, pencarian 3 kolom (kode, nama, barcode), dan filter kategori
Route::get('/menu', function (Request $request) {
    $katakunci = trim((string) $request->query('q', ''));
    $kategoriMenuId = $request->query('kategori_menu_id', '');

    try {
        $pdo = DB::connection()->getPdo();
        $sql = 'SELECT m.id, m.kode_menu, m.nama_menu, m.barcode, m.harga,
                       m.kategori_menu_id, k.nama_kategori
                FROM menu AS m
                JOIN kategori_menu AS k ON k.id = m.kategori_menu_id
                WHERE 1=1';
        $parameter = [];

        if ($katakunci !== '') {
            $sql .= ' AND (m.kode_menu LIKE :kata_kunci_kode
                      OR m.nama_menu LIKE :kata_kunci_nama
                      OR m.barcode LIKE :kata_kunci_barcode)';
            // BENAR (Tanpa backslash):
            $nilaiPencarian = '%' . $katakunci . '%';
            $parameter['kata_kunci_kode'] = $nilaiPencarian;
            $parameter['kata_kunci_nama'] = $nilaiPencarian;
            $parameter['kata_kunci_barcode'] = $nilaiPencarian;
        }

        if ($kategoriMenuId !== '') {
            $sql .= ' AND m.kategori_menu_id = :kategori_menu_id';
            $parameter['kategori_menu_id'] = (int) $kategoriMenuId;
        }

        $sql .= ' ORDER BY m.kode_menu';

        $stmtMenu = $pdo->prepare($sql);
        $stmtMenu->execute($parameter);
        $daftarMenu = $stmtMenu->fetchAll(PDO::FETCH_ASSOC);

        $stmtKat = $pdo->prepare('SELECT id, nama_kategori FROM kategori_menu ORDER BY nama_kategori');
        $stmtKat->execute();
        $daftarKategori = $stmtKat->fetchAll(PDO::FETCH_ASSOC);

        return view('menu', compact('daftarMenu', 'daftarKategori', 'katakunci', 'kategoriMenuId'));
    } catch (\Throwable $error) {
        report($error);
        return response('Data tidak dapat dibaca. Periksa koneksi dan query.', 500);
    }
})->name('menu.index');

// 2. CREATE: Sanitasi, Validasi Server-Side, Cek Duplikat, dan Prepared Statement INSERT
Route::post('/menu', function (Request $request) {$dataRaw = [
        'kode_menu' => strip_tags(trim((string) $request->input('kode_menu'))),
        'nama_menu' => strip_tags(trim((string) $request->input('nama_menu'))),
        'barcode' => strip_tags(trim((string) $request->input('barcode'))),
        'harga' => $request->input('harga'),
        'kategori_menu_id' => $request->input('kategori_menu_id'),
    ];

    $validator = Validator::make($dataRaw, [
        'kode_menu' => ['required', 'string', 'size:10', 'regex:/^[a-zA-Z0-9]+$/'],
        'nama_menu' => ['required', 'string', 'min:3', 'max:100'],
        'barcode' => ['required', 'string', 'min:5', 'max:30'],
        'harga' => ['required', 'integer', 'min:1000', 'max:100000'],
        'kategori_menu_id' => ['required', 'integer'],
    ], [
        'kode_menu.required' => 'Kode menu wajib diisi.',
        'kode_menu.size' => 'Kode menu harus tepat 10 karakter.',
        'kode_menu.regex' => 'Kode menu harus berupa alfanumerik.',
        'nama_menu.required' => 'Nama menu wajib diisi.',
        'nama_menu.min' => 'Nama menu minimal 3 karakter.',
        'barcode.required' => 'Barcode wajib diisi.',
        'barcode.min' => 'Barcode minimal 5 karakter.',
        'harga.required' => 'Harga wajib diisi.',
        'harga.integer' => 'Harga harus berupa angka.',
        'harga.min' => 'Harga minimal Rp 1.000.',
        'harga.max' => 'Harga maksimal Rp 100.000.',
        'kategori_menu_id.required' => 'Kategori menu wajib dipilih.',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    $data =$validator->validated();

    try {
        $pdo = DB::connection()->getPdo();$cek = $pdo->prepare('SELECT COUNT(*) FROM menu WHERE kode_menu = :kode OR barcode = :barcode');$cek->execute(['kode' => $data['kode_menu'], 'barcode' =>$data['barcode']]);

        if ((int) $cek->fetchColumn() > 0) {
            return back()->withInput()->with('gagal', 'Kode menu atau Barcode sudah digunakan.');
        }

        $simpan =$pdo->prepare('INSERT INTO menu (kode_menu, nama_menu, barcode, harga, kategori_menu_id) VALUES (:kode_menu, :nama_menu, :barcode, :harga, :kategori_menu_id)');
        $simpan->execute($data);

        return redirect()->route('menu.index')->with('berhasil', 'Data menu berhasil ditambahkan.');
    } catch (\Throwable $error) {
        report($error);
        return back()->withInput()->with('gagal', 'Data gagal disimpan. Periksa koneksi dan data.');
    }
})->name('menu.store');

// 3. EDIT FORM: Mengambil data menu lama berdasarkan ID
Route::get('/menu/{id}/edit', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();
        $stmt =$pdo->prepare('SELECT id, kode_menu, nama_menu, barcode, harga, kategori_menu_id FROM menu WHERE id = :id');
        $stmt->execute(['id' =>$id]);
        $menu =$stmt->fetch(PDO::FETCH_ASSOC);

        if (!$menu) {
            return redirect()->route('menu.index')->with('gagal', 'Data menu tidak ditemukan.');
        }

        $stmtKat = $pdo->prepare('SELECT id, nama_kategori FROM kategori_menu ORDER BY nama_kategori');$stmtKat->execute();
        $daftarKategori =$stmtKat->fetchAll(PDO::FETCH_ASSOC);

        return view('menu-edit', compact('menu', 'daftarKategori'));
    } catch (\Throwable $error) {
        report($error);
        return redirect()->route('menu.index')->with('gagal', 'Data gagal dibuka untuk diubah.');
    }
})->name('menu.edit');

// 4. UPDATE: Memperbarui data dengan pengecekan duplikat id <> :id
Route::put('/menu/{id}', function (Request $request, int $id) {$dataRaw = [
        'kode_menu' => strip_tags(trim((string) $request->input('kode_menu'))),
        'nama_menu' => strip_tags(trim((string) $request->input('nama_menu'))),
        'barcode' => strip_tags(trim((string) $request->input('barcode'))),
        'harga' => $request->input('harga'),
        'kategori_menu_id' => $request->input('kategori_menu_id'),
    ];

    $validator = Validator::make($dataRaw, [
        'kode_menu' => ['required', 'string', 'size:10', 'regex:/^[a-zA-Z0-9]+$/'],
        'nama_menu' => ['required', 'string', 'min:3', 'max:100'],
        'barcode' => ['required', 'string', 'min:5', 'max:30'],
        'harga' => ['required', 'integer', 'min:1000', 'max:100000'],
        'kategori_menu_id' => ['required', 'integer'],
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    $data =$validator->validated();

    try {
        $pdo = DB::connection()->getPdo();
        $cek =$pdo->prepare('SELECT COUNT(*) FROM menu WHERE (kode_menu = :kode OR barcode = :barcode) AND id <> :id');
        $cek->execute(['kode' =>$data['kode_menu'], 'barcode' => $data['barcode'], 'id' =>$id]);

        if ((int) $cek->fetchColumn() > 0) {
            return back()->withInput()->with('gagal', 'Kode menu atau Barcode sudah digunakan menu lain.');
        }

        $ubah = $pdo->prepare('UPDATE menu SET kode_menu = :kode_menu, nama_menu = :nama_menu, barcode = :barcode, harga = :harga, kategori_menu_id = :kategori_menu_id WHERE id = :id');$params = array_merge($data, ['id' =>$id]);
        $ubah->execute($params);

        return redirect()->route('menu.index')->with('berhasil', 'Data menu berhasil diperbarui.');
    } catch (\Throwable $error) {
        report($error);
        return back()->withInput()->with('gagal', 'Data gagal diperbarui. Periksa koneksi dan data.');
    }
})->name('menu.update');

// 5. DELETE: Menghapus data menu berdasarkan ID
Route::delete('/menu/{id}', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();
        $hapus = $pdo->prepare('DELETE FROM menu WHERE id = :id');
        $hapus->execute(['id' => $id]);

        if ($hapus->rowCount() === 0) {
            return redirect()->route('menu.index')->with('gagal', 'Data menu tidak ditemukan.');
        }

        return redirect()->route('menu.index')->with('berhasil', 'Data menu berhasil dihapus.');
    } catch (\Throwable $error) {
        report($error);
        return redirect()->route('menu.index')->with('gagal', 'Data gagal dihapus. Periksa relasi atau koneksi.');
    }
})->name('menu.destroy');   