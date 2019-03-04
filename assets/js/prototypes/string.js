String.prototype.tryParseJson = function() {
    try {
        return JSON.parse(this)
    } catch (error) {
        return false
    }
}
