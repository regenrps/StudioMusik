/** Teks hitung mundur yang ramah, mis. "dimulai dalam 2 jam 15 menit". */
export default function hitungMundur({ mulai, selesai }) {
    return {
        teks: '',
        fase: 'nanti',

        init() {
            this.hitung();
            const id = setInterval(() => this.hitung(), 30_000);
            this.$cleanup?.(() => clearInterval(id));
        },

        hitung() {
            const now = Date.now();
            const a = new Date(mulai).getTime();
            const b = new Date(selesai).getTime();

            if (now < a) {
                this.fase = 'nanti';
                this.teks = `Dimulai ${durasi(a - now)} lagi`;
            } else if (now < b) {
                this.fase = 'sekarang';
                this.teks = `Sedang berjalan · berakhir ${durasi(b - now)} lagi`;
            } else {
                this.fase = 'lewat';
                this.teks = 'Jadwal telah berakhir';
            }
        },
    };
}

function durasi(ms) {
    const menit = Math.max(1, Math.round(ms / 60000));
    const hari = Math.floor(menit / 1440);
    const jam = Math.floor((menit % 1440) / 60);
    const sisa = menit % 60;

    if (hari > 0) return `${hari} hari${jam ? ` ${jam} jam` : ''}`;
    if (jam > 0) return `${jam} jam${sisa ? ` ${sisa} menit` : ''}`;
    return `${sisa} menit`;
}
