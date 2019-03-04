require('./string')
describe('tryParseJson', function() {
    it('returns false when given an invalid json string', function() {
        ''.tryParseJson().should.be.false
    })
    it('returns the object', function() {
        '{}'.tryParseJson().should.eql({})
    })
})
