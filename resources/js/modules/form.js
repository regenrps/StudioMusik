/**
 * Perilaku formulir global:
 *  - Cegah kirim ganda: tombol submit dinonaktifkan & menampilkan spinner.
 *  - Konfirmasi sederhana lewat atribut data-konfirmasi="Pesan?".
 * Tambahkan data-tanpa-loading pada <form> untuk menonaktifkan perilaku ini.
 */
export function pasangPenangananForm() {
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-tanpa-loading')) {
                return;
            }

            const pesan = form.getAttribute('data-konfirmasi');
            if (pesan && !window.confirm(pesan)) {
                event.preventDefault();
                return;
            }

            if (form.dataset.terkirim === '1') {
                event.preventDefault();
                return;
            }

            // Biarkan validasi HTML5 bekerja lebih dulu.
            if (!form.checkValidity()) {
                return;
            }

            form.dataset.terkirim = '1';
            const pengirim = event.submitter || form.querySelector('[type="submit"]');
            if (pengirim) {
                pengirim.classList.add('is-loading');
                pengirim.setAttribute('aria-busy', 'true');
            }
            // Ditunda agar nilai tombol pengirim tetap ikut terkirim.
            setTimeout(() => form.querySelectorAll('[type="submit"]').forEach((b) => (b.disabled = true)), 0);
        },
        false,
    );

    // Kembalikan keadaan tombol saat halaman dipulihkan dari bfcache (tombol Kembali).
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-terkirim="1"]').forEach((form) => {
            delete form.dataset.terkirim;
            form.querySelectorAll('[type="submit"]').forEach((b) => {
                b.disabled = false;
                b.classList.remove('is-loading');
                b.removeAttribute('aria-busy');
            });
        });
    });
}

/** fetch JSON dengan header Laravel yang sesuai. */
export async function ambilJson(url, opsi = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(url, {
        credentials: 'same-origin',
        ...opsi,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            ...(opsi.headers || {}),
        },
    });

    if (!res.ok) {
        let pesan = 'Terjadi kesalahan. Silakan coba lagi.';
        try {
            const data = await res.json();
            pesan = data.message || pesan;
        } catch {
            /* abaikan */
        }
        throw new Error(pesan);
    }

    return res.json();
}
