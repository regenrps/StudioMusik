import { ambilJson } from './form';
import { dariIso, hariIniIso, keJam, keMenit, labelDurasi, labelJam, labelTanggal, tambahHari } from './waktu';

/**
 * Wizard pengajuan peminjaman 3 langkah:
 *  1. Ruangan & tanggal (kalender ketersediaan berwarna)
 *  2. Durasi & jam mulai (hanya slot yang benar-benar kosong yang bisa dipilih)
 *  3. Detail & konfirmasi
 * Server tetap memvalidasi ulang seluruh aturan saat formulir dikirim.
 */
export default function wizardPeminjaman(konfig) {
    const aturan = konfig.aturan;
    const durasiAwal = Number(konfig.lama.durasi) || Math.max(aturan.durasi_min_menit, Math.min(120, aturan.durasi_maks_menit));

    return {
        langkah: 1,
        aturan,
        daftarRuangan: konfig.ruangan,
        ruangan: konfig.ruanganAwal,
        bulan: (konfig.tanggalAwal || hariIniIso()).slice(0, 7),
        labelBulan: '',
        hari: [],
        memuatBulan: false,
        galatBulan: null,
        tanggal: konfig.tanggalAwal || null,
        detail: null,
        memuatHari: false,
        galatHari: null,
        durasi: durasiAwal,
        mulai: konfig.lama.waktu_mulai || null,
        jumlahOrang: Number(konfig.lama.jumlah_orang) || 1,
        keperluan: konfig.lama.ket_keperluan || '',
        adaJaminan: false,
        setuju: Boolean(konfig.lama.setuju_sop),

        init() {
            this.muatBulan();
            if (this.tanggal) {
                this.muatHari(this.tanggal, true);
            }
            if (konfig.langkahError) {
                this.langkah = konfig.langkahError;
            }

            this.$watch('durasi', () => this.validasiMulai());
            this.$watch('ruangan', () => {
                this.tanggal = null;
                this.detail = null;
                this.mulai = null;
                this.muatBulan();
            });

            this.$el.addEventListener('berkas-dipilih', (e) => {
                if (e.detail.nama === 'jaminan') this.adaJaminan = e.detail.ada;
            });
        },

        // ------------------------------------------------------------ Ruangan

        get ruanganAktif() {
            return this.daftarRuangan.find((r) => r.slug === this.ruangan) || null;
        },

        get maksOrang() {
            return Math.min(this.ruanganAktif?.kapasitas || aturan.maks_orang, aturan.maks_orang);
        },

        url(templat) {
            return templat.replace('__RUANGAN__', encodeURIComponent(this.ruangan));
        },

        // ---------------------------------------------------------- Kalender

        async muatBulan() {
            if (!this.ruangan) return;
            this.memuatBulan = true;
            this.galatBulan = null;
            try {
                const data = await ambilJson(`${this.url(konfig.urlBulan)}?bulan=${this.bulan}`);
                this.hari = data.hari;
                this.labelBulan = data.label;
            } catch (e) {
                this.galatBulan = e.message;
            } finally {
                this.memuatBulan = false;
            }
        },

        gantiBulan(delta) {
            const [y, m] = this.bulan.split('-').map(Number);
            const d = new Date(y, m - 1 + delta, 1);
            this.bulan = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            this.muatBulan();
        },

        get bisaMundur() {
            return this.bulan > hariIniIso().slice(0, 7);
        },

        get bisaMaju() {
            return this.bulan < tambahHari(hariIniIso(), aturan.batas_hari_pemesanan).slice(0, 7);
        },

        get sel() {
            if (!this.hari.length) return [];
            const offset = (dariIso(this.hari[0].tanggal).getDay() + 6) % 7;
            return [...Array(offset).fill(null), ...this.hari];
        },

        bisaDipilih(h) {
            return Boolean(h) && ['tersedia', 'sebagian'].includes(h.status);
        },

        pilihTanggal(h) {
            if (!this.bisaDipilih(h)) return;
            this.tanggal = h.tanggal;
            this.mulai = null;
            this.muatHari(h.tanggal);
        },

        async muatHari(tanggal, pertahankanJam = false) {
            this.memuatHari = true;
            this.galatHari = null;
            try {
                this.detail = await ambilJson(`${this.url(konfig.urlHari)}?tanggal=${tanggal}`);
                if (!pertahankanJam) this.mulai = null;
                this.validasiMulai();
            } catch (e) {
                this.galatHari = e.message;
            } finally {
                this.memuatHari = false;
            }
        },

        // ---------------------------------------------------------- Jam

        get opsiDurasi() {
            const out = [];
            for (let d = aturan.durasi_min_menit; d <= aturan.durasi_maks_menit; d += 30) out.push(d);
            if (!out.includes(aturan.durasi_maks_menit)) out.push(aturan.durasi_maks_menit);
            return out;
        },

        get slot() {
            const d = this.detail;
            if (!d || !['tersedia', 'sebagian'].includes(d.status)) return [];

            const awal = keMenit(d.paling_awal);
            const tutup = keMenit(d.tutup);
            const terisi = d.terisi.map((t) => [keMenit(t.mulai), keMenit(t.selesai)]);
            const out = [];

            for (let m = awal; m + this.durasi <= tutup; m += aturan.interval_menit) {
                const s = m + this.durasi;
                out.push({ jam: keJam(m), tersedia: !terisi.some(([a, b]) => m < b && a < s) });
            }
            return out;
        },

        get slotPerJam() {
            const grup = {};
            for (const s of this.slot) {
                const jam = s.jam.slice(0, 2);
                (grup[jam] ||= []).push(s);
            }
            return Object.entries(grup).map(([jam, items]) => ({ jam, items }));
        },

        get jumlahSlotTersedia() {
            return this.slot.filter((s) => s.tersedia).length;
        },

        validasiMulai() {
            if (this.mulai && !this.slot.some((s) => s.jam === this.mulai && s.tersedia)) {
                this.mulai = null;
            }
        },

        get selesai() {
            return this.mulai ? keJam(keMenit(this.mulai) + this.durasi) : null;
        },

        // Timeline visual hari terpilih
        persen(hhmm) {
            if (!this.detail) return 0;
            const b = keMenit(this.detail.buka);
            const t = keMenit(this.detail.tutup);
            return ((keMenit(hhmm) - b) / (t - b)) * 100;
        },

        get labelTimeline() {
            if (!this.detail) return [];
            const out = [];
            for (let m = keMenit(this.detail.buka); m <= keMenit(this.detail.tutup); m += 60) out.push(keJam(m));
            return out;
        },

        // ---------------------------------------------------------- Label

        labelDurasi,
        labelJam,
        labelTanggal,

        // ---------------------------------------------------------- Langkah

        get bolehLanjut1() {
            return Boolean(this.ruangan && this.tanggal && this.detail && this.slot.some((s) => s.tersedia));
        },

        get bolehLanjut2() {
            return this.bolehLanjut1 && Boolean(this.mulai);
        },

        get bolehKirim() {
            return (
                this.bolehLanjut2 &&
                this.jumlahOrang >= 1 &&
                this.jumlahOrang <= this.maksOrang &&
                this.keperluan.trim().length >= 10 &&
                this.adaJaminan &&
                this.setuju
            );
        },

        ke(n) {
            if (n >= 2 && !this.bolehLanjut1) return;
            if (n >= 3 && !this.bolehLanjut2) return;
            this.langkah = n;
            this.$nextTick(() => this.$refs.atas?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },
    };
}
