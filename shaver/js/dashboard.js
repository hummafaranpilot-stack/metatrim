/**
 * Unified Dashboard JavaScript
 * Combines metrics from both Shaving Analytics and BuyGoods Analytics
 */

// Initialize dashboard on load
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    initializeChart();
});

// Load all dashboard data
async function loadDashboardData() {
    await Promise.all([
        loadBuyGoodsStats(),
        loadShavingStats(),
        loadRecentOrders(),
        loadRecentTraffic()
    ]);
}

// Load BuyGoods statistics
async function loadBuyGoodsStats() {
    try {
        const response = await apiRequest(BUYGOODS_API_URL, 'getDashboardStats');

        if (response.success && response.data) {
            const stats = response.data;

            // Update main KPIs
            document.getElementById('totalRevenue').textContent = formatCurrency(stats.totalRevenue || 0);
            document.getElementById('totalOrders').textContent = formatNumber(stats.totalOrders || 0);
            document.getElementById('netProfit').textContent = formatCurrency(stats.netProfit || 0);

            // Update BuyGoods quick stats
            document.getElementById('todayOrders').textContent = formatNumber(stats.todayOrders || 0);
            document.getElementById('todayRevenue').textContent = formatCurrency(stats.todayRevenue || 0);
            document.getElementById('pendingRefunds').textContent = formatNumber(stats.pendingRefunds || 0);
            document.getElementById('activeSubscriptions').textContent = formatNumber(stats.activeSubscriptions || 0);
        }
    } catch (error) {
        console.error('Error loading BuyGoods stats:', error);
    }
}

// Load Shaving Analytics statistics
async function loadShavingStats() {
    try {
        const response = await apiRequest(SHAVING_API_URL, 'getAnalytics');

        if (response.success && response.stats) {
            const stats = response.stats;

            // Update traffic KPI
            document.getElementById('totalTraffic').textContent = formatNumber(stats.totalVisits || 0);

            // Update Shaving quick stats
            document.getElementById('todayTraffic').textContent = formatNumber(stats.todayVisits || 0);
            document.getElementById('shavedVisits').textContent = formatNumber(stats.shavedVisits || 0);
            document.getElementById('checkoutRate').textContent = ((stats.checkoutRate || 0) * 100).toFixed(1) + '%';
        }

        // Load active sessions count
        const sessionsResponse = await apiRequest(SHAVING_API_URL, 'getSessions');
        if (sessionsResponse.success && sessionsResponse.sessions) {
            document.getElementById('activeSessions').textContent = formatNumber(sessionsResponse.sessions.length);
        }
    } catch (error) {
        console.error('Error loading Shaving stats:', error);
    }
}

// Load recent orders
async function loadRecentOrders() {
    try {
        const response = await apiRequest(BUYGOODS_API_URL, 'getRecentOrders', { limit: 5 });

        if (response.success && response.data) {
            const tbody = document.getElementById('recentOrdersTable');
            tbody.innerHTML = '';

            if (response.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No orders yet</td></tr>';
                return;
            }

            response.data.forEach(order => {
                const statusClass = order.status === 'completed' ? 'success' :
                                  order.status === 'refunded' ? 'warning' :
                                  order.status === 'chargeback' ? 'danger' : 'info';

                const row = `
                    <tr>
                        <td>#${order.order_id}</td>
                        <td>${order.product_name || 'N/A'}</td>
                        <td>${formatCurrency(order.amount)}</td>
                        <td><span class="badge badge-${statusClass}">${order.status}</span></td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
    } catch (error) {
        console.error('Error loading recent orders:', error);
        document.getElementById('recentOrdersTable').innerHTML =
            '<tr><td colspan="4" class="text-center text-danger">Error loading orders</td></tr>';
    }
}

// Load recent traffic
async function loadRecentTraffic() {
    try {
        const response = await apiRequest(SHAVING_API_URL, 'getTrafficLog', { limit: 5 });

        if (response.success && response.traffic) {
            const tbody = document.getElementById('recentTrafficTable');
            tbody.innerHTML = '';

            if (response.traffic.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No traffic yet</td></tr>';
                return;
            }

            response.traffic.forEach(visit => {
                const row = `
                    <tr>
                        <td>${formatTimeAgo(visit.timestamp)}</td>
                        <td>${visit.affId || 'Direct'}</td>
                        <td title="${visit.landingPage}">${truncateUrl(visit.landingPage)}</td>
                        <td>${visit.country || 'Unknown'}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
    } catch (error) {
        console.error('Error loading recent traffic:', error);
        document.getElementById('recentTrafficTable').innerHTML =
            '<tr><td colspan="4" class="text-center text-danger">Error loading traffic</td></tr>';
    }
}

// Truncate URL for display
function truncateUrl(url, maxLength = 30) {
    if (!url) return 'N/A';
    if (url.length <= maxLength) return url;
    return url.substring(0, maxLength) + '...';
}

// Initialize revenue & traffic chart
async function initializeChart() {
    try {
        // Load chart data from both APIs
        const [revenueData, trafficData] = await Promise.all([
            apiRequest(BUYGOODS_API_URL, 'getRevenueChart', { days: 30 }),
            apiRequest(SHAVING_API_URL, 'getTrafficChart', { days: 30 })
        ]);

        const ctx = document.getElementById('revenueTrafficChart');
        if (!ctx) return;

        // Check if API responses are valid
        if (!revenueData.success || !revenueData.data) {
            console.error('Revenue chart data unavailable:', revenueData.error || 'No data');
            ctx.parentElement.innerHTML = '<div class="alert alert-warning">Revenue data unavailable. Please refresh.</div>';
            return;
        }

        if (!trafficData.success || !trafficData.data) {
            console.error('Traffic chart data unavailable:', trafficData.error || 'No data');
            ctx.parentElement.innerHTML = '<div class="alert alert-warning">Traffic data unavailable. Please refresh.</div>';
            return;
        }

        // Prepare chart data
        const labels = [];
        const revenueValues = [];
        const trafficValues = [];

        // Generate last 30 days labels
        for (let i = 29; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));

            // Find matching data points
            const dateStr = date.toISOString().split('T')[0];

            const revenuePoint = revenueData.data.find(d => d.date === dateStr);
            revenueValues.push(revenuePoint ? parseFloat(revenuePoint.revenue || 0) : 0);

            const trafficPoint = trafficData.data.find(d => d.date === dateStr);
            trafficValues.push(trafficPoint ? parseInt(trafficPoint.visits || 0) : 0);
        }

        // Create dual-axis chart
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: revenueValues,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        yAxisID: 'y',
                        tension: 0.4
                    },
                    {
                        label: 'Traffic (Visits)',
                        data: trafficValues,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Traffic (Visits)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error initializing chart:', error);
        const ctx = document.getElementById('revenueTrafficChart');
        if (ctx) {
            ctx.parentElement.innerHTML = '<div class="alert alert-danger">Failed to load chart. Please refresh the page.</div>';
        }
    }
}

// Auto-refresh dashboard every 60 seconds
setInterval(loadDashboardData, 60000);
