<x-layouts.error kode="403" judul="Akses ditolak" ikon="heroicon-o-lock-closed"
    :pesan="$exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.' ? $exception->getMessage() : 'Anda tidak memiliki izin untuk membuka halaman ini.'" />
