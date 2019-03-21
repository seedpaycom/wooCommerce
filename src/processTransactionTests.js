import processTransaction from './processTransaction'
import transactionStatus from './transactionStatus'

describe('processTransaction', () => {
    var options
    beforeEach(() => {
        options = {
            transaction: {},
            transactionAccepted: () => {
                options.transactionAcceptedCalled = true
            },
            errorHandler: (errorMessage) => {
                options.errorHandlerCalled = true
                options.errorMessage = errorMessage
            },
            pendingTransactionHandler: () => {
                options.pendingTransactionHandlerCalled = true
            },
        }
    })
    it('returns null and does not call stuff when wtf is given', async () => {
        should.not.exist(processTransaction('wtf'))
        should.not.exist(options.transactionAcceptedCalled)
        should.not.exist(options.errorHandlerCalled)
        should.not.exist(options.pendingTransactionHandlerCalled)
    })
    it('returns null when no status is given', async () => {
        should.not.exist(processTransaction(options))
        should.not.exist(options.transactionAcceptedCalled)
        should.not.exist(options.errorHandlerCalled)
        should.not.exist(options.pendingTransactionHandlerCalled)
    })
    it('calls transactionAccepted', async () => {
        options.transaction.status = transactionStatus.acceptedAndPaid

        let response = processTransaction(options)

        response.should.be.true
        options.transactionAcceptedCalled.should.be.true
        should.not.exist(options.errorHandlerCalled)
        should.not.exist(options.pendingTransactionHandlerCalled)
    })
    it('calls errorHandler when transaction has been rejected', async () => {
        options.transaction.status = transactionStatus.rejected

        let response = processTransaction(options)

        response.should.be.false
        options.errorHandlerCalled.should.be.true
        should.not.exist(options.transactionAcceptedCalled)
        should.not.exist(options.pendingTransactionHandlerCalled)
        options.errorMessage.should.contain('rejected').and.not.contain('@seedpay', 'dont show support email for rejected transactions')
    })
    it('calls errorHandler when transaction has errored', async () => {
        options.transaction.status = transactionStatus.errored

        let response = processTransaction(options)

        response.should.be.false
        options.errorHandlerCalled.should.be.true
        should.not.exist(options.transactionAcceptedCalled)
        should.not.exist(options.pendingTransactionHandlerCalled)
        options.errorMessage.should.contain('failed').and.contain('@seedpay', 'show the support email for failed transactions')
    })
    it('calls errorHandler when transaction has an unknown status', async () => {
        options.transaction.status = 'moobz status'

        let response = processTransaction(options)

        response.should.be.false
        options.errorHandlerCalled.should.be.true
        should.not.exist(options.transactionAcceptedCalled)
        should.not.exist(options.pendingTransactionHandlerCalled)
        options.errorMessage.toLowerCase().should.contain('unknown').and.contain('@seedpay', 'show support email for unknown statuses')
    })
    it('calls pending handler when pending', async () => {
        options.transaction.status = transactionStatus.pending

        let response = processTransaction(options)

        response.should.be.false
        options.pendingTransactionHandlerCalled.should.be.true
        should.not.exist(options.errorHandlerCalled)
        should.not.exist(options.transactionAcceptedCalled)
    })
    it('calls pending handler when accepting', async () => {
        options.transaction.status = transactionStatus.accepting

        let response = processTransaction(options)

        response.should.be.false
        options.pendingTransactionHandlerCalled.should.be.true
        should.not.exist(options.errorHandlerCalled)
        should.not.exist(options.transactionAcceptedCalled)
    })
})
