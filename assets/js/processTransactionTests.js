import processTransaction from './processTransaction'
import chai from 'chai'
import transactionStatus from './transactionStatus'
describe('transaction', () => {
    var options
    beforeEach(() => {
        options = {
            maybeTransaction: {},
        }
        options.transactionStatusHandlers = {
            errored: (transaction) => {
                options.calledErrored = true
                options.erroredTransaction = transaction
            },
        }
    })
    describe('processTransaction', () => {
        it('returns null when not given a transaction', () => {
            delete options.maybeTransaction
            let transaction = processTransaction(options)
            transaction //?
            should.not.exist(transaction)
        })
        it('can deal with transaction.transaction she·nan·i·gans', () => {
            options.maybeTransaction = {
                transaction: {
                    status: transactionStatus.errored,
                },
            }

            let response = processTransaction(options)

            options.calledErrored.should.be.true
            options.erroredTransaction.should.equal(options.maybeTransaction.transaction)
            response.should.equal(options.maybeTransaction.transaction)
        })
        it('calls the appropriate handler with the expected transaction', () => {
            options.maybeTransaction.status = transactionStatus.errored
            let response = processTransaction(options)

            options.calledErrored.should.be.true
            options.erroredTransaction.should.equal(options.maybeTransaction)
            response.should.equal(options.maybeTransaction)
        })
        it('does not esplode if the handler does not exist', () => {
            options.maybeTransaction.status = transactionStatus.accepting

            let response = processTransaction(options)

            response.should.equal(options.maybeTransaction)
        })
    })
})
