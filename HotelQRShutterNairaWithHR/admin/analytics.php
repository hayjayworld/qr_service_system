<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAuth();

// Set date ranges
$today = date('Y-m-d');
$firstDayOfMonth = date('Y-m-01');
$lastDayOfMonth = date('Y-m-t');
$startDate = null;
$endDate = null;

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
} else {
    $startDate = $firstDayOfMonth;
    $endDate = $lastDayOfMonth;
}
// Get analytics data
$dailySales = getDailySales($pdo, $today);
$monthlySales = getMonthlySales($pdo, $startDate, $endDate);
$topServices = getTopServices($pdo, $startDate, $endDate);
$revenueByCategory = getRevenueByCategory($pdo, $startDate, $endDate);
$orderStats = getOrderStats($pdo);
$dailyRevenueData = getDailyRevenueData($pdo, 30, $startDate, $endDate);
$otherTrend = getOrderTrend($pdo,30, $startDate, $endDate);
?>

<?php include 'admin_header.php'; ?>
<p id="text"></p>
<div class="container">
    <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
    
    <div class="date-range-selector">
        <form method="GET" class="date-filter-form">
            <div class="form-group">
                <label for="start_date">From:</label>
                <input type="date" id="start_date" name="start_date" value="<?= $firstDayOfMonth ?>">
            </div>
            <div class="form-group">
                <label for="end_date">To:</label>
                <input type="date" id="end_date" name="end_date" value="<?= $lastDayOfMonth ?>">
            </div>
            <button type="submit" class="btn btn-primary">Apply</button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="card">
            <div class="card-body">
                <h3>Today's Revenue</h3>
                <p class="amount">₦<?= number_format($dailySales['total_revenue'], 2) ?></p>
                <p class="compare <?= $dailySales['growth'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= $dailySales['growth'] >= 0 ? '↑' : '↓' ?> 
                    <?= abs($dailySales['growth']) ?>% vs yesterday
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Monthly Revenue</h3>
                <p class="amount">₦<?= number_format($monthlySales['total_revenue'], 2) ?></p>
                <p class="compare <?= $monthlySales['growth'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= $monthlySales['growth'] >= 0 ? '↑' : '↓' ?> 
                    <?= abs($monthlySales['growth']) ?>% vs last month
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Total Orders</h3>
                <p class="amount"><?= number_format($orderStats['total_orders']) ?></p>
                <p class="compare <?= $orderStats['order_growth'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= $orderStats['order_growth'] >= 0 ? '↑' : '↓' ?> 
                    <?= abs($orderStats['order_growth']) ?>% vs last month
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Avg. Order Value</h3>
                <p class="amount">₦<?= number_format($orderStats['avg_order_value'], 2) ?></p>
                <p class="compare <?= $orderStats['aov_growth'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= $orderStats['aov_growth'] >= 0 ? '↑' : '↓' ?> 
                    <?= abs($orderStats['aov_growth']) ?>% vs last month
                </p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="chart-section">
        <div class="chart-card">
            <h2><i class="fas fa-calendar-day"></i> Daily Revenue (Last 30 Days)</h2>
            <canvas id="dailyRevenueChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h2><i class="fas fa-star"></i> Top Performing Services</h2>
            <canvas id="topServicesChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h2><i class="fas fa-tags"></i> Revenue by Category</h2>
            <canvas id="revenueByCategoryChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h2><i class="fas fa-clock"></i> Order Trends</h2>
            <canvas id="orderTrendsChart"></canvas>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="data-tables">
        <div class="table-card">
            <h2><i class="fas fa-list-ol"></i> Recent Orders</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (getRecentOrders($pdo, 10) as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><?= date('H:i', strtotime($order['created_at'])) ?></td>
                            <td><?= htmlspecialchars($order['location_label']) ?></td>
                            <td><?= $order['item_count'] ?></td>
                            <td>₦<?= number_format($order['total'] ?? 0, 2) ?></td>
                            <td><span class="status-badge <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="table-card">
            <h2><i class="fas fa-concierge-bell"></i> Service Performance</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Category</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Avg. Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topServices as $service): ?>
                        <tr>
                            <td><?= htmlspecialchars($service['name']) ?></td>
                            <td><?= htmlspecialchars($service['category_name']) ?></td>
                            <td><?= $service['order_count'] ?></td>
                            <td>₦<?= number_format($service['total_revenue'], 2) ?></td>
                            <td>₦<?= number_format($service['avg_price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    // Pass PHP data to JavaScript
    const chartData = {
        dailyRevenue: <?= json_encode($dailyRevenueData) ?>,
        topServices: <?= json_encode($topServices) ?>,
        revenueByCategory: <?= json_encode($revenueByCategory) ?>,
        ordertrend: <?= json_encode($otherTrend) ?>
    };
</script>
    
<?php include 'admin_footer.php'; ?>