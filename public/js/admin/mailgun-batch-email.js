/** public/js/admin/batch-email.js */

/* global  $, fail, moment, displayValidationErrors */

$(function(){

    
    /** yes, this section is the same as public/js/admin/batch-email.js */
    var form = $("#email-form");
    form.carousel();
    $("button[name=\"preview\"]").on("click",(e)=>{
        $.post("/admin/email/preview",form.serialize())
            .then((res)=>{
                if (res.validation_errors) {
                    return displayValidationErrors(res.validation_errors);
                }
                $(".validation-error").hide();
                $("#message-preview").html(res.markdown);
                $("#recipient-preview").text($("#recipient_list").val());
                $("#subject-preview").text($("#subject").val());
                $("#salutation-preview").text($("#salutation option:selected").text());
                form.carousel("next");
            });
    });
    $("button[name=\"revise\"]").on("click",(e)=>{form.carousel("prev");});
    $("button[name=\"send\"]").on("click",submit_form);
    var list_help = $("#list-help");
    $("#recipient_list").on("change",function(){
        var is_availability_list = $(this).children(":selected").text().includes("availab");
        if (is_availability_list) {
            list_help.html(`To view or modify this list see <a target="_blank" href="${window.basePath}/admin/interpreters/availability/list">${window.basePath}/admin/interpreters/availability/list</a>`  );
            var next_monday = moment().add(1,"weeks").startOf("isoWeek");
            var from = next_monday.format("ddd DD-MMM-YYYY");
            var to = next_monday.add(4,"days").format("ddd DD-MMM-YYYY");
            $("#subject").val(`your availability from ${from} to ${to}`);
            var organization_name = $("#email-form").data("organization_name");
            if (organization_name) {
                organization_name = `the ${organization_name}`;
            } else {
                organization_name = "our office";
            }            
            // subject to further tweaking etc...
            $("#body").text(
                `We write to ask when you would be available to accept contract interpreting assignments for ${organization_name} `
                + `during the coming week of ${from} through ${to}.`);
        } else {
            $("#subject").val("");
            $("#message").text("");
            list_help.empty();
        }
    });    
});



});