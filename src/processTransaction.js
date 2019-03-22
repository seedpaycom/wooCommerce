import transactionStatus from './transactionStatus'

export default ({
    transaction,
    errorHandler,
    transactionAccepted,
    pendingTransactionHandler,
}) => {
    if (!transaction || !transaction.status) return null
    let status = transaction.status
    if (status == transactionStatus.acceptedAndPaid) {
        transactionAccepted()
        return true
    } else if (status == transactionStatus.rejected) {
        errorHandler('Payment rejected.  Please resubmit the order to try again.')
    } else if (status == transactionStatus.errored) {
        errorHandler('Payment failed.  Please resubmit the order to try again or contact helpdesk@seedpay.com for assistance.')
    } else if (status == transactionStatus.pending || status == transactionStatus.accepting) {
        pendingTransactionHandler()
    } else {
        errorHandler('Unknown transaction status.  Please contact helpdesk@seedpay.com.')
    }
    return false
}
