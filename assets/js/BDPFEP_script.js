jQuery(document).ready(function ($) {

    function getUrlVars(decodeUrl) {
        var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,    
        function(m, key, value) {
            $sKey = key;
            if(decodeUrl){
                $sKey = decodeURIComponent(key);
            }
            vars[$sKey] = value;
        });
        return vars;
    }

    $('body').on('click', '.bdpfep-admin-change-role-btn', function(e){
        e.preventDefault();
        e.stopPropagation();


        $oButton = $(this);
        $iUserId = $oButton.attr("data-userid");
        $iRoleId = $oButton.parent().find('.bdpfep-admin-select-role').children('option:selected').val();
        $eSelectBox = $oButton.parent().find('.bdpfep-admin-select-role');

        if($iUserId != '' && $iRoleId != ''){
            
            bdpfep_showmodal();
            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bdpfep_changerole',
                    userid: $iUserId,
                    roleid: $iRoleId
                },
                success: function (oResult) {
                    var jsonResult = JSON.parse(oResult);
                    if(jsonResult['result'] == 'success'){
                        //$eSelectBox.val($eSelectBox.children('option:first').val());
                        bdpfep_togglemodal(jsonResult['msg']);
                    } else {
                        bdpfep_togglemodal(jsonResult['msg']);
                    }
                }
            });
        }
    });

    function bdpfep_showmodal() {
        var sModalHtml =
        '<div id="bdpfep-open-modal" class="bdpfep-modal-window">' +
            '<div>' +
                '<a href="#" title="Close" class="bdpfep-modal-close">X</a>' +
                '<div style="padding-top:20px;" class="bdpfep-modal-text"></div>' +
            '</div>' +
        '</div>';

        $('body').append(sModalHtml);
    }

    function bdpfep_togglemodal(sText) {
        //setTimeout(function(){
        $('.bdpfep-modal-text').text(sText);
        $('.bdpfep-modal-window').css({'visibility' : 'visible'}).animate({opacity: 1}, 100);
        //}, 100);
    }

    $('body').on('click', '.bdpfep-modal-close, .bdpfep-modal-window', function(e){
        e.preventDefault();
        $('.bdpfep-modal-window').animate({opacity: 0}, 50, function() {
            $('.bdpfep-modal-window').remove();
        });
    });

    if($('#wpbdp-admin-page-field-form')){
        if($('#wpbdp-admin-page-field-form').find('#wpbdp-formfield-form').find('h2:contains("Field privacy options")').length > 0){
            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    'action': 'bdpfep_connectinput',
                    'field_id': $('#wpbdp-formfield-form input[name ="field[id]"]').val()
                },
                success: function (oResult) {
                    var jsonResult = JSON.parse(oResult);

                    $sChecked = ''
                    $sShowChecked = ''
                    if(jsonResult != undefined && jsonResult['checked'] == 'true'){
                        $sChecked = 'checked="checked"'
                    }
                    if(jsonResult != undefined && jsonResult['showchecked'] == 'true'){
                        $sShowChecked = 'checked="checked"'
                    }
                    $sHtml = 
                    '<tr>' +
                        '<th scope="row">' +
                            '<label>Searchable in connect?</label>' +
                        '</th>' +
                        '<td>' +
                            '<label>' +
                            '<input name="field[display_flags][]" ' + $sChecked +' value="searchconnect" type="checkbox"> Make this field searchable in connect (Front End PM).</label>' +
                        '</td>' +
                    '</tr>' +
                    '<tr>' +
                        '<th scope="row">' +
                            '<label>Show this field in connect</label>' +
                        '</th>' +
                        '<td>' +
                            '<label>' +
                            '<input name="field[display_flags][]" ' + $sShowChecked +' value="showconnect" type="checkbox"> Show this field in connect (Front End PM).</label>' +
                        '</td>' +
                    '</tr>';
                    $('#wpbdp-admin-page-field-form').find('#wpbdp-formfield-form').find('h2:contains("Field privacy options")').next('table').append($sHtml);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        }
    }

    $('body').on('keypress', '#fep-directory-search-form input, #fep-directory-search-form select', function(e) {
        if(e.which == 10 || e.which == 13) {
            $("#fep-directory-search-form").submit();
        }
    });

    if($('#fep-content').length > 0 && $("#fep-directory-search-form").length > 0){

        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                'action': 'bdpfep_get_dir_search_fields'
            },
            success: function (oResult) {
                var jsonResult = JSON.parse(oResult);
                $sHtml = '';
                if(jsonResult['data'] != null && jsonResult['data'] != undefined){
                    $iCnt = 0;
                    for(var i=0; i < jsonResult['data'].length; i++){
                        $sStyle = '';
                        if(i % 3 == 1){
                            $sStyle = 'margin:0 0.5% 0 0.5%; ';
                        }

                        $sId = jsonResult['data'][i]['id'];
                        $sShortName = jsonResult['data'][i]['short_name'];
                        $sLabel = jsonResult['data'][i]['label'];
                        $sUrlParam = getUrlVars(true);
                        $sUrlParamVal = ($sUrlParam['bdpfep-bf[' + $sId + ']'] != undefined ? $sUrlParam['bdpfep-bf[' + $sId + ']'] : '');
                        $sHtml += 
                        '<input type="search" style="' + $sStyle + 'display:inline-block; width:33%; margin-top:10px;" value="' + $sUrlParamVal + '" name="bdpfep-bf[' + $sId + ']" class="fep-directory-' + $sShortName + '-form-field" value="" placeholder="' + $sLabel + '">' +
                        '';
                        $iCnt = i;
                    }
                }
                
                if(jsonResult['state_data'] != null && jsonResult['state_data'] != undefined){
                    if(($iCnt + 1) % 3 == 1) {
                        $sStyle = 'margin:0 0.5% 0 0.5%; ';
                    }
                    $sHtml += '<select name="bdpfep-bf-state" style="' + $sStyle + 'width:33%; padding:18px 0.5%;">';
                    $sHtml += '<option value="">Select a state</option>';
                    for(var i=0; i < jsonResult['state_data'].length; i++){
                        $sTermId = jsonResult['state_data'][i]['term_id'];
                        $sTermName = jsonResult['state_data'][i]['name'];
                        $sTermSlug = jsonResult['state_data'][i]['slug'];
                        $sHtml += 
                        '<option value="' + $sTermId + '">' + $sTermName + '</option>' +
                        '';
                    }
                    $sHtml += '</select>';
                }
                $("#fep-directory-search-form").append($sHtml);

            },
            error: function(errorThrown) {
                console.log(errorThrown);
            }
        });
    }

    // Show extra column in Front End Plugin directory

});