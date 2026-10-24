<?php
/**
 * Diagnostic only: compare the legacy SID cache guard across PHP versions.
 * Run as separate PHP processes with -d session.* settings.
 * No WordPress bootstrap or production changes.
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'stderr' );

$phase = $argv[1] ?? 'before';
$active = ( 'active' === $phase );
if ( $active ) {
    session_id( 'w3tcsidcompat123456789' );
    if ( ! session_start() ) {
        fwrite( STDERR, "session_start failed\n" );
        exit( 1 );
    }
}

$defined = defined( 'SID' );
$legacy_value = null;
$legacy_guard = false;
if ( $defined ) {
    // Read exactly as the old W3TC code does, recording PHP diagnostics.
    $legacy_value = SID;
    $legacy_guard = ! empty( SID );
}
$result = array(
    'php' => PHP_VERSION,
    'phase' => $phase,
    'status' => session_status(),
    'session_id' => session_id(),
    'session_name' => session_name(),
    'use_cookies' => ini_get( 'session.use_cookies' ),
    'use_only_cookies' => ini_get( 'session.use_only_cookies' ),
    'use_trans_sid' => ini_get( 'session.use_trans_sid' ),
    'sid_defined' => $defined,
    'sid_value' => $legacy_value,
    'legacy_reject' => $legacy_guard,
);
echo json_encode( $result, JSON_UNESCAPED_SLASHES ) . PHP_EOL;
if ( $active ) {
    session_write_close();
}
