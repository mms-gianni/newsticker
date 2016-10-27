(function( $ ) {

    "use strict";

    var SrfNewsticker = function() {

        var client;

        this.init = function (channel) {
            this.client = new Faye.Client('http://f4ba43e0.fanoutcdn.com/bayeux');
            this.client.subscribe('/'+channel, this.onNewMessage.bind(this));
            console.log("initialised: "+channel )

            this.initialLoad(channel);
        };

        this.initialLoad = function (channel) {

            $.ajax({
                url: '/api/initial/'+channel,
                type: 'get',
                dataType: 'json',
                success: this.onLoad.bind(this)
            });

        };

        this.onLoad = function(data){
            console.log("Data Loaded success!");
            $.each(data, $.proxy(function(i, message) {
                this.addMessage(message);
            }, this));
        };

        this.onNewMessage = function (data){
            console.log('got data: ' + data);
            var message = $.parseJSON( data );

            switch(message.action){
                case "add":
                    this.addMessage(message);
                    break;
                case "update":
                    this.updateMessage(message)
                    break;
                case "delete":
                    this.deleteMessage(message);
                    break;
                default:
                    console.log("ERROR: no Action provided");
            };
        };

        this.addMessage = function (message) {
            var date = new Date(message.time*1000);
            // Hours part from the timestamp
            var hours = date.getHours();
            // Minutes part from the timestamp
            var minutes = "0" + date.getMinutes();
            minutes = minutes.substr(-2);

            var message_html = $('.liveticker-body #liveticker-template:last').clone();
            message_html.prop('id', message.muid);
            message_html.css('visibility', 'inherit');
            message_html.find('.time-hour').text(hours);
            message_html.find('.time-minute').text(minutes);
            message_html.find('.liveticker-item-title').text(message.title);
            message_html.find('.liveticker-item-body').text(message.body);
            $('.liveticker-list').prepend(message_html);
            console.log("added new element");
        };
        this.deleteMessage = function (message) {
            $(".liveticker-body #"+message.muid).remove();
        };

        this.updateMessage = function (message) {
            console.log("editrow "+$('#'+message.muid));
            $('#'+message.muid+" .liveticker-item-title").text(message.title);
            $('#'+message.muid+" .liveticker-item-body").text(message.body);
        }
    };

    $(document).ready(function() {
        var srfNewsticker = new SrfNewsticker();
        srfNewsticker.init($( ".liveticker-body" ).data("ticker-id"));
    });

}( window.jQuery ));


