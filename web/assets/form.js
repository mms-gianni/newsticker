(function( $ ) {

    "use strict";


    var SrfFormNewsticker = function() {


        this.emptyForm = function () {
            $( "#broadcastform" )[0].reset();
        }

        this.init = function () {
            this.emptyForm();

            $( "#broadcastform" ).submit(this.addMessage);
            $( ".liveticker-list" ).on("click", "li .liveticker-btn-del", this.deleteMessage);
            $( ".liveticker-list" ).on("click", "li .liveticker-btn-edit", this.editMessage);

        }

        this.addMessage = function (event) {
            var channel = $( "#channel" ).val();
            var title = $( "#title" ).val();
            var body = $( "#body" ).val();
            var muid = $( "#muid" ).val();

            var formvars = {
                "muid": muid,
                "title" : title,
                "body" : body
            };

            console.log("channel: "+channel);
            console.log("muid: "+muid);
            console.log("title: "+title);
            console.log("body: "+body);


            $.ajax({
                url: '/index.php/form/submit/'+channel,
                type: 'post',
                dataType: 'json',
                data: formvars,
                success: function(data) {
                           console.log("Message add/edit success!");
                         }
            });

            $( "#muid" ).val('');
            $( "#title" ).val('');
            $( "#body" ).val('');
            event.preventDefault();

        }

        this.editMessage = function (event) {

            var channel = $( "#channel" ).val();
            var muid = $(event.target).parents("li").attr('id')
            var title = $(event.target).parents("li").find('.liveticker-item-title').text();
            var body = $(event.target).parents("li").find('.liveticker-item-body').text();
            console.log("channel: "+channel);
            console.log("muid: "+muid);
            console.log("title: "+title);
            console.log("body: "+body);

            $( "#muid" ).val(muid);
            $( "#title" ).val(title);
            $( "#body" ).val(body);


        }

        this.deleteMessage = function (event){

            var channel = $( "#channel" ).val();
            var muid = $(event.target).parents("li").attr('id')
            console.log("channel: "+channel);
            console.log("muid: "+muid);

            var formvars = {};
            $.ajax({
                url: '/index.php/form/delete/'+channel+'/'+muid,
                type: 'post',
                dataType: 'json',
                data: formvars,
                success: function(data) {
                           console.log("Message delete success!");
                         }
            });

        }
    }

    $(document).ready(function() {
        var srfFormNewsticker = new SrfFormNewsticker();
        srfFormNewsticker.init();
    });


}( window.jQuery ));


