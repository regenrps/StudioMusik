let urutan = 0;

export function storeToast() {
    return {
        items: [],

        tampil(pesan, jenis = 'info', durasi = 5000) {
            const id = ++urutan;
            this.items.push({ id, pesan, jenis, tampil: true });
            if (durasi > 0) {
                setTimeout(() => this.tutup(id), durasi);
            }
        },

        tutup(id) {
            const item = this.items.find((t) => t.id === id);
            if (item) {
                item.tampil = false;
                setTimeout(() => (this.items = this.items.filter((t) => t.id !== id)), 300);
            }
        },
    };
}
