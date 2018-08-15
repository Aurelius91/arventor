	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			initChart();
		});

		function initChart() {
			var saleChartCTX = document.getElementById("weekly-sale-chart").getContext('2d');

			var saleChart = new Chart(saleChartCTX, {
			    type: 'bar',
			    data: {
			        labels: [<? foreach ($arr_analytic as $analytic): ?>"<?= $analytic->date_display; ?>",<? endforeach; ?>],
			        datasets: [{
			            label: 'Sales',
			            data: [<? foreach ($arr_analytic as $analytic): ?><?= $analytic->count_date; ?>,<? endforeach; ?>],
			            backgroundColor: [
			                'rgba(255, 99, 132, 0.2)',
			                'rgba(54, 162, 235, 0.2)',
			                'rgba(255, 206, 86, 0.2)',
			                'rgba(75, 192, 192, 0.2)',
			                'rgba(153, 102, 255, 0.2)',
			                'rgba(255, 159, 64, 0.2)',
			                'rgba(0, 181, 173, 0.2)'
			            ],
			            borderColor: [
			                'rgba(255,99,132,1)',
			                'rgba(54, 162, 235, 1)',
			                'rgba(255, 206, 86, 1)',
			                'rgba(75, 192, 192, 1)',
			                'rgba(153, 102, 255, 1)',
			                'rgba(255, 159, 64, 1)',
			                'rgba(0, 181, 173, 1)',
			            ],
			            borderWidth: 1,
			        }]
			    },
			    options: {
			        scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero: true
			                }
			            }]
			        },
			        legend: {
					    display: false,
					}
			    },
			});
		}
	</script>

	<!-- Dashboard Here -->
	<div class="main-content">
		<div class="ui five column grid">
			<? if (isset($acl['sale']) && $acl['sale']->list > 0): ?>
				<div class="column">
					<div class="ui raised segment">
						<a class="ui red ribbon label">Sales</a>
						<span>Sales Today</span>
						<div style="padding-top: 15px;">
							<div style="font-size: 1.1rem !important; text-align: right;"><?= $count_sale; ?> Sale(s)</div>
						</div>
					</div>
				</div>
			<? endif; ?>

			<? if (isset($acl['transaction']) && $acl['transaction']->list > 0): ?>
				<div class="column">
					<div class="ui raised segment">
						<a class="ui orange ribbon label">Earns</a>
						<span>Today's Earn</span>
						<div style="padding-top: 15px;">
							<div style="font-size: 1.1rem !important; text-align: right;">Rp <?= $total_earnings; ?></div>
						</div>
					</div>
				</div>
				<div class="column">
					<div class="ui raised segment">
						<a class="ui yellow ribbon label">Spends</a>
						<span>Today's Spend</span>
						<div style="padding-top: 15px;">
							<div style="font-size: 1.1rem !important; text-align: right;">Rp <?= $total_spendings; ?></div>
						</div>
					</div>
				</div>
			<? endif; ?>

			<div class="column">
				<div class="ui raised segment">
					<a class="ui teal ribbon label">Customer</a>
					<span>My Customer</span>
					<div style="padding-top: 15px;">
						<div style="font-size: 1.1rem !important; text-align: right;"><?= $count_customer; ?> Customer(s)</div>
					</div>
				</div>
			</div>
			<div class="column">
				<div class="ui raised segment">
					<a class="ui blue ribbon label">Product</a>
					<span>My Product</span>
					<div style="padding-top: 15px;">
						<div style="font-size: 1.1rem !important; text-align: right;"><?= $count_product; ?> Product(s)</div>
					</div>
				</div>
			</div>
		</div>

		<div class="ui four column grid">
			<div class="eight wide column">
				<? if (isset($acl['sale']) && $acl['sale']->list > 0): ?>
					<div class="ui raised segment">
						<a class="ui teal ribbon label">Analytics </a>
						<span>Weekly Sales Analytics</span>
						<div style="padding-top: 15px; height: 40%; max-height: 400px; overflow-y: auto;">
							<canvas id="weekly-sale-chart" width="400" height="190"></canvas>
						</div>
					</div>
				<? endif; ?>

				<div class="ui two column grid">
					<div class="column">
						<div class="ui raised segment">
							<a class="ui blue ribbon label">Product </a>
							<span>Top 10 Product</span>
							<div class="table-segment" style="padding-top: 15px; max-height: 400px; overflow-y: auto;">
								<table class="ui striped selectable celled table">
									<thead>
										<tr>
											<th>#</th>
											<th>Product</th>
										</tr>
									</thead>
									<tbody>
										<? foreach ($arr_sale_item as $key => $sale_item): ?>
											<tr>
												<td><?= $key + 1; ?></td>
												<td><?= $sale_item->product_name; ?></td>
											</tr>
										<? endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="column">
						<div class="ui raised segment">
							<a class="ui orange ribbon label">Customer </a>
							<span>Top 10 Customer</span>
							<div class="table-segment" style="padding-top: 15px; max-height: 400px; overflow-y: auto;">
								<table class="ui striped selectable celled table">
									<thead>
										<tr>
											<th>#</th>
											<th>Customer</th>
										</tr>
									</thead>
									<tbody>
										<? foreach ($arr_sale as $key => $sale): ?>
											<tr>
												<td><?= $key + 1; ?></td>
												<td><?= $sale->customer_name; ?></td>
											</tr>
										<? endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="eight wide column">
				<? if (isset($acl['inventory']) && $acl['inventory']->list > 0): ?>
					<div class="ui raised segment">
						<a class="ui red ribbon label">WARNING </a>
						<span>Minimum Stock</span>
						<div class="table-segment" style="padding-top: 15px; height: 40%; max-height: 70vh; overflow-y: auto;">
							<table class="ui striped selectable celled table">
								<thead>
									<tr>
										<th>Product</th>
										<th>Location</th>
										<th>QTY</th>
									</tr>
								</thead>
								<tbody>
									<? foreach ($arr_inventory as $inventory): ?>
										<tr>
											<td><?= $inventory->product_name; ?></td>
											<td><?= $inventory->location_name; ?></td>
											<td><?= $inventory->quantity_display; ?></td>
										</tr>
									<? endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				<? endif; ?>
			</div>
		</div>
	</div>
</body>
</html>