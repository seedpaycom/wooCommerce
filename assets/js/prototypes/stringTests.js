require('./string')
describe('tryParseJson', () => {
    it('returns false when given an invalid json string', () => {
        ''.tryParseJson().should.be.false
    })
    it('returns the object', () => {
        '{}'.tryParseJson().should.eql({})
    })
})
