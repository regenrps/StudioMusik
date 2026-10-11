import { ambilJson } from './form';

/** Dropdown lonceng notifikasi dengan pembaruan berkala ringan. */
export default function notifikasi({ url, urlBacaSemua, belum = 0 }) {
    return {
        buka: false,
        items: [],
        belum,
        memuat: false,
        dimuat: false,

        init() {
            setInterval(() => this.segarkan(true), 60_000);
        },

        async alihkan() {
            this.buka = !this.buka;
            if (this.buka) await this.segarkan();
        },

        async segarkan(latar = false) {
            if (latar && document.hidden) return;
            if (!latar) this.memuat = !this.dimuat;
            try {
                const data = await ambilJson(url);
                const sebelumnya = this.belum;
                this.items = data.items;
                this.belum = data.belum_dibaca;
                this.dimuat = true;
                if (latar && data.belum_dibaca > sebelumnya && data.items[0]) {
                    window.toast(data.items[0].judul, 'info');
                }
            } catch {
                /* senyap */
            } finally {
                this.memuat = false;
            }
        },

        async bacaSemua() {
            try {
                await ambilJson(urlBacaSemua, { method: 'POST' });
                this.belum = 0;
                this.items = this.items.map((i) => ({ ...i, dibaca: true }));
            } catch (e) {
                window.toast(e.message, 'error');
            }
        },
    };
}
