import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

import { storeTema } from './modules/tema';
import { storeToast } from './modules/toast';
import wizardPeminjaman from './modules/wizard-peminjaman';
import jadwalMingguan from './modules/jadwal-mingguan';
import unggahBerkas from './modules/unggah-berkas';
import notifikasi from './modules/notifikasi';
import fieldBuilder from './modules/field-builder';
import grafikDashboard from './modules/grafik';
import hitungMundur from './modules/hitung-mundur';
import { pasangPenangananForm } from './modules/form';

Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.store('tema', storeTema());
Alpine.store('toast', storeToast());

Alpine.data('wizardPeminjaman', wizardPeminjaman);
Alpine.data('jadwalMingguan', jadwalMingguan);
Alpine.data('unggahBerkas', unggahBerkas);
Alpine.data('notifikasi', notifikasi);
Alpine.data('fieldBuilder', fieldBuilder);
Alpine.data('grafikDashboard', grafikDashboard);
Alpine.data('hitungMundur', hitungMundur);

// Salin teks ke clipboard, mis. kode booking.
Alpine.magic('salin', () => async (teks, pesan = 'Disalin ke clipboard') => {
    try {
        await navigator.clipboard.writeText(teks);
        Alpine.store('toast').tampil(pesan, 'success');
    } catch {
        Alpine.store('toast').tampil('Gagal menyalin', 'error');
    }
});

window.Alpine = Alpine;
window.toast = (pesan, jenis = 'info') => Alpine.store('toast').tampil(pesan, jenis);

pasangPenangananForm();
Alpine.start();
