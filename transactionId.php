<?php
function getTransactionId()
{
    if (!session_id()) {
        session_start();
    }
    if (get_transient('uniqueTransactionId' . session_id()) == null) {
        generateNewId();
    }
    return get_transient('uniqueTransactionId' . session_id());
}
function generateNewId()
{
    if (!session_id()) {
        session_start();
    }
    $transactionId = round(microtime(true) * 1000);
    set_transient('uniqueTransactionId' . session_id(), $transactionId, 168 * HOUR_IN_SECONDS);
    return $transactionId;
}
