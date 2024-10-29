let $ = jQuery;
function get_alert_message(response) {
    let html_message = '';
    let type = '';
    if (typeof response.errors != 'undefined') {
        $(Object.keys(response.message)).each(function(index, order_id) {
            if (typeof response.errors[order_id] != 'undefined') {
                html_message += '<b>' + response.message[order_id] + ' ' + order_id + '</b><br>';
                if (typeof response.errors[order_id] == 'string') {
                    html_message += response.errors[order_id] + '<br>';
                } else {
                    $(Object.keys(response.errors[order_id])).each(function(index, product_name) {
                        if (typeof response.errors[order_id][product_name] == 'string') {
                            html_message += product_name + ' ' + response.errors[order_id][product_name] + '<br>';
                        } else {
                            $(Object.keys(response.errors[order_id][product_name])).each(function(index, key) {
                                html_message += product_name + ' ' + response.errors[order_id][product_name][key] + '<br>';
                            });
                        }
                    });
                }
            } else {
                html_message += '<b>' + response.message[order_id] + ' ' + order_id + '</b><br>';
            }
        });
    } else {
        $(Object.keys(response.message)).each(function(index, order_id) {
            html_message += '<b>' + response.message[order_id] + ' ' + order_id + '</b><br>';
        });
    }

    return html_message;
}

function addParameters( url, params )
{
    $.each(params, function(key, value) {
        url += '&' + key + '=' + value;
    });

    return url;
}
