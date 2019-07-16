
( function( $ ) {
    /**
     *
     * Widgets
     *
     */
    $(document).on( "click", '.related-topics-cloud a', function( event ){
        // prevent a click
        event.preventDefault();
        // remove all the selected topics links
        $( ".related-topics-cloud a" ).removeClass( "active-topic" );
        // add the selected to the currently clicked one
        $(this).addClass( "active-topic" );
        // remobe the children's display
        $( ".related-topics-links div" ).removeClass( "active-topic" );
        // add the display to the currently selected topic
        $( ".related-topics-links [data-topic='" + $(this).attr("data-topic") + "']" ).addClass( "active-topic" );
    });
} ( jQuery ));
