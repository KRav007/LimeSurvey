// Submit the form with Ajax
var AjaxSubmitObject = function () {
    var activeSubmit = false;
    // First we get the value of the button clicked  (movenext, submit, prev, etc)
    var move = "";

    var startLoadingBar = function () {
        $('#ajax_loading_indicator').css('display','block').find('#ajax_loading_indicator_bar').css({
            'width': '20%',
            'display': 'block'
        });
    };
    
    
    var endLoadingBar = function () {
        $('#ajax_loading_indicator').css('opacity','0').find('#ajax_loading_indicator_bar').css('width', '100%');
        setTimeout(function () {
            $('#ajax_loading_indicator').css({'display': 'none', 'opacity': 1}).find('#ajax_loading_indicator_bar').css({
                'width': '0%',
                'display': 'none'
            });
        }, 1800);
    };

    var checkScriptNotLoaded = function(scriptNode){
        if(!!scriptNode.src){
            return ($('head').find('script[src="'+scriptNode.src+'"]').length > 0);
        }
        return true;
    }

    var appendScript = function(scriptText, scriptPosition, src){
        src = src || '';
        scriptPosition = scriptPosition || null;
        var scriptNode = document.createElement('script');
        scriptNode.type  = "text/javascript";
        if(src != false){
            scriptNode.src   = src;
        }
        scriptNode.text  = scriptText;
        scriptNode.attributes.class = "toRemoveOnAjax";
        switch(scriptPosition) {
            case "head": if(checkScriptNotLoaded(scriptNode)){ document.head.appendChild(scriptNode); } break;
            case "body": document.body.appendChild(scriptNode); break;
            case "beginScripts": document.getElementById('beginScripts').appendChild(scriptNode); break;
            case "bottomScripts": //fallthrough
            default: document.getElementById('bottomScripts').appendChild(scriptNode); break;

        }
    };

    var bindActions = function () {
        var globalPjax = new Pjax({
            elements: "#limesurvey", // default is "a[href], form[action]"
            selectors: ["#dynamicReloadContainer", "#beginScripts", "#bottomScripts"],
            
        });
        // Always bind to document to not need to bind again
        $(document).off('.lsmove').on("click.lsmove", ".ls-move-btn",function () {
            $("#limesurvey").append("<input name='"+$(this).attr("name")+"' value='"+$(this).attr("value")+"' type='hidden' />");
        });

        // If the user try to submit the form
        // Always bind to document to not need to bind again
        $("#limesurvey").off('.submitMainForm').on("submit.submitMainForm", function (e) {
            console.log($('#limesurvey').serializeArray());
            // Prevent multiposting
            //Check if there is an active submit
            //If there is -> return immediately
            if(activeSubmit) return false;
            //block further submissions
            activeSubmit = true;
            //start the loading animation
            startLoadingBar();

            $(document).on('pjax:complete.onreload', function(){
                // We end the loading animation
                endLoadingBar();
                //free submitting again
                activeSubmit = false;
                if (/<###begin###>/.test($('#beginScripts').text())) {
                    $('#beginScripts').text("");
                }
                if (/<###end###>/.test($('#bottomScripts').text())){
                    $('#bottomScripts').text("");
                }
                
                $(document).off('pjax:complete.onreload');
            });
            
        });
        return globalPjax;
    };

    return {
        bindActions: bindActions,
        startLoadingBar: startLoadingBar,
        endLoadingBar: endLoadingBar,
        unsetSubmit: function(){activeSubmit = false;},
        blockSubmit: function(){activeSubmit = true;}
    }
}

