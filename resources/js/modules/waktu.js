/** Utilitas tanggal/jam tanpa dependensi, aman dari pergeseran zona waktu. */

export const keMenit = (hhmm) => {
    const [h, m] = String(hhmm).slice(0, 5).split(':').map(Number);
    return h * 60 + m;
};

export const keJam = (menit) => `${String(Math.floor(menit / 60)).padStart(2, '0')}:${String(menit % 60).padStart(2, '0')}`;

export const isoLokal = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

export const hariIniIso = () => isoLokal(new Date());

export const dariIso = (iso) => {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d);
};

export const tambahHari = (iso, n) => {
    const d = dariIso(iso);
    d.setDate(d.getDate() + n);
    return isoLokal(d);
};

export const seninDari = (iso) => {
    const d = dariIso(iso);
    const geser = (d.getDay() + 6) % 7;
    d.setDate(d.getDate() - geser);
    return isoLokal(d);
};

export const labelDurasi = (menit) => {
    const j = Math.floor(menit / 60);
    const s = menit % 60;
    return [j ? `${j} jam` : '', s ? `${s} menit` : ''].filter(Boolean).join(' ');
};

export const labelJam = (hhmm) => (hhmm ? String(hhmm).slice(0, 5).replace(':', '.') : '');

export const labelTanggal = (iso, opsi = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) =>
    iso ? dariIso(iso).toLocaleDateString('id-ID', opsi) : '';
