<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Menu Kantin</title>
</head>
<body>
    <h1>Ubah Menu Kantin</h1>

    @if (session('gagal'))
        <p>{{ session('gagal') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('menu.update', $menu['id']) }}" method="POST">
        @csrf
        @method('PUT')

        <p>Kode Menu<br>
            <input type="text" name="kode_menu" value="{{ old('kode_menu', $menu['kode_menu']) }}">
        </p>
        <p>Nama Menu<br>
            <input type="text" name="nama_menu" value="{{ old('nama_menu', $menu['nama_menu']) }}">
        </p>
        <p>Barcode<br>
            <input type="text" name="barcode" value="{{ old('barcode', $menu['barcode']) }}">
        </p>
        <p>Harga<br>
            <input type="number" name="harga" value="{{ old('harga', $menu['harga']) }}">
        </p>
        <p>Kategori Menu<br>
            <select name="kategori_menu_id">
                @foreach ($daftarKategori as $kat)
                    <option value="{{ $kat['id'] }}" @selected(old('kategori_menu_id', $menu['kategori_menu_id']) == $kat['id'])>
                        {{ $kat['nama_kategori'] }}
                    </option>
                @endforeach
            </select>
        </p>

        <button type="submit">Simpan Perubahan</button>
        <a href="{{ route('menu.index') }}">Kembali</a>
    </form>
</body>
</html>