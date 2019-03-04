let submitRequest = function({
    url,
    action,
    parameters,
    callback,
    jQuery = require('jquery'),
}) {
    jQuery.post(url, {
        action,
        parameters,
    }, function(response) {
        if (callback) callback(response)
    })
}

export default {
    submitRequest,
}
