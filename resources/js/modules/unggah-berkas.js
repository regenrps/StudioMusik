/**
 * Input berkas dengan seret-lepas, pratinjau, dan kompresi gambar di
 * perangkat (foto kamera ponsel 5–10 MB diperkecil sebelum diunggah).
 */
export default function unggahBerkas({ nama, maksKb = 5120, kompres = true }) {
    return {
        berkas: null,
        pratinjau: null,
        seret: false,
        galat: null,
        memproses: false,

        pilih(event) {
            this.proses(event.target.files[0]);
        },

        jatuh(event) {
            this.seret = false;
            const file = event.dataTransfer?.files?.[0];
            if (file) this.proses(file);
        },

        async proses(file) {
            this.galat = null;
            if (!file) {
                this.hapus();
                return;
            }

            let hasil = file;
            if (kompres && /^image\/(jpeg|png|webp)$/.test(file.type) && file.size > 900 * 1024) {
                this.memproses = true;
                try {
                    hasil = await kompresGambar(file);
                } catch {
                    hasil = file;
                }
                this.memproses = false;
            }

            if (hasil.size > maksKb * 1024) {
                this.galat = `Ukuran berkas maksimal ${ukuran(maksKb * 1024)} (berkas Anda ${ukuran(hasil.size)}).`;
                this.hapus();
                return;
            }

            try {
                const dt = new DataTransfer();
                dt.items.add(hasil);
                this.$refs.input.files = dt.files;
            } catch {
                /* Browser lama: biarkan berkas asli di input */
            }

            if (this.pratinjau) URL.revokeObjectURL(this.pratinjau);
            this.berkas = { nama: hasil.name, ukuran: ukuran(hasil.size), gambar: hasil.type.startsWith('image/') };
            this.pratinjau = this.berkas.gambar ? URL.createObjectURL(hasil) : null;
            this.$dispatch('berkas-dipilih', { nama, ada: true });
        },

        hapus() {
            if (this.$refs.input) this.$refs.input.value = '';
            if (this.pratinjau) URL.revokeObjectURL(this.pratinjau);
            this.berkas = null;
            this.pratinjau = null;
            this.$dispatch('berkas-dipilih', { nama, ada: false });
        },
    };
}

function ukuran(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} MB`;
}

async function kompresGambar(file, sisiMaks = 1920, kualitas = 0.82) {
    const bitmap = await createImageBitmap(file);
    const skala = Math.min(1, sisiMaks / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * skala);
    canvas.height = Math.round(bitmap.height * skala);
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close?.();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', kualitas));
    if (!blob || blob.size >= file.size) return file;

    return new File([blob], `${file.name.replace(/\.[^.]+$/, '')}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
}
