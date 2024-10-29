$(document).ready(function() {
    let api_key_input = $('input[name=pod_api_key]');

    $('#edit').on('click', function() {
        api_key_input.prop('readonly', false);
    });

    $('#cancel').on('click', function() {
        api_key_input.prop('readonly', true);
        api_key_input.val(api_key_input.data('value'));
    });
});
