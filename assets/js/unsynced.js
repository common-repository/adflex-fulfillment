$(document).ready(function() {
    $('.button-sync-order').on('click', function(event) {
        let button = $(this)
        event.preventDefault();
        button.parent().append('<span class="spinner is-active"></span>')
        this.disabled = true;

        let id = $(this).attr('data-id');

        $.ajax({
            type: "POST",
            dataType: "json",
            url: aff_ajax.url,
            data: {
                pod_nonce: $('#pod_nonce').val(),
                action: "handle_sync_order",
                order_id: id
            },
            success: function(response) {
                let html_message = '<b>' + response.message + '</b><br>';
                Swal.fire({
                    title: 'Notification',
                    html: html_message,
                    icon: 'success',
                    timer: 1500
                });
                location.reload();
            },
            error: function (error) {
                response = JSON.parse(error.responseText)['data'];
                let html_message = '<b>' + response.message + '</b><br>';
                if (typeof response.errors != 'undefined') {
                    $(Object.keys(response.errors)).each(function(index, product_name) {
                        $(Object.keys(response.errors[product_name])).each(function(index, key) {
                            html_message += '<b>' + product_name + '</b> ' +
                            response.errors[product_name][key] + '<br>';
                        });
                    });
                }
                Swal.fire({
                    title: 'Notification',
                    html: html_message,
                    icon: 'warning'
                });
            },
            complete: function () {
                button.parent().find('.spinner').remove();
                button.removeAttr('disabled');
            }
        });
    });

    $('#btn-sync-mul').on('click', function(event) {
        event.preventDefault();

        let checkboxs = $('input[name="order[]"]:checked');
        if (checkboxs.length > 0) {
            let html = 'You selected ' + checkboxs.length + ' orders<br>';
            html += 'Are you sure to sync these orders?'
            Swal.fire({
                html: html,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#757575',
                confirmButtonText: 'Sync orders',
                reverseButtons: true,
                customClass: 'syn-mul-alert'
            }).then((result) => {
                if (result.isConfirmed) {
                    let orders_id = [];
                    checkboxs.each(function(index, checkbox) {
                        orders_id.push($(checkbox).val());
                    });

                    $.ajax({
                        type: "POST",
                        dataType: "json",
                        url: aff_ajax.url,
                        data: {
                            pod_nonce: $('#pod_nonce').val(),
                            action: "handle_sync_mul",
                            orders_id: orders_id
                        },
                        success: function(response) {
                            let html_message = '';
                            $(Object.keys(response.message)).each(function(index, order_id) {
                                html_message += '<b>' + response.message[order_id] + ' ' + order_id + '</b><br>';
                            });
                            Swal.fire({
                                title: 'Notification',
                                html: html_message,
                                icon: 'success'
                            });
                            location.reload();
                        },
                        error: function (error) {
                            response = JSON.parse(error.responseText)['data'];
                            html_message = get_alert_message(response);
                            Swal.fire({
                                title: 'Notification',
                                html: html_message,
                                icon: 'warning'
                            }).then(function(){
                                location.reload();
                            });
                        }
                    });
                }
            });
        }
        else {
            Swal.fire({
                title: 'Notification',
                text: 'Please chose at least 1 order to sync',
                icon: 'warning'
            });
        }
    });
});
