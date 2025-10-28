<?php
session_start();
include 'db.php';
require_once 'csrf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Validate CSRF token for POST requests
check_csrf_token();

// Fetch analytics data
// Enhanced Key Metrics
$totalTickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$openTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
$closedTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'closed'")->fetchColumn();
$pendingTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'pending'")->fetchColumn();
$ticketsToday = $pdo->query("SELECT COUNT(*) FROM tickets WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$ticketsThisWeek = $pdo->query("SELECT COUNT(*) FROM tickets WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// Tickets by status
$ticketsByStatus = $pdo->query("SELECT status, COUNT(*) as count FROM tickets GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// Tickets by category
$ticketsByCategory = $pdo->query("SELECT c.name, COUNT(t.id) as count FROM categories c LEFT JOIN tickets t ON c.name = t.category GROUP BY c.id, c.name ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);

// Tickets by priority
$ticketsByPriority = $pdo->query("SELECT CASE t.priority WHEN 'low' THEN 'Low' WHEN 'medium' THEN 'Medium' WHEN 'high' THEN 'High' END as name, COUNT(t.id) as count FROM tickets t GROUP BY t.priority ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);

// Average resolution time (in hours)
$avgResolutionTime = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours FROM tickets WHERE status = 'closed'")->fetchColumn() ?? 0;

// User activity (tickets submitted per user)
$userActivity = $pdo->query("SELECT u.username, COUNT(t.id) as ticket_count FROM users u LEFT JOIN tickets t ON u.id = t.user_id GROUP BY u.id, u.username ORDER BY ticket_count DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Monthly ticket trends (last 12 months)
$monthlyTrends = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month")->fetchAll(PDO::FETCH_ASSOC);

// SLA compliance
$slaCompliance = $pdo->query("
    SELECT
        COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) <= s.response_time_hours THEN 1 END) as compliant_responses,
        COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) <= s.resolution_time_hours THEN 1 END) as compliant_resolutions,
        COUNT(*) as total_tickets
    FROM tickets t
    JOIN categories c ON t.category = c.name
    JOIN priorities p ON p.name = t.priority
    JOIN sla_rules s ON s.category_id = c.id AND s.priority_id = p.id
    WHERE t.status IN ('closed')
")->fetch(PDO::FETCH_ASSOC) ?? ['compliant_responses' => 0, 'compliant_resolutions' => 0, 'total_tickets' => 0];

// SLA Compliance Trends (last 6 months)
$slaTrends = $pdo->query("
    SELECT
        DATE_FORMAT(t.created_at, '%Y-%m') as month,
        COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) <= s.resolution_time_hours THEN 1 END) * 100.0 / COUNT(*) as compliance_rate
    FROM tickets t
    JOIN categories c ON t.category = c.name
    JOIN priorities p ON p.name = t.priority
    JOIN sla_rules s ON s.category_id = c.id AND s.priority_id = p.id
    WHERE t.status = 'closed' AND t.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);

// User Activity Over Time (last 6 months)
$userActivityOverTime = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(DISTINCT user_id) as active_users
    FROM tickets
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);

