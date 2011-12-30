var language = {
    "sProcessing": "Processing...",
    "sLengthMenu": "Show _MENU_ entries",
    "sZeroRecords": "No matching records found",
    "sEmptyTable": "No data available in table",
    "sLoadingRecords": "Loading...",
    "sInfo": "Showing _START_ to _END_ of _TOTAL_ entries",
    "sInfoEmpty": "Showing 0 to 0 of 0 entries",
    "sInfoFiltered": "(filtered from _MAX_ total entries)",
    "sInfoPostFix": "",
    "sInfoThousands": ",",
    "sSearch": "Search:",
    "sUrl": "",
    "oPaginate": {
        "sFirst":    "First",
        "sPrevious": "Previous",
        "sNext":     "Next",
        "sLast":     "Last"
    },
};

$(document).ready(function() {
    $('#agency-all').dataTable({   "aoColumns": [
        null,
        null,
        { "bSearchable": false },
        null,
        { "bSearchable": false },
        { "bSortable": false,"bSearchable": false }
    ] ,
        "oLanguage": language
    });

    $('#agency-formulars').dataTable({   "aoColumns": [
        null,
        null,
        { "bSearchable": false },
        { "bSearchable": false, "bSearchable": false }
    ],
        "oLanguage": language});


    $('#add_agency-button').click(function() {
        document.location = "agency/create";
        return false;
    });


    $("#cancel-button").click(function() {
        document.location = "dashboard";
        return false;
    });

    $(".agency-item #cancel-button").click(function(){
        document.location = "agency/" + $("input[name=agency_id]").val();
        return false;
    });

    $("#type").buttonset().change(function() {
        if ($("#radio1").is(":checked")) {
            $("#agentur-block").show();
            $("#kunden-block").hide();
            $("input[name=type]").val("agency");
        }
        else {
            $("#agentur-block").hide();
            $("#kunden-block").show();
            $("input[name=type]").val("person");
        }
    });

    $('#agency-all .edit-button').click(function() {
        document.location = "agency/edit/" + $(this).parent().parent().attr("agency_id");
        return false;
    });

    $('#agency-all .createformular-button').click(function() {
        document.location = "formular/create/" + $(this).parent().parent().attr("agency_id");
        return false;
    });

    $('.agency-item input').keypress(function(event) {
        if (event.keyCode == KEY_ENTER) {
            var input = $(this).attr("name") == "comment" ? $('input[type=submit]').focus() : $(this).next('input, textarea');
            if ($(input).size() == 0)
                input = $(this).parents('.param').next().find('input,textarea').first();
            $(input).focus();
            return false;
        }
        else if (event.keyCode == KEY_ESC) {
            var input = $(this).prev('input, textarea');
            if ($(input).size() == 0)
                input = $(this).parents('.param').prev().find('input,textarea').last();
            $(input).focus();
            return false;
        }
    });


    $('.view-param input').keypress(function(event) {
        if (event.keyCode == KEY_ENTER) {
            $(this).parent().find('button').click();
            return false;
        }
    });

    $('#add_formular-button').click(function() {
        document.location = "formular/create/"+ $('#agency_id').val();
        return false;
    });

    $('#edit_agency-button').click(function(){
        document.location = "agency/edit/" + $('#agency_id').val();
        return false;
    });
});

