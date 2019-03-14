import transactionStatus from './transactionStatus'

export default ({
    maybeTransaction,
    pendingHandler,
    transactionStatusHandlers,
}) => {
    if (!maybeTransaction) return null
    let transaction = maybeTransaction.transaction || maybeTransaction
    if (transactionStatusHandlers[transaction.status]) transactionStatusHandlers[transaction.status](transaction)
    return transaction
}
