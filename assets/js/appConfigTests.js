describe('submitRequest', function() {
    beforeEach(() => {})

    it('lets you set global settings at runtime', function() {
        let appConfig = require('./appConfig')
        appConfig.someSetting = 'yay!  i amz a settingzor'
        let appConfig2 = require('./appConfig')
        appConfig2.someSetting.should.equal(appConfig.someSetting)
    })
})
