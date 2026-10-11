/**
 * Grafik dasbor admin. Chart.js dimuat terpisah (code-splitting) hanya di
 * halaman yang membutuhkannya, dan warnanya mengikuti tema terang/gelap.
 */
const WARNA = {
    amber: '#f59e0b',
    sky: '#0ea5e9',
    violet: '#8b5cf6',
    emerald: '#10b981',
    rose: '#f43f5e',
    zinc: '#a1a1aa',
    orange: '#f97316',
};

export default function grafikDashboard(data) {
    let tren = null;
    let komposisi = null;

    return {
        async init() {
            const { Chart, registerables } = await import('chart.js');
            Chart.register(...registerables);
            this.gambar(Chart);
            window.addEventListener('tema-berubah', () => this.gambar(Chart));
        },

        gambar(Chart) {
            const gelap = document.documentElement.classList.contains('dark');
            const teks = gelap ? '#a1a1aa' : '#71717a';
            const garis = gelap ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
            Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
            Chart.defaults.color = teks;

            tren?.destroy();
            komposisi?.destroy();

            if (this.$refs.tren) {
                tren = new Chart(this.$refs.tren, {
                    type: 'bar',
                    data: {
                        labels: data.label,
                        datasets: [
                            { label: 'Terlaksana/terjadwal', data: data.terlaksana, backgroundColor: '#7c5cf5', borderRadius: 4, maxBarThickness: 18 },
                            { label: 'Batal/ditolak/tidak hadir', data: data.batal, backgroundColor: gelap ? '#3f3f46' : '#d4d4d8', borderRadius: 4, maxBarThickness: 18 },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } },
                        scales: {
                            x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
                            y: { stacked: true, beginAtZero: true, grid: { color: garis }, ticks: { precision: 0 } },
                        },
                    },
                });
            }

            if (this.$refs.komposisi && data.status.length) {
                komposisi = new Chart(this.$refs.komposisi, {
                    type: 'doughnut',
                    data: {
                        labels: data.status.map((s) => s.label),
                        datasets: [{
                            data: data.status.map((s) => s.jumlah),
                            backgroundColor: data.status.map((s) => WARNA[s.tone] || WARNA.zinc),
                            borderColor: gelap ? '#18181b' : '#ffffff',
                            borderWidth: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } },
                    },
                });
            }
        },
    };
}
