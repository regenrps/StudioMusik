let kunci = 0;

/** Penyusun formulir dinamis layanan jasa musik (admin). */
export default function fieldBuilder(awal = []) {
    return {
        fields: awal.map((f) => ({ ...f, _k: ++kunci })),

        tambah(jenis = 'text') {
            this.fields.push({ _k: ++kunci, id: null, nama_field: '', jenis_field: jenis, is_required: true, bantuan: '' });
            this.$nextTick(() => {
                const input = this.$el.querySelectorAll('[data-nama-field]');
                input[input.length - 1]?.focus();
            });
        },

        hapus(i) {
            this.fields.splice(i, 1);
        },

        geser(i, arah) {
            const j = i + arah;
            if (j < 0 || j >= this.fields.length) return;
            const [item] = this.fields.splice(i, 1);
            this.fields.splice(j, 0, item);
        },
    };
}
