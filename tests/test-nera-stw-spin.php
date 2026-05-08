<?php
/**
 * Lightweight sanity checks for Spin To Win (run with: php tests/test-nera-stw-spin.php from plugin dir).
 *
 * @package Nera_Spin_To_Win
 */

if ( php_sapi_name() !== 'cli' ) {
	exit( 'CLI only' );
}

/**
 * Pure copy of Nera_STW_Spin_Service::weighted_pick_index_seeded() — kept in sync with the real method
 * so this CLI script can run without bootstrapping WordPress.
 */
function nera_stw_test_weighted_pick_seeded( $segments, $eligible, $server_seed, $client_seed, $nonce ) {
	$total = 0.0;
	foreach ( $eligible as $i ) {
		$total += (float) $segments[ $i ]['weight'];
	}
	if ( $total <= 0 ) {
		return array(
			'index' => $eligible[0],
			'cut'   => 0.0,
			'total' => 0.0,
		);
	}

	$hex = hash_hmac( 'sha256', $client_seed . ':' . $nonce, $server_seed );
	$u53 = hexdec( substr( $hex, 0, 14 ) ) & ( ( 1 << 53 ) - 1 );
	$r   = $u53 / (float) ( 1 << 53 );
	$cut = $r * $total;

	$acc = 0.0;
	foreach ( $eligible as $i ) {
		$acc += (float) $segments[ $i ]['weight'];
		if ( $cut <= $acc ) {
			return array(
				'index' => (int) $i,
				'cut'   => $cut,
				'total' => $total,
			);
		}
	}
	return array(
		'index' => (int) $eligible[ count( $eligible ) - 1 ],
		'cut'   => $cut,
		'total' => $total,
	);
}

$segments = array(
	array( 'weight' => 10 ),
	array( 'weight' => 30 ),
	array( 'weight' => 60 ),
);
$eligible = array( 0, 1, 2 );

// Determinism: same inputs → same outputs.
$server = str_repeat( 'a', 64 );
$client = str_repeat( 'b', 64 );

$first  = nera_stw_test_weighted_pick_seeded( $segments, $eligible, $server, $client, 1 );
$second = nera_stw_test_weighted_pick_seeded( $segments, $eligible, $server, $client, 1 );
if ( $first !== $second ) {
	fwrite( STDERR, "FAIL: seeded pick is non-deterministic\n" );
	exit( 1 );
}

// Outcome must be a real eligible index.
if ( ! in_array( $first['index'], $eligible, true ) ) {
	fwrite( STDERR, "FAIL: outcome index {$first['index']} not in eligibles\n" );
	exit( 1 );
}

// Cut must satisfy: prefix_sum_before(outcome) < cut <= prefix_sum_through(outcome).
$prefix = 0.0;
$expected = null;
foreach ( $eligible as $i ) {
	$prefix += (float) $segments[ $i ]['weight'];
	if ( $first['cut'] <= $prefix ) {
		$expected = $i;
		break;
	}
}
if ( $expected !== $first['index'] ) {
	fwrite( STDERR, "FAIL: cut={$first['cut']} maps to index {$expected}, but pick returned {$first['index']}\n" );
	exit( 1 );
}

// Bumping the nonce changes the outcome stream.
$saw_change = false;
$baseline   = nera_stw_test_weighted_pick_seeded( $segments, $eligible, $server, $client, 1 );
for ( $n = 2; $n <= 20; $n++ ) {
	$next = nera_stw_test_weighted_pick_seeded( $segments, $eligible, $server, $client, $n );
	if ( $next['cut'] !== $baseline['cut'] ) {
		$saw_change = true;
		break;
	}
}
if ( ! $saw_change ) {
	fwrite( STDERR, "FAIL: nonce changes did not alter the cut value across 20 iterations\n" );
	exit( 1 );
}

// Distribution sanity: weights [10,30,60] over 5000 nonces should land roughly 10/30/60 percent.
$counts = array( 0, 0, 0 );
$trials = 5000;
for ( $n = 1; $n <= $trials; $n++ ) {
	$pick = nera_stw_test_weighted_pick_seeded( $segments, $eligible, $server, $client, $n );
	$counts[ $pick['index'] ]++;
}
$pct0 = $counts[0] / $trials * 100.0;
$pct1 = $counts[1] / $trials * 100.0;
$pct2 = $counts[2] / $trials * 100.0;
// Allow ±3 percentage points slack.
if ( abs( $pct0 - 10.0 ) > 3.0 || abs( $pct1 - 30.0 ) > 3.0 || abs( $pct2 - 60.0 ) > 3.0 ) {
	fwrite( STDERR, sprintf( "FAIL: distribution off (got %.1f / %.1f / %.1f, expected ~10/30/60)\n", $pct0, $pct1, $pct2 ) );
	exit( 1 );
}

echo "OK: nera-stw seeded weighted pick — deterministic, nonce-sensitive, distribution within tolerance\n";
