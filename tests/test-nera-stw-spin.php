<?php
/**
 * Lightweight sanity checks for Spin To Win (run with: php tests/test-nera-stw-spin.php from plugin dir).
 *
 * @package Nera_Spin_To_Win
 */

if ( php_sapi_name() !== 'cli' ) {
	exit( 'CLI only' );
}

// Minimal stub for weighted pick logic (mirrors Nera_STW_Spin_Service private method pattern).
function nera_stw_test_weighted_pick( $segments, $eligible ) {
	$total = 0.0;
	foreach ( $eligible as $i ) {
		$total += (float) $segments[ $i ]['weight'];
	}
	if ( $total <= 0 ) {
		return $eligible[0];
	}
	$r   = 0.42;
	$acc = 0.0;
	$cut = $r * $total;
	foreach ( $eligible as $i ) {
		$acc += (float) $segments[ $i ]['weight'];
		if ( $cut <= $acc ) {
			return $i;
		}
	}
	return $eligible[ count( $eligible ) - 1 ];
}

$segments = array(
	array( 'weight' => 10 ),
	array( 'weight' => 30 ),
	array( 'weight' => 60 ),
);
$eligible = array( 0, 1, 2 );
$idx = nera_stw_test_weighted_pick( $segments, $eligible );

// With r=0.42, cut = 42; acc idx0=10, idx1=40, idx2=100 — first where 42<=acc is idx2.
if ( 2 !== $idx ) {
	fwrite( STDERR, "FAIL: expected index 2, got {$idx}\n" );
	exit( 1 );
}

echo "OK: nera-stw weighted pick stub\n";
