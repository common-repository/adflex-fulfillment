$(document).ready(function() {
    let daterange_input = $('.picker');
    let daterangeWrap = daterange_input.parent();
    let startDate = daterangeWrap.find('.start-date');
    let endDate = daterangeWrap.find('.end-date');
    daterange_input.daterangepicker({
        autoUpdateInput: false,
        locale: {
          format: 'YYYY-MM-DD'
        }
    },function(start,end,label){
        startDate.val(start.format('YYYY-MM-DD'));
        endDate.val(end.format('YYYY-MM-DD'));
    });
    daterange_input.on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    });

    daterange_input.on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    $('#btn-clear-filters').on('click', function() {
        daterange_input.daterangepicker({
            startDate: moment(),
            endDate: moment(),
        });
        $('input[name=search_key]').val('');
        $('select[name=search_type]').val('');
        $('select[name=payment_status]').val('');
        $('select[name=fulfillment_status]').val('');
        $('select[name=tracking_status]').val('');
        $('select[name=other_status]').val('');
    });

    $('.button-submit-order').on('click', function(event) {
        event.preventDefault();
        let button = $(this)
        button.parent().append('<span class="spinner is-active"></span>')
        this.disabled = true;

        let id = $(this).attr('data-id');

        $.ajax({
            type: "POST",
            dataType: "json",
            url: aff_ajax.url,
            data: {
                pod_nonce: $('#pod_nonce').val(),
                action: "handle_submit_order",
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
                let response = JSON.parse(error.responseText)['data'],
                    errorMessage = '';
                if(error.status === 422){
                    let message = JSON.parse(response.message)
                    console.log(message);
                    Object.values(message).map(error=>{
                        console.log(error)
                        errorMessage += error.join('<br>')
                    })
                }else{
                    errorMessage = 'Có lỗi xảy ra. Vui lòng thử lại hoặc liên hệ với AM'
                }
                Swal.fire({
                    title: 'Notification',
                    html: errorMessage,
                    icon: 'warning'
                });
            },
            complete: function () {
                button.parent().find('.spinner').remove()
                button.removeAttr('disabled');
            }
        });
    });
});
