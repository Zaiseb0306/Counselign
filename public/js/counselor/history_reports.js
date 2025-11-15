let trendChart;
let pieChart;

// Initialize charts on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Set default month to current month
    const today = new Date();
    const defaultMonth = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
    document.getElementById('monthFilter').value = defaultMonth;
    
    // Load initial report
    loadHistoricalReport();
    
    // Add window resize event listener for chart responsiveness
    window.addEventListener('resize', function() {
        if (trendChart) {
            trendChart.resize();
        }
        if (pieChart) {
            pieChart.resize();
        }
    });
});

function initializeCharts() {
    // Initialize Trend Chart
    const trendCtx = document.getElementById('appointmentTrendChart').getContext('2d');
    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Completed',
                    borderColor: '#0d6efd',
                    backgroundColor: '#0d6efd',
                    fill: false,
                    tension: 0.4,
                    data: []
                },
                {
                    label: 'Approved',
                    borderColor: '#198754',
                    backgroundColor: '#198754',
                    fill: false,
                    tension: 0.4,
                    data: []
                },
                {
                    label: 'Rejected',
                    borderColor: '#dc3545',
                    backgroundColor: '#dc3545',
                    fill: false,
                    tension: 0.4,
                    data: []
                },
                {
                    label: 'Pending',
                    borderColor: '#ffc107',
                    backgroundColor: '#ffc107',
                    fill: false,
                    tension: 0.4,
                    data: []
                },
                {
                    label: 'Cancelled',
                    borderColor: '#6c757d',
                    backgroundColor: '#6c757d',
                    fill: false,
                    tension: 0.4,
                    data: []
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Appointment Trends',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // Initialize Pie Chart
    const pieCtx = document.getElementById('statusPieChart').getContext('2d');
    pieChart = new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Approved', 'Rejected', 'Pending', 'Cancelled'],
            datasets: [{
                data: [0, 0, 0, 0, 0],
                backgroundColor: ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6c757d'],
                borderWidth: 0,
                cutout: '65%'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((acc, curr) => acc + curr, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                            return `${context.label}: ${value} (${percentage})`;
                        }
                    }
                }
            }
        }
    });
}

function loadHistoricalReport() {
    const selectedMonth = document.getElementById('monthFilter').value;
    const reportType = document.getElementById('reportTypeFilter').value;
    
    if (!selectedMonth) {
        alert('Please select a month');
        return;
    }

    // Show loading state
    document.querySelectorAll('.stat-card h3').forEach(el => el.textContent = 'Loading...');
    
    // Fetch historical data
    fetch((window.BASE_URL || '/') + `counselor/history-reports/historical-data?month=${selectedMonth}&type=${reportType}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            updateCharts(data);
            updateStatistics(data);
        })
        .catch(error => {
            console.error('Error fetching report data:', error);
            alert('Error loading report data: ' + error.message);
            resetStatistics();
        });
}

function updateCharts(data) {
    const reportType = document.getElementById('reportTypeFilter').value;
    
    // Update trend chart based on report type
    if (reportType === 'monthly') {
        trendChart.options.scales.y = {
            beginAtZero: true,
            max: 100,
            ticks: {
                stepSize: 20,
                callback: function(value) {
                    return value.toFixed(0);
                }
            }
        };
    } else if (reportType === 'weekly') {
        trendChart.options.scales.y = {
            beginAtZero: true,
            max: 40,
            ticks: {
                stepSize: 10,
                callback: function(value) {
                    return value.toFixed(0);
                }
            }
        };
    } else if (reportType === 'yearly') {
        trendChart.options.scales.y = {
            beginAtZero: true,
            max: 100,
            ticks: {
                stepSize: 20,
                callback: function(value) {
                    return value.toFixed(0);
                }
            }
        };
    } else {
        trendChart.options.scales.y = {
            beginAtZero: true,
            max: 8,
            ticks: {
                stepSize: 2,
                callback: function(value) {
                    return value.toFixed(0);
                }
            }
        };
    }

    // Update chart data
    trendChart.data.labels = data.labels || [];
    trendChart.data.datasets[0].data = data.completed || [];
    trendChart.data.datasets[1].data = data.approved || [];
    trendChart.data.datasets[2].data = data.rejected || [];
    trendChart.data.datasets[3].data = data.pending || [];
    trendChart.data.datasets[4].data = data.cancelled || [];
    
    // Update chart title
    const monthName = new Date(document.getElementById('monthFilter').value + '-01')
        .toLocaleString('default', { month: 'long', year: 'numeric' });
    trendChart.options.plugins.title.text = `Appointment Trends - ${monthName} (${reportType.charAt(0).toUpperCase() + reportType.slice(1)})`;
    
    trendChart.update();

    // Update pie chart
    const pieData = [
        data.totalCompleted || 0,
        data.totalApproved || 0,
        data.totalRejected || 0,
        data.totalPending || 0,
        data.totalCancelled || 0
    ];
    pieChart.data.datasets[0].data = pieData;
    
    const total = pieData.reduce((acc, curr) => acc + curr, 0);
    pieChart.options.plugins.tooltip.callbacks.label = function(context) {
        const value = context.raw;
        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
        return `${context.label}: ${value} (${percentage})`;
    };
    
    pieChart.update();
}

function updateStatistics(data) {
    document.getElementById('completedCount').textContent = data.totalCompleted || 0;
    document.getElementById('approvedCount').textContent = data.totalApproved || 0;
    document.getElementById('rejectedCount').textContent = data.totalRejected || 0;
    document.getElementById('pendingCount').textContent = data.totalPending || 0;
    document.getElementById('cancelledCount').textContent = data.totalCancelled || 0;
}

function resetStatistics() {
    document.getElementById('completedCount').textContent = '0';
    document.getElementById('approvedCount').textContent = '0';
    document.getElementById('rejectedCount').textContent = '0';
    document.getElementById('pendingCount').textContent = '0';
    document.getElementById('cancelledCount').textContent = '0';
    
    if (trendChart && pieChart) {
        trendChart.data.labels = [];
        trendChart.data.datasets.forEach(dataset => dataset.data = []);
        trendChart.update();
        
        pieChart.data.datasets[0].data = [0, 0, 0, 0, 0];
        pieChart.update();
    }
} 