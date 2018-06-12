var $, displayValidationErrors;

$(function(){
    /** fix the minumum height for (sliding) fieldsets */
    var h = $("#fieldset-personal-data").height();
    $("fieldset").css("min-height",h+"px");

    var number_of_slides = $('.carousel-item').length;

    $("#carousel").on("slid.bs.carousel",function(){
        /** id of the current fieldset/slide */
        var i = $("fieldset:visible").index();
        if (i > 0) {
            $("#btn-back").show();
        } else {
            $("#btn-back").hide();
        }
    });

    $("#btn-back").on("click",function(event){
        event.preventDefault();
        $('.carousel').carousel("prev");
    });

    /** toggle visibility of judge control depending on the hat */
    $('#hat').on("change",function(){
        if (! $(this).val()) {
            $('#judge-div').hide();
            return;
        }

        var hat = $(this).children(":selected");
        if (hat.data().is_judges_staff) {
            $('#judge-div').slideDown();
        } else {
            $('#judge-div').slideUp();
        }
    });
    /** sort of a template for the judge widget */
    var judge_tmpl = $("<li>").addClass("list-group-item py-1").append(
        '<button type="button" title="click to remove this judge" class="btn '
        + 'btn-warning btn-sm float-right remove-div">X</button>'
    );

    /** append a judge */
    var appendJudge = function(event) {
        event.preventDefault();
        var id = $("#judge-select").val();
        if (! id) { return ; }
        var selector = "li input[value="+id+"]";
        if ($(selector).length) {
            return;
        }
        var element = judge_tmpl.clone();
        var name = $("#judge-select option:selected").text();
        element.prepend(name).prepend(
            $("<input>")
                .attr({type:"hidden",name:"user[judges][]",value:id}))
            .appendTo($("#judges"));
        $("#judge-select").val("");
        $('#judge-div .validation-error').hide();
    };
    /** assign handler */
    $("#btn-add-judge").on("click",appendJudge);

    /** remove a judge */
    $("#judges").on("click",".remove-div",function(){
        $(this).parent().remove();
    });

    /** validate each section */
    $('#btn-next').on("click",function(event){
        event.preventDefault();
        var id = $("fieldset:visible").attr('id');
        if (id === "fieldset-personal-data" || id === "fieldset-hat") {
            var params = $("fieldset:visible").serialize();
            $.post("/user/register/validate?step="+id,params).then(
                function(response){
                    if (response.validation_errors) {
                        var errors = response.validation_errors;
                        if ( id === "fieldset-personal-data") {
                            return displayValidationErrors(errors.person);
                        } else {
                            displayValidationErrors(errors);
                            if (errors.person) {
                                displayValidationErrors(errors.person);
                            }
                        }
                    } else {
                        $("fieldset:visible .validation-error").hide();
                        $(".carousel").carousel("next");
                    }
                }
            );
        } else {
            console.log("submit the whole form");
        }
    });
});
/*
// sort of an experiment, worked on it for a while, abandoned...
// have a good look at:
// https://vuejs.org/v2/guide/list.html#v-for-with-a-Component
var vm = new Vue({
    el : "#registration-form",
    data: {
        user : {
            person : {},
            judges : []
        },
    },
    methods : {
        addJudge : function() {
            var id = $("#judge-select").val();
            if (id && this.user.judges.indexOf(id) === -1) {
                this.user.judges.push(id);
            }
        },
        removeJudge : function(id) {
            var index = this.user.judges.indexOf(id);
            if (index > -1) {
              this.user.judges.splice(index, 1);
            }
            console.log( this.user.judges);
        }
    },
});
*/
