import processTransaction from './processTransaction'
import chai from 'chai'
import transactionStatus from './transactionStatus'
describe('transaction', () => {
    var options
    beforeEach(() => {
        options = {
            maybeTransaction: {},
        }
    })
    describe('processTransaction', () => {
        it('returns null when not given a transaction', () => {
            delete options.maybeTransaction

            should.not.exist(processTransaction(options))
        })
        it('can deal with transaction.transaction she·nan·i·gans', () => {
            options.maybeTransaction = {}
            options.maybeTransaction.transaction = {
                status: transactionStatus.errored,
            }
            options.transactionStatusHandlers = {
                errored: (transaction) => {
                    options.calledErrored = true
                    options.erroredTransaction = transaction
                },
            }

            let response = processTransaction(options)

            options.calledErrored.should.be.true
            options.erroredTransaction.should.equal(options.maybeTransaction.transaction)
            response.should.equal(options.maybeTransaction.transaction)
        })
        it('calls the appropriate handler with the expected transaction', () => {
            options.maybeTransaction.status = transactionStatus.errored
            options.transactionStatusHandlers = {
                errored: (transaction) => {
                    options.calledErrored = true
                    options.erroredTransaction = transaction
                },
            }
            let response = processTransaction(options)

            options.calledErrored.should.be.true
            options.erroredTransaction.should.equal(options.maybeTransaction)
            response.should.equal(options.maybeTransaction)
        })
    })
})
