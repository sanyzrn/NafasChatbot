<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ncp_render_logs_page(): void {
	global $wpdb;
	$table  = $wpdb->prefix . NCP_TABLE;
	$page   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
	$limit  = 50;
	$offset = ( $page - 1 ) * $limit;
	$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	$pages  = max( 1, (int) ceil( $total / $limit ) );
	$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset ), ARRAY_A );
	?>
	<div class="wrap ncp-admin">
		<div class="ncp-admin-header">
			<h1>📋 گزارش مکالمات چت‌بات</h1>
			<p class="ncp-tagline">مجموع <?php echo number_format( $total ); ?> مکالمه ثبت شده</p>
		</div>

		<div class="ncp-admin-body">
			<div class="ncp-card">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
					<div>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=ncp_export_log&format=csv' ), NCP_NONCE ) ); ?>">📥 CSV</a>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=ncp_export_log&format=json' ), NCP_NONCE ) ); ?>">📥 JSON</a>
					</div>
					<button class="button button-secondary" id="ncp-clear-log" style="color:#c00">🗑️ پاک کردن لاگ</button>
				</div>

				<table class="widefat striped">
					<thead>
						<tr>
							<th>#</th>
							<th>Session</th>
							<th>IP</th>
							<th>Provider</th>
							<th>Model</th>
							<th>Tokens</th>
							<th>Cache</th>
							<th>پیام کاربر</th>
							<th>پاسخ</th>
							<th>تاریخ</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="10" style="text-align:center;padding:2rem">هنوز مکالمه‌ای ثبت نشده است.</td></tr>
						<?php else : ?>
							<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['id'] ); ?></td>
								<td><code style="font-size:11px"><?php echo esc_html( substr( $row['session_id'], 0, 12 ) ); ?>…</code></td>
								<td><?php echo esc_html( $row['user_ip'] ); ?></td>
								<td><?php echo esc_html( $row['provider'] ); ?></td>
								<td><?php echo esc_html( $row['model'] ); ?></td>
								<td><?php echo esc_html( $row['tokens_used'] ); ?></td>
								<td><?php echo $row['cached'] ? '⚡' : '—'; ?></td>
								<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?php echo esc_attr( $row['message'] ); ?>"><?php echo esc_html( mb_substr( $row['message'], 0, 80 ) ); ?></td>
								<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?php echo esc_attr( $row['response'] ); ?>"><?php echo esc_html( mb_substr( $row['response'], 0, 80 ) ); ?></td>
								<td style="white-space:nowrap"><?php echo esc_html( $row['created_at'] ); ?></td>
							</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<?php if ( $pages > 1 ) : ?>
				<div class="tablenav bottom" style="margin-top:1rem">
					<div class="tablenav-pages">
						<?php
// FIX: Reference the constant so the slug is always in sync with registration
$logs_slug = NCP_MENU_SLUG . '-logs';
$base_url  = admin_url( 'admin.php?page=' . rawurlencode( $logs_slug ) . '&paged=%#%' );
echo wp_kses_post( paginate_links( [
    'base'    => $base_url,
    'format'  => '',
    'current' => $page,
    'total'   => $pages,
] ) );
						?>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<script>
	document.getElementById('ncp-clear-log')?.addEventListener('click', function() {
		if (!confirm('آیا مطمئنید؟ همه لاگ‌ها و آمارها پاک خواهند شد.')) return;
		fetch(ajaxurl, {
			method: 'POST',
			headers: {'Content-Type':'application/x-www-form-urlencoded'},
			body: 'action=ncp_clear_log&nonce=<?php echo esc_js( wp_create_nonce( NCP_NONCE ) ); ?>'
		}).then(r => r.json()).then(d => {
			if (d.success) { location.reload(); }
			else { alert('خطا: ' + (d.data?.message || 'unknown')); }
		});
	});
	</script>
	<?php
}