// Resolution Time Distribution
$resolutionTimeDistribution = $pdo->query("
    SELECT
        CASE
            WHEN TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 1 THEN '< 1 hour'
            WHEN TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 4 THEN '1-4 hours'
            WHEN TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 24 THEN '4-24 hours'
            WHEN TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 168 THEN '1-7 days'
            ELSE '> 7 days'
        END as time_range,
        COUNT(*) as count
    FROM tickets
    WHERE status = 'closed'
    GROUP BY time_range
    ORDER BY
        CASE time_range
            WHEN '< 1 hour' THEN 1
            WHEN '1-4 hours' THEN 2
            WHEN '4-24 hours' THEN 3
            WHEN '1-7 days' THEN 4
            ELSE 5
        END
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data and Analytics - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-role="admin">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2><span class="notification-icon type-system"></span> Data and Analytics</h2>

        <!-- Enhanced Key Metrics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="analytics-metrics-grid">
                    <div class="metric-card total-tickets">
                        <div class="metric-icon">📊</div>
                        <div class="metric-value"><?php echo $totalTickets; ?></div>
                        <div class="metric-label">Total Tickets</div>
                    </div>
                    <div class="metric-card open-tickets">
                        <div class="metric-icon">🔓</div>
                        <div class="metric-value"><?php echo $openTickets; ?></div>
                        <div class="metric-label">Open Tickets</div>
                    </div>
                    <div class="metric-card closed-tickets">
                        <div class="metric-icon">🔒</div>
                        <div class="metric-value"><?php echo $closedTickets; ?></div>
                        <div class="metric-label">Closed Tickets</div>
                    </div>
                    <div class="metric-card pending-tickets">
                        <div class="metric-icon">⏳</div>
                        <div class="metric-value"><?php echo $pendingTickets; ?></div>
                        <div class="metric-label">Pending Tickets</div>
                    </div>
                    <div class="metric-card today-tickets">
                        <div class="metric-icon">📅</div>
                        <div class="metric-value"><?php echo $ticketsToday; ?></div>
                        <div class="metric-label">Tickets Today</div>
                    </div>
                    <div class="metric-card week-tickets">
                        <div class="metric-icon">📈</div>
                        <div class="metric-value"><?php echo $ticketsThisWeek; ?></div>
                        <div class="metric-label">This Week</div>
                    </div>
                    <div class="metric-card active-users">
                        <div class="metric-icon">👥</div>
                        <div class="metric-value"><?php echo $activeUsers; ?></div>
                        <div class="metric-label">Active Users (30d)</div>
                    </div>
                    <div class="metric-card avg-resolution">
                        <div class="metric-icon">⏱️</div>
                        <div class="metric-value"><?php echo number_format($avgResolutionTime, 1); ?>h</div>
                        <div class="metric-label">Avg Resolution</div>
                    </div>
                    <div class="metric-card sla-response">
                        <div class="metric-icon">✅</div>
                        <div class="metric-value"><?php echo $slaCompliance['total_tickets'] > 0 ? round(($slaCompliance['compliant_responses'] / $slaCompliance['total_tickets']) * 100, 1) : 0; ?>%</div>
                        <div class="metric-label">SLA Response</div>
                    </div>
                    <div class="metric-card sla-resolution">
                        <div class="metric-icon">🎯</div>
                        <div class="metric-value"><?php echo $slaCompliance['total_tickets'] > 0 ? round(($slaCompliance['compliant_resolutions'] / $slaCompliance['total_tickets']) * 100, 1) : 0; ?>%</div>
                        <div class="metric-label">SLA Resolution</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Charts Section -->
        <div class="row mb-4">
            <!-- Tickets by Status -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h5><span class="chart-icon">📊</span> Tickets by Status</h5>
                    </div>
                    <div class="analytics-card-body">
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tickets by Category -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h5><span class="chart-icon">📂</span> Tickets by Category</h5>
                    </div>
                    <div class="analytics-card-body">
                        <canvas id="categoryChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tickets by Priority -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h5><span class="chart-icon">⚡</span> Tickets by Priority</h5>
                    </div>
                    <div class="analytics-card-body">
                        <canvas id="priorityChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Monthly Trends -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h5><span class="chart-icon">📈</span> Monthly Ticket Trends</h5>
                    </div>
                    <div class="analytics-card-body">
                        <canvas id="trendsChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- SLA Compliance Trends -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h5><span class="chart-icon">🎯</span> SLA Compliance Trends</h5>
                    </div>
                    <div class="analytics-card-body">
                        <canvas id="slaTrendsChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- User Activity Over Time -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h5><span class="chart-icon">👥</span> User Activity Over Time</h5>
                    </div>
                    <div class="analytics-card-body">
                        <canvas id="userActivityChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Resolution Time Distribution -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h5><span class="chart-icon">⏱️</span> Resolution Time Distribution</h5>
                    </div>
                    <div class="analytics-card-body">
                        <canvas id="resolutionTimeChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Users by Ticket Submission -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Top Users by Ticket Submission</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Tickets Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userActivity as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo $user['ticket_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        // Enhanced Chart Configurations
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        };

        // Tickets by Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?php echo json_encode($ticketsByStatus); ?>;
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: {
                        ...chartOptions.plugins.legend,
                        position: 'right'
                    }
                }
            }
        });

        // Tickets by Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($ticketsByCategory); ?>;
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryData.map(item => item.name),
                datasets: [{
                    label: 'Tickets',
                    data: categoryData.map(item => item.count),
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Tickets by Priority Chart
        const priorityCtx = document.getElementById('priorityChart').getContext('2d');
        const priorityData = <?php echo json_encode($ticketsByPriority); ?>;
        new Chart(priorityCtx, {
            type: 'doughnut',
            data: {
                labels: priorityData.map(item => item.name),
                datasets: [{
                    data: priorityData.map(item => item.count),
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                ...chartOptions,
                cutout: '60%'
            }
        });

        // Monthly Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        const trendsData = <?php echo json_encode($monthlyTrends); ?>;
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: trendsData.map(item => item.month),
                datasets: [{
                    label: 'Tickets Created',
                    data: trendsData.map(item => item.count),
                    borderColor: '#FF6384',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FF6384',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // SLA Compliance Trends Chart
        const slaTrendsCtx = document.getElementById('slaTrendsChart').getContext('2d');
        const slaTrendsData = <?php echo json_encode($slaTrends); ?>;
        new Chart(slaTrendsCtx, {
            type: 'line',
            data: {
                labels: slaTrendsData.map(item => item.month),
                datasets: [{
                    label: 'SLA Compliance %',
                    data: slaTrendsData.map(item => parseFloat(item.compliance_rate).toFixed(1)),
                    borderColor: '#4BC0C0',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4BC0C0',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // User Activity Over Time Chart
        const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
        const userActivityData = <?php echo json_encode($userActivityOverTime); ?>;
        new Chart(userActivityCtx, {
            type: 'bar',
            data: {
                labels: userActivityData.map(item => item.month),
                datasets: [{
                    label: 'Active Users',
                    data: userActivityData.map(item => item.active_users),
                    backgroundColor: 'rgba(153, 102, 255, 0.8)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Resolution Time Distribution Chart
        const resolutionTimeCtx = document.getElementById('resolutionTimeChart').getContext('2d');
        const resolutionTimeData = <?php echo json_encode($resolutionTimeDistribution); ?>;
        new Chart(resolutionTimeCtx, {
            type: 'bar',
            data: {
                labels: resolutionTimeData.map(item => item.time_range),
                datasets: [{
                    label: 'Tickets',
                    data: resolutionTimeData.map(item => item.count),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 205, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
