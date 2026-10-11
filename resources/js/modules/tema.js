/**
 * Tema terang / gelap / ikuti sistem. Nilai awal sudah diterapkan oleh
 * skrip inline di <head> agar tidak ada kedipan (FOUC).
 */
export function storeTema() {
    const media = window.matchMedia('(prefers-color-scheme: dark)');

    return {
        pilihan: localStorage.getItem('tema') || 'sistem',

        get gelap() {
            return this.pilihan === 'gelap' || (this.pilihan === 'sistem' && media.matches);
        },

        init() {
            media.addEventListener('change', () => this.terapkan());
        },

        atur(pilihan) {
            this.pilihan = pilihan;
            localStorage.setItem('tema', pilihan);
            this.terapkan();
        },

        alihkan() {
            this.atur(this.gelap ? 'terang' : 'gelap');
        },

        terapkan() {
            document.documentElement.classList.toggle('dark', this.gelap);
            window.dispatchEvent(new CustomEvent('tema-berubah', { detail: { gelap: this.gelap } }));
        },
    };
}
