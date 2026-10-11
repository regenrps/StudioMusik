import { ambilJson } from './form';
import { dariIso, hariIniIso, keMenit, labelJam, seninDari, tambahHari } from './waktu';

const NAMA_HARI = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

/**
 * Kalender mingguan (jadwal publik & kalender admin).
 * Desktop: grid jam × hari. Ponsel: daftar per hari.
 */
export default function jadwalMingguan(konfig) {
    return {
        awal: seninDari(konfig.tanggal || hariIniIso()),
        ruangan: konfig.ruangan || '',
        aturan: konfig.aturan,
        events: [],
        memuat: false,
        galat: null,
        tampilan: window.matchMedia('(min-width: 768px)').matches ? 'minggu' : 'daftar',
        dipilih: null,

        init() {
            this.muat();
            this.$watch('ruangan', () => this.muat());
        },

        get akhir() {
            return tambahHari(this.awal, 6);
        },

        get hariMinggu() {
            const hariIni = hariIniIso();
            return Array.from({ length: 7 }, (_, i) => {
                const iso = tambahHari(this.awal, i);
                return {
                    iso,
                    nama: NAMA_HARI[i],
                    tgl: dariIso(iso).getDate(),
                    hariIni: iso === hariIni,
                    lewat: iso < hariIni,
                    operasional: this.aturan.hari_operasional.includes(i + 1),
                };
            });
        },

        get hariTampil() {
            // Sembunyikan hari non-operasional yang tidak memiliki event.
            return this.hariMinggu.filter((h) => h.operasional || this.eventsHari(h.iso).length);
        },

        get labelRentang() {
            const a = dariIso(this.awal);
            const b = dariIso(this.akhir);
            const opsi = { day: 'numeric', month: 'short' };
            return `${a.toLocaleDateString('id-ID', opsi)} – ${b.toLocaleDateString('id-ID', { ...opsi, year: 'numeric' })}`;
        },

        get rentangMenit() {
            let awal = keMenit(this.aturan.jam_buka);
            let akhir = keMenit(this.aturan.jam_tutup);
            for (const e of this.events) {
                awal = Math.min(awal, keMenit(e.mulai));
                akhir = Math.max(akhir, keMenit(e.selesai));
            }
            awal = Math.floor(awal / 60) * 60;
            akhir = Math.ceil(akhir / 60) * 60;
            return [awal, Math.max(akhir, awal + 60)];
        },

        get labelJamGrid() {
            const [a, b] = this.rentangMenit;
            const out = [];
            for (let m = a; m < b; m += 60) out.push(`${String(m / 60).padStart(2, '0')}.00`);
            return out;
        },

        gaya(ev) {
            const [a, b] = this.rentangMenit;
            const total = b - a;
            const top = ((keMenit(ev.mulai) - a) / total) * 100;
            const tinggi = ((keMenit(ev.selesai) - keMenit(ev.mulai)) / total) * 100;
            return `top:${top}%;height:${tinggi}%`;
        },

        eventsHari(iso) {
            return this.events.filter((e) => e.tanggal === iso);
        },

        get jumlahEvent() {
            return this.events.filter((e) => e.jenis === 'booking').length;
        },

        async muat() {
            this.memuat = true;
            this.galat = null;
            try {
                const params = new URLSearchParams({ mulai: this.awal, selesai: this.akhir });
                if (this.ruangan) params.set('ruangan', this.ruangan);
                const data = await ambilJson(`${konfig.url}?${params}`);
                this.events = data.events;
                if (data.aturan) this.aturan = data.aturan;
            } catch (e) {
                this.galat = e.message;
            } finally {
                this.memuat = false;
            }
        },

        geser(minggu) {
            this.awal = tambahHari(this.awal, minggu * 7);
            this.dipilih = null;
            this.muat();
        },

        mingguIni() {
            this.awal = seninDari(hariIniIso());
            this.muat();
        },

        get diMingguIni() {
            return this.awal === seninDari(hariIniIso());
        },

        labelJam,

        warna(ev) {
            const peta = {
                amber: 'border-amber-400 bg-amber-50 text-amber-900 dark:border-amber-500/60 dark:bg-amber-500/15 dark:text-amber-100',
                sky: 'border-sky-400 bg-sky-50 text-sky-900 dark:border-sky-500/60 dark:bg-sky-500/15 dark:text-sky-100',
                violet: 'border-violet-400 bg-violet-50 text-violet-900 dark:border-violet-500/60 dark:bg-violet-500/15 dark:text-violet-100',
                emerald: 'border-emerald-400 bg-emerald-50 text-emerald-900 dark:border-emerald-500/60 dark:bg-emerald-500/15 dark:text-emerald-100',
                orange: 'border-orange-400 bg-orange-50 text-orange-900 dark:border-orange-500/60 dark:bg-orange-500/15 dark:text-orange-100',
                zinc: 'border-zinc-300 bg-zinc-100 text-zinc-600 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
            };
            if (ev.jenis === 'tutup') {
                return `${peta.zinc} bg-[repeating-linear-gradient(135deg,transparent,transparent_6px,rgb(0_0_0/0.04)_6px,rgb(0_0_0/0.04)_12px)]`;
            }
            return peta[ev.tone] || peta.sky;
        },
    };
}
