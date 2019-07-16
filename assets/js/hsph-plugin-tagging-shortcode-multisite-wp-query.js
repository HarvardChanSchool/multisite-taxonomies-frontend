
( function( $ ) {
    $(document).on( "click", '.multisite_wp_query_sc .pagination a', function( event ){
        // Prevents default click.
        event.preventDefault();
        $('.ajax-overlay').addClass('active');
        $.ajax({
            url: event.target.href,
            dataType: 'json'
        }).done( function(response) {
            if(typeof response === 'object' && response.result === true ) {
                $(event.target).parents('.multisite_wp_query_sc').html(response.content);
                window.scrollTo(0, 0);
                $('.ajax-overlay').removeClass('active');
            }
        });
    });
} ( jQuery ));
