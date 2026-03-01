<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

/* ===============================
   KPI DATA
================================ */

$total_revenue = $wpdb->get_var(
    "SELECT IFNULL(SUM(amount),0) FROM {$wpdb->prefix}rsjm_payments"
);

$this_month_revenue = $wpdb->get_var(
    "SELECT IFNULL(SUM(amount),0)
     FROM {$wpdb->prefix}rsjm_payments
     WHERE MONTH(created_at)=MONTH(CURRENT_DATE())
     AND YEAR(created_at)=YEAR(CURRENT_DATE())"
);

$total_jobs = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}rsjm_jobs"
);

$total_pending = $wpdb->get_var(
    "SELECT IFNULL(SUM(total),0)
     FROM {$wpdb->prefix}rsjm_jobs
     WHERE status!='completed'"
);

/* ===============================
   MONTHLY REVENUE (Last 6 Months)
================================ */

$monthly_data = [];

for($i=5;$i>=0;$i--){
    $month = date('m', strtotime("-$i months"));
    $year  = date('Y', strtotime("-$i months"));

    $sum = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT IFNULL(SUM(amount),0)
             FROM {$wpdb->prefix}rsjm_payments
             WHERE MONTH(created_at)=%d AND YEAR(created_at)=%d",
             $month,$year
        )
    );

    $monthly_data[] = [
        'label'=>date('M Y', strtotime("-$i months")),
        'value'=>floatval($sum)
    ];
}

/* ===============================
   PAYMENT METHOD BREAKDOWN
================================ */

$methods = $wpdb->get_results(
    "SELECT method, SUM(amount) as total
     FROM {$wpdb->prefix}rsjm_payments
     GROUP BY method"
);

?>


<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

/* ===============================
   KPI DATA
================================ */

$total_revenue = $wpdb->get_var(
    "SELECT IFNULL(SUM(amount),0) FROM {$wpdb->prefix}rsjm_payments"
);

$this_month_revenue = $wpdb->get_var(
    "SELECT IFNULL(SUM(amount),0)
     FROM {$wpdb->prefix}rsjm_payments
     WHERE MONTH(created_at)=MONTH(CURRENT_DATE())
     AND YEAR(created_at)=YEAR(CURRENT_DATE())"
);

$total_jobs = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}rsjm_jobs"
);

$total_pending = $wpdb->get_var(
    "SELECT IFNULL(SUM(total),0)
     FROM {$wpdb->prefix}rsjm_jobs
     WHERE status!='completed'"
);

/* ===============================
   MONTHLY REVENUE (Last 6 Months)
================================ */

$monthly_data = [];

for($i=5;$i>=0;$i--){
    $month = date('m', strtotime("-$i months"));
    $year  = date('Y', strtotime("-$i months"));

    $sum = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT IFNULL(SUM(amount),0)
             FROM {$wpdb->prefix}rsjm_payments
             WHERE MONTH(created_at)=%d AND YEAR(created_at)=%d",
             $month,$year
        )
    );

    $monthly_data[] = [
        'label'=>date('M Y', strtotime("-$i months")),
        'value'=>floatval($sum)
    ];
}

/* ===============================
   PAYMENT METHOD BREAKDOWN
================================ */

$methods = $wpdb->get_results(
    "SELECT method, SUM(amount) as total
     FROM {$wpdb->prefix}rsjm_payments
     GROUP BY method"
);


/* ===============================
   TOP CUSTOMERS
================================ */

$top_customers = $wpdb->get_results(
    "SELECT j.customer_id,
            u.display_name,
            SUM(p.amount) as total_paid
     FROM {$wpdb->prefix}rsjm_payments p
     JOIN {$wpdb->prefix}rsjm_jobs j ON j.id = p.job_id
     JOIN {$wpdb->prefix}users u ON u.ID = j.customer_id
     GROUP BY j.customer_id
     ORDER BY total_paid DESC
     LIMIT 5"
);


/* ===============================
   COMPLETED BUT UNPAID
================================ */

$danger_jobs = $wpdb->get_results(
    "SELECT j.id, j.total,
            IFNULL(SUM(p.amount),0) as paid
     FROM {$wpdb->prefix}rsjm_jobs j
     LEFT JOIN {$wpdb->prefix}rsjm_payments p
     ON j.id = p.job_id
     WHERE j.status='completed'
     GROUP BY j.id
     HAVING paid < j.total"
);

/* ===============================
   CUSTOMER LIFETIME VALUE
================================ */

$clv = $wpdb->get_results(
    "SELECT u.display_name,
            SUM(p.amount) as lifetime_value
     FROM {$wpdb->prefix}rsjm_payments p
     JOIN {$wpdb->prefix}rsjm_jobs j ON j.id = p.job_id
     JOIN {$wpdb->prefix}users u ON u.ID = j.customer_id
     GROUP BY j.customer_id
     ORDER BY lifetime_value DESC"
);


/* ===============================
   LAST 7 DAYS REVENUE
================================ */

$daily_data = [];

