import Chart from 'chart.js/auto';
//chart هي الآداء للمدير العام واللمدقق فقط
document.addEventListener("DOMContentLoaded", function() {

    // 1. شارت المدير العام (CISO Trend)
    const cisoTrendElement = document.getElementById('cisoTrendChart');
    if (cisoTrendElement) {
        const labels = JSON.parse(cisoTrendElement.dataset.labels || '[]');
        const completedData = JSON.parse(cisoTrendElement.dataset.completed || '[]');
        const createdData = JSON.parse(cisoTrendElement.dataset.created || '[]');

        new Chart(cisoTrendElement.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'المهام المنجزة',
                        data: completedData,
                        borderColor: '#007B69',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'المهام الجديدة',
                        data: createdData,
                        borderColor: '#973D4B',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // 2. شارت المدقق   (Trend Chart)
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        const labels = JSON.parse(trendCtx.dataset.labels || '[]');
        const createdData = JSON.parse(trendCtx.dataset.created || '[]');
        const completedData = JSON.parse(trendCtx.dataset.completed || '[]');

        new Chart(trendCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'جديدة',
                        data: createdData,
                        borderColor: '#973D4B',
                        backgroundColor: 'rgba(13, 110, 253, 0.05)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4
                    },
                    {
                        label: 'المهام المنجزة',
                        data: completedData,
                        borderColor: '#007B69',
                        backgroundColor: 'rgba(25, 135, 84, 0.05)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});


