<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$pageTitle = 'Dashboard';
$today = $pdo->query("SELECT COALESCE(SUM(total_amount),0) total, COUNT(*) transactions FROM sales WHERE status='completed' AND DATE(sold_at)=CURDATE()")->fetch();
$month = $pdo->query("SELECT COALESCE(SUM(total_amount),0) total FROM sales WHERE status='completed' AND YEAR(sold_at)=YEAR(CURDATE()) AND MONTH(sold_at)=MONTH(CURDATE())")->fetchColumn();
$productCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn();
$lowStock = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE active=1 AND stock_qty <= min_stock")->fetchColumn();
$stockValue = (float)$pdo->query("SELECT COALESCE(SUM(stock_qty*cost_price),0) FROM products WHERE active=1")->fetchColumn();
$expenses = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE YEAR(expense_date)=YEAR(CURDATE()) AND MONTH(expense_date)=MONTH(CURDATE())")->fetchColumn();
$recent = $pdo->query("SELECT s.id,s.transaction_no,s.total_amount,s.status,s.sold_at,u.name cashier FROM sales s JOIN users u ON u.id=s.cashier_id ORDER BY s.id DESC LIMIT 7")->fetchAll();
$lowItems = $pdo->query("SELECT name,stock_qty,unit,min_stock FROM products WHERE active=1 AND stock_qty <= min_stock ORDER BY stock_qty ASC LIMIT 6")->fetchAll();
$chartRows = $pdo->query("SELECT DATE(sold_at) day, SUM(total_amount) total FROM sales WHERE status='completed' AND sold_at >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(sold_at) ORDER BY day")->fetchAll();
$chartMap = []; foreach ($chartRows as $r) $chartMap[$r['day']] = (float)$r['total'];
$chartLabels=[]; $chartValues=[]; for($i=6;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i days"));$chartLabels[]=date('M j',strtotime($d));$chartValues[]=$chartMap[$d]??0;}
require __DIR__ . '/includes/header.php';
?>
<div class="welcome-row"><div><p class="muted no-margin">Here's what's happening in your store today.</p><h2 class="welcome-title">Good day, <?= e(explode(' ', $_SESSION['name'])[0]) ?> <span>✦</span></h2></div><div class="date-chip"><?= date('D, M j, Y') ?></div></div>
<div class="stats-grid">
 <article class="stat-card"><div class="stat-top"><span>Today's sales</span><span class="stat-icon orange">₱</span></div><strong><?= money($today['total']) ?></strong><small><?= (int)$today['transactions'] ?> completed transactions</small></article>
 <article class="stat-card"><div class="stat-top"><span>Sales this month</span><span class="stat-icon green">↗</span></div><strong><?= money($month) ?></strong><small>Completed sales only</small></article>
 <article class="stat-card"><div class="stat-top"><span>Active products</span><span class="stat-icon blue">▤</span></div><strong><?= number_format($productCount) ?></strong><small>Products in your catalog</small></article>
 <article class="stat-card"><div class="stat-top"><span>Low-stock alerts</span><span class="stat-icon red">!</span></div><strong><?= number_format($lowStock) ?></strong><small>At or below minimum stock</small></article>
</div>
<div class="dashboard-grid">
 <article class="panel chart-panel"><div class="panel-heading"><div><h3>Sales overview</h3><p>Completed sales over the last 7 days</p></div><span class="subtle-tag">Last 7 days</span></div><div class="chart-wrap"><canvas id="salesChart" aria-label="Sales chart"></canvas></div></article>
 <article class="panel"><div class="panel-heading"><div><h3>Inventory snapshot</h3><p>Estimated value at recorded cost</p></div><span class="stat-icon green">▦</span></div><div class="inventory-value"><?= money($stockValue) ?></div><div class="mini-row"><span>Monthly expenses</span><strong><?= money($expenses) ?></strong></div><div class="mini-row"><span>Low-stock items</span><strong class="<?= $lowStock ? 'text-danger':'' ?>"><?= $lowStock ?></strong></div><a class="text-link" href="products.php">Manage inventory →</a></article>
</div>
<div class="dashboard-grid lower-grid">
 <article class="panel"><div class="panel-heading"><div><h3>Recent transactions</h3><p>Your latest store activity</p></div><a class="text-link" href="sales.php">View all →</a></div>
 <?php if (!$recent): ?><div class="empty-state"><div>▣</div><strong>No sales yet</strong><p>When you complete a sale, it will appear here.</p><a class="btn secondary" href="pos.php">Start a sale</a></div><?php else: ?>
 <div class="table-wrap"><table><thead><tr><th>Transaction</th><th>Cashier</th><th>Date</th><th>Status</th><th class="align-right">Total</th></tr></thead><tbody><?php foreach($recent as $r): ?><tr><td><strong><?= e($r['transaction_no']) ?></strong></td><td><?= e($r['cashier']) ?></td><td><?= date('M j, g:i A',strtotime($r['sold_at'])) ?></td><td><span class="badge <?= $r['status']==='completed'?'success':'muted-badge' ?>"><?= e(ucfirst($r['status'])) ?></span></td><td class="align-right"><?= money($r['total_amount']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></article>
 <article class="panel"><div class="panel-heading"><div><h3>Stock watch</h3><p>Products that may need restocking</p></div><span class="stat-icon orange">!</span></div>
 <?php if(!$lowItems): ?><div class="empty-state compact"><div>✓</div><strong>All stocked up</strong><p>No products are below their minimum level.</p></div><?php else: ?><div class="stock-list"><?php foreach($lowItems as $item): ?><div class="stock-item"><span class="product-dot"><?= e(strtoupper(substr($item['name'],0,1))) ?></span><span class="stock-name"><strong><?= e($item['name']) ?></strong><small>Minimum: <?= e((string)$item['min_stock']) ?> <?= e($item['unit']) ?></small></span><span class="stock-qty"><?= e((string)$item['stock_qty']) ?><small><?= e($item['unit']) ?> left</small></span></div><?php endforeach; ?></div><a class="text-link" href="products.php?stock=low">Review low stock →</a><?php endif; ?></article>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>document.addEventListener('DOMContentLoaded',()=>{const el=document.getElementById('salesChart');if(!el||!window.Chart)return;new Chart(el,{type:'line',data:{labels:<?= json_encode($chartLabels) ?>,datasets:[{label:'Sales',data:<?= json_encode($chartValues) ?>,borderColor:'#df794e',backgroundColor:'rgba(223,121,78,.12)',fill:true,tension:.35,pointRadius:3,pointBackgroundColor:'#df794e'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' ₱'+Number(c.raw).toLocaleString('en-PH',{minimumFractionDigits:2})}}},scales:{y:{beginAtZero:true,grid:{color:'#f0eee9'},ticks:{callback:v=>'₱'+v}},x:{grid:{display:false}}}}});});</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
