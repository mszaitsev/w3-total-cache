<?php
/**
 * SID compatibility regression probe, run in a fresh PHP process.
 * Compare the legacy W3TC guard with its proposed replacement.
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'stderr' );

$phase = isset( $argv[1] ) ? $argv[1] : 'before';
$custom_name = isset( $argv[2] ) && 'custom' === $argv[2];
if ( $custom_name ) {
    session_name( 'W3TC_TEST_SID' );
}
if ( 'cookie' === $phase ) {
    $_COOKIE[ session_name() ] = 'w3tccookie123456789';
}
if ( 'active' === $phase ) {
    session_id( 'w3tcsidcompat123456789' );
}
if ( 'before' !== $phase ) {
    if ( ! session_start() ) {
        fwrite( STDERR, "session_start failed\n" );
        exit( 1 );
    }
}

$legacy = defined( 'SID' ) && ! empty( SID );
$candidate = session_status() === PHP_SESSION_ACTIVE
    && ! (bool) ini_get( 'session.use_only_cookies' );
$data = array(
    'php' => PHP_VERSION,
    'phase' => $phase,
    'name' => session_name(),
    'id' => session_id(),
    'cookies' => ini_get( 'session.use_cookies' ),
    'only_cookies' => ini_get( 'session.use_only_cookies' ),
    'trans_sid' => ini_get( 'session.use_trans_sid' ),
    'legacy' => $legacy,
    'candidate' => $candidate,
);
echo json_encode( $data, JSON_UNESCAPED_SLASHES ) . PHP_EOL;
if ( 'before' !== $phase ) {
    session_write_close();
}
if ( $legacy !== $candidate ) {
    fwrite( STDERR, "FAIL: cache rejection differs\n" );
    exit( 1 );
}