for($i=6;$i>=0;$i--){
    $date = date('Y-m-d', strtotime("-$i days"));

    $sum = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT IFNULL(SUM(amount),0)
             FROM {$wpdb->prefix}rsjm_payments
             WHERE DATE(created_at)=%s",
             $date
        )
    );

    $daily_data[] = [
        'label'=>date('d M', strtotime($date)),
        'value'=>floatval($sum)
    ];
}
?>
<button id="toggleDark" class="rsjm-dark-btn">🌙 Dark Mode</button>
<?php if($danger_jobs): ?>
<div class="rsjm-alert-card">
    ⚠️ <strong><?=count($danger_jobs)?></strong>
    Completed Jobs Still Pending Payment
</div>
<?php endif; ?>

<div class="rsjm-dashboard">

<h1>📊 Business Analytics</h1>

<div class="rsjm-kpi-grid">

    <div class="rsjm-kpi-card">
        <span>Total Revenue</span>
       
		<h2 class="counter" data-value="<?=$total_revenue?>">0</h2>
    </div>

    <div class="rsjm-kpi-card green">
        <span>This Month</span>
        <h2>₹<?=number_format($this_month_revenue,2)?></h2>
    </div>

    <div class="rsjm-kpi-card orange">
        <span>Pending Amount</span>
        <h2>₹<?=number_format($total_pending,2)?></h2>
    </div>

    <div class="rsjm-kpi-card blue">
        <span>Total Jobs</span>
        <h2><?=$total_jobs?></h2>
    </div>

</div>

<div class="rsjm-chart-card">
    <h3>Monthly Revenue</h3>
    <canvas id="revenueChart"></canvas>
</div>

<div class="rsjm-chart-card">
    <h3>Payment Methods</h3>
    <canvas id="methodChart"></canvas>
</div>


<div class="rsjm-chart-card">
    <h3>🏆 Top Customers</h3>

    <div class="rsjm-leaderboard">
        <?php 
        $rank = 1;
        foreach($top_customers as $c): ?>
            <div class="rsjm-leader-item">
                <span class="rank">#<?=$rank?></span>
                <span class="name"><?=esc_html($c->display_name)?></span>
                <span class="amount">₹<?=number_format($c->total_paid,2)?></span>
            </div>
        <?php 
        $rank++;
        endforeach; ?>
    </div>
</div>

<div class="rsjm-chart-card">
    <h3>💎 Customer Lifetime Value</h3>
    <?php foreach($clv as $c): ?>
        <div class="rsjm-leader-item">
            <span><?=esc_html($c->display_name)?></span>
            <span class="amount">₹<?=number_format($c->lifetime_value,2)?></span>
        </div>
    <?php endforeach; ?>
</div>


<div class="rsjm-chart-card">
    <h3>📈 Last 7 Days Revenue</h3>
    <canvas id="dailyChart"></canvas>
</div>


<?php 
$outstanding = $wpdb->get_results(
    "SELECT j.id, j.total,
            IFNULL(SUM(p.amount),0) as paid
     FROM {$wpdb->prefix}rsjm_jobs j
     LEFT JOIN {$wpdb->prefix}rsjm_payments p
     ON j.id = p.job_id
     GROUP BY j.id
     HAVING paid < j.total"
);
?>
<div class="rsjm-chart-card">
    <h3>🔥 Outstanding Payments</h3>
    <?php foreach($outstanding as $o): 
        $pending = $o->total - $o->paid;
    ?>
        <a href="<?=admin_url('admin.php?page=rsjm-view-job&job_id='.$o->id)?>"
		   class="rsjm-heat-item-link">

			<div class="rsjm-heat-item">
				<div>
					<strong>Job #<?=$o->id?></strong>
					<small>Outstanding</small>
				</div>

				<span>₹<?=number_format($pending,2)?></span>
			</div>

		</a>
    <?php endforeach; ?>
</div>

<?php
$last = end($monthly_data);
$forecast = $last['value'] * 1.1; // 10% projected growth
?>

<div class="rsjm-chart-card">
    <h3>📊 Next Month Forecast</h3>
    <h2>₹<?=number_format($forecast,2)?></h2>
    <small>Based on last month trend</small>
</div>



</div>


<script>
document.querySelectorAll('.counter').forEach(counter=>{
    let target = parseFloat(counter.dataset.value);
    let count = 0;
    let speed = target / 60;

    function update(){
        count += speed;
        if(count >= target){
            counter.innerText = '₹' + target.toFixed(2);
        } else {
            counter.innerText = '₹' + count.toFixed(2);
            requestAnimationFrame(update);
        }
    }

    update();
});

const dailyData = <?=json_encode($daily_data)?>;

new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: dailyData.map(d=>d.label),
        datasets: [{
            data: dailyData.map(d=>d.value),
            borderColor:'#16a34a',
            backgroundColor:'rgba(22,163,74,.1)',
            fill:true,
            tension:0.4
        }]
    }
});


document.getElementById('toggleDark').addEventListener('click',()=>{
    document.body.classList.toggle('rsjm-dark');
});
</script>