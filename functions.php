<?php

// Admin Panel - START
function BDPFEP_add_roles_filter() {
    $bShowFilter = false;
    $sStyle = '';
    $sOrderTop = '';
    $sOrderBot = '';
    $sHtmlInput = '<input type="submit" %s% name="bdpfep_change_role_action" id="post-query-submit" class="bdpfep-admin-change-role-btn button" value="Change role">';
    $sSelectedUserRole = '';

    if( $_REQUEST['post_type'] == 'wpbdp_listing' && isset($_REQUEST['post_type']) ) {
        $bShowFilter = true;
        $sStyle = 'style="float:right;"';
        $sOrderTop = str_replace('%s%', $sStyle, $sHtmlInput);
        $sOrderBot = '';
    }
    
    if( isset($_REQUEST['post']) ) {
        $bShowFilter = true;
        $oPost = get_post($_REQUEST['post']);
        $sStyle = 'style="float:none; margin-left:4px;" data-userid="'. $oPost->post_author . '"';
        $sOrderBot = str_replace('%s%', $sStyle, $sHtmlInput);
        $sOrderTop = '';

        $oAuthor = get_user_by('id', $oPost->post_author);
        $sSelectedUserRole = $oAuthor->roles[0];

        if($oPost->post_author == get_current_user_id()){
            echo 'User cannot change his own role.';
            return;
        }
    }

    if($bShowFilter == false){
        return;
    }
    if( isset($_SERVER['REQUEST_URI']) ){
        if(strpos($_SERVER['REQUEST_URI'], 'post-new.php') != false){
            return;
        }
    }

    echo $sOrderTop;
    echo '<select name="bdpfep_change_role" class="bdpfep-admin-select-role postform" ' . $sStyle . ' >';
    echo '<option>Select a user role</option>';
	wp_dropdown_roles($sSelectedUserRole);
    echo '</select>';
    echo $sOrderBot;
    //echo '<div style="clear:both;"></div>';
}

function BDPFEP_handle_actions() {
    if (!isset($_REQUEST['bdpfep_change_role_action']) || !isset($_REQUEST['post']))
    return;

    if($_REQUEST['post_type'] != 'wpbdp_listing' || !isset($_REQUEST['post_type'])){
        return;
    }

    $action = $_REQUEST['bdpfep_change_role'];
    $posts = is_array($_REQUEST['post']) ? $_REQUEST['post'] : array($_REQUEST['post']);

    //$listings_api = wpbdp_listings_api();

    if (!current_user_can('administrator'))
    exit;

    if(isset($action) && $action != ''){
        foreach ($posts as $post_id) {
            $oPost = get_post($post_id);
            if($oPost->post_author != get_current_user_id()){
                $aRoles = get_editable_roles(); 
                $EditRoles = array();
                foreach ( $aRoles as $sKey => $oData ) {
                    array_push($EditRoles, $sKey);
                }
                if ( in_array( $action, (array) $EditRoles ) ) {
                    $oUser = new WP_User( $oPost->post_author );
                    $oUser->set_role( $action );
                }
            }
        }
    }

    $_SERVER['REQUEST_URI'] = remove_query_arg( array('bdpfep_change_role', 'bdpfep_change_role_action', 'wpbdmaction', 'wpbdmfilter', 'transaction_id', 'category_id', 'fee_id', 'u', 'renewal_id', 'flagging_user' ), $_SERVER['REQUEST_URI'] );
}

function BDPFEP_changerole(){
    if( current_user_can('administrator') ) {

        $aResults['result'] = "success";
        $aResults['msg'] = "User role changed successfully!";

        $oUser = new WP_User( $_POST['userid'] );
        $oUser->set_role( $_POST['roleid'] );

        $oJson = json_encode($aResults);
        echo $oJson;
    }
    wp_die();
}

function BDPFEP_changerole_noadmin(){
    $aResults['result'] = "fail";
    $aResults['msg'] = "Sorry you don't have the rights to use this action";
    $oJson = json_encode($aResults);
    echo $oJson;
}

function change_role_meta_boxes() {
    add_meta_box(
        'BDPFEP-change-role-fields',
        _x( 'Change user role', 'admin', 'BDPFEP' ),
        'change_role_meta_boxes_html',
        'wpbdp_listing',
        'normal',
        'core'
    );
}

function change_role_meta_boxes_html($oData)
{
    BDPFEP_add_roles_filter();
}

function BDPFEP_connectinput(){
    if( current_user_can('administrator') ) {

        $aResults['result'] = "success";
        $f = wpbdp_get_form_field( $_POST['field_id'] );
        if(in_array('searchconnect', $f->get_display_flags())){
            $aResults['checked'] = 'true';
        } else {
            $aResults['checked'] = 'false';
        }
        if(in_array('showconnect', $f->get_display_flags())){
            $aResults['showchecked'] = 'true';
        } else {
            $aResults['showchecked'] = 'false';
        }

        $oJson = json_encode($aResults);
        echo $oJson;
    }
    wp_die();
}

function BDPFEP_bdpfep_get_dir_search_fields(){

    $aResults['result'] = "success";
    $aFields = wpbdp_get_form_fields( 
        //array( 'display_flags' => 'searchconnect', 'association' => 'meta' ) 
        array( 'display_flags' => 'searchconnect' ) 
    );

    $aResults['data'] = array();
    foreach ( $aFields as $sKey => $oData ) {
        if($oData->get_association() == 'meta'){
            $aFieldData = array();
            $aFieldData['short_name'] = $oData->get_short_name();
            $aFieldData['label'] = $oData->get_label();
            $aFieldData['id'] = $oData->get_id();
            array_push($aResults['data'], $aFieldData);
        }
        if($oData->get_association() == 'category' && $oData->get_short_name() == 'state'){
            //$aResults['state_data'] 
            $aTerms = get_terms( 
                'wpbdp_category',
                array( 
                    'parent' => 0,
                    'orderby' => 'name',
                    'hide_empty'=> false
                ) 
            );

            if(!isset($aResults['state_data'])){
                $aResults['state_data'] = array();
            }
            if(isset($aTerms) && count($aTerms) > 0){
                foreach ( $aTerms as $sTermKey => $oTermData ) {
                    $aFieldData = array();
                    $aFieldData['slug'] = $oTermData->slug;
                    $aFieldData['name'] = $oTermData->name;
                    $aFieldData['term_id'] = $oTermData->term_id;
                    array_push($aResults['state_data'], $aFieldData);
                }
            }
        }
    }

    $oJson = json_encode($aResults);
    echo $oJson;

    wp_die();
}

function BDPFEP_connectinput_noadmin(){

}
// Admin Panel - END

// Front End Site - START
function BDPFEP_extend_listing($custom, $action, $listing_id=null, $user_id=null) { 

    $sHtml = '';

    $oPost = get_post($listing_id);
    $oAuthor = get_user_by('id', $oPost->post_author);
    //$aRoles = wp_roles()->get_names();
    if('view' == $action){
        $sHtml .= '<a href="' . site_url() . '/connect/?fepaction=newmessage&fep_to=' . $oAuthor->user_login . '" class="wpbdp-button button view-listing bdpfep-change-role-btn">Start chat</a>';
        //$sHtml .= '<input type="hidden" class="bdpfep-author-data" data-displayname="' . $oAuthor->user_login . '"  />';
    }
    echo $sHtml;

    return true;
}

function BDPFEP_fep_directory_arguments($aArgs)
{
    /*
    if(isset($_REQUEST['bdpfep-bf'])){
        $aCustomQuery = array();
        $aCustomQuery['meta_query'] = array();
        foreach ( $_REQUEST['bdpfep-bf'] as $sKey => $oData ) {
        }
    }
    */
    /*
    global $wpdb;
    
    if(isset($_REQUEST['bdpfep-bf'])){
        $aMetaField = array();
        foreach ( $_REQUEST['bdpfep-bf'] as $sKey => $sValue ) {
            if($sValue != ''){
                array_push($aMetaField, '(B.meta_key = \'_wpbdp[fields][' . $sKey . ']\' AND B.meta_value LIKE \'' . $sValue . '%\')');
            }
        }
    };
    $sExtendQuery = '';
    if(isset($aMetaField) && count($aMetaField) > 0){
        $sExtendQuery = 'WHERE ( ' . implode(' OR ', $aMetaField) . ' ) AND A.post_type = \'wpbdp_listing\'';
    }
    $sQuery = "
    SELECT DISTINCT(A.post_author) FROM $wpdb->posts A LEFT JOIN $wpdb->postmeta B ON A.ID = B.post_id $sExtendQuery";

    echo $sQuery;

    $aPosts = $wpdb->get_results($sQuery, ARRAY_A);
    */

    //echo '<pre>';
    $aMetaQuery = array();
    if(isset($_REQUEST['bdpfep-bf'])){
        foreach ( $_REQUEST['bdpfep-bf'] as $sKey => $sValue ) {
            if($sValue != ''){
                array_push($aMetaQuery, array(
                        'key'     => '_wpbdp[fields][' . $sKey . ']',
                        'value'   => $sValue,
                        'compare' => 'LIKE'
                    )
                );
            }
        }
    };

    $aQueryArgs = array(
        'post_type'  => 'wpbdp_listing',
        'meta_query' => $aMetaQuery
    );

    if(isset($_REQUEST['bdpfep-bf-state']) && $_REQUEST['bdpfep-bf-state'] != ''){
        $aQueryArgs['tax_query'] = array(
            array (
                'taxonomy' => 'wpbdp_category', 
                'field' => 'term_id',
                'terms' => intval($_REQUEST['bdpfep-bf-state'])
            )
        );
    }

    $rQuery = new WP_Query( $aQueryArgs );
    $aIncludeAuthorId = array();

    if ( $rQuery->have_posts() ) {

        $sCurrentRoleLevel = BDPFEP_get_user_role_level(get_current_user_id());
        foreach ( $rQuery->posts as $iKey => $aPost ) {
            // Check for user role level add to exclude or include
            $sPostAuthorRoleLevel = BDPFEP_get_user_role_level($aPost->post_author);
            
            if(intval($sCurrentRoleLevel) <= intval($sPostAuthorRoleLevel)){
                array_push($aIncludeAuthorId, $aPost->post_author);
            }
        }
        $aArgs['include'] = $aIncludeAuthorId;
    }
    if(count($aMetaQuery) > 0 && count($aIncludeAuthorId) == 0){
        $aArgs['role__in'] = 'cannot_find_any_user_____';
    }
    //var_dump($aArgs);

    //echo '</pre>';
    return $aArgs;
}

function BDPFEP_user_roles_by_id( $id )
{
    $user = new WP_User( $id );

    if ( empty ( $user->roles ) or ! is_array( $user->roles ) )
        return array ();

    $wp_roles = new WP_Roles;
    $names    = $wp_roles->get_names();
    $out      = array ();

    foreach ( $user->roles as $role )
    {
        if ( isset ( $names[ $role ] ) ){
            $out[ $role ] = $names[ $role ];
        }
    }

    return $out;
}

function debug_msg($oData)
{
    echo '<pre>';
    var_dump($oData);
    echo '</pre>';
}

function BDPFEP_get_user_role_level( $id )
{
    $sRole = '';
    $sRoleLevel = '0';
    $sMyCurrentRole = BDPFEP_user_roles_by_id($id);
    if(isset($sMyCurrentRole) && count($sMyCurrentRole) > 0){
        $sRoleKeys = array_keys($sMyCurrentRole);
    }
    if(isset($sRoleKeys) && count($sRoleKeys) > 0){
        $sRole = $sRoleKeys[0];
    }
    
    $aUserRoleLevel = get_option(OPTION_NAME_ROLELEVEL);
    if($aUserRoleLevel != false){
        $aUserRoleLevel = unserialize($aUserRoleLevel);
    }
    if(isset($aUserRoleLevel['user_role_level_value'])){
        $sRoleLevel = $aUserRoleLevel['user_role_level_value'][$sRole];
    }

    return $sRoleLevel;
}

function BDPFEP_add_custom_user_submenu() {
    add_submenu_page(
        'users.php',
        'User role level',
        'User role level',
        'manage_options',
        'role-level-page',
        'BDPFEP_role_level_page_callback' );
}

function BDPFEP_role_level_page_callback($aValues) 
{
    if(isset($_POST['action']) && $_POST['action'] == 'change-user-role-level'){
        update_option(OPTION_NAME_ROLELEVEL, serialize($_POST));
    }

    $aUserRoleLevel = get_option(OPTION_NAME_ROLELEVEL);
    
    if($aUserRoleLevel != false){
        $aUserRoleLevel = unserialize($aUserRoleLevel);
    }

    $aRoles = wp_roles()->get_names();
    ksort($aRoles);

    $sRoleRow = '';
    $sPiramydDiagram = '';

    if(isset($aUserRoleLevel['user_role_level_value'])){
        $aLevelToRole = array();
        foreach ( $aUserRoleLevel['user_role_level_value'] as $sRole => $sRoleLevel ) {
            if(!isset($aLevelToRole[$sRoleLevel])){
                $aLevelToRole[$sRoleLevel] = array();
            }
            array_push($aLevelToRole[$sRoleLevel], $aRoles[$sRole]);
        }

        if(isset($aLevelToRole) && count($aLevelToRole) > 0){
            $sPiramydDiagram = '';
            foreach ( $aLevelToRole as $sRoleLevel => $aRoleName ) {
                $sPiramydDiagram .= '<ul class="bdpfep-row">';
                if(isset($aRoleName) && count($aRoleName) > 0){
                    foreach ( $aRoleName as $iKey => $sRoleName ) {
                        $sPiramydDiagram .= '<li class="bdpfep-node">' . $sRoleName . '</li>';
                    }
                }
                $sPiramydDiagram .= '</ul>';
            }
        }
    }
    
    foreach ( $aRoles as $iKey => $sRole ) {
        $sRoleLevelValue = '';
        $sDisabled = '';
        if(count($aUserRoleLevel) > 0){
            if(isset($aUserRoleLevel['user_role_level_value'])){
                $sRoleLevelValue = $aUserRoleLevel['user_role_level_value'][$iKey];
            }
        }

        if($iKey == 'administrator'){
            $sRoleLevelValue = '0';
            $sDisabled = 'readonly="readonly"';
        }
        $sRoleRow .= '
        <tr class="form-field">
            <th scope="row" style="width:160px; padding:10px 10px 10px 0;">
                <label for="user_login">' . $sRole . '</label>
            </th>
            <td style="padding:5px 10px 5px 0;">
                <input style="width:80px;" name="user_role_level_value[' . $iKey . ']" type="text" id="" ' . $sDisabled . ' value="' . $sRoleLevelValue . '" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60">
            </td>
        </tr>
        ';
    }

    echo '
    <div class="wrap">
        <h1 class="wp-heading-inline">Manage level for each user role</h1>
        <p>This page is part of BD & FE PM extension plugin.</p>
        <div class="bdpfep-app-wrapper-left">
            <form method="post" name="change-user-role-level" id="createuser" class="validate" novalidate="novalidate">
                <input name="action" type="hidden" value="change-user-role-level">
                <table class="form-table" role="presentation">
                    <tbody>
                        ' . $sRoleRow . '
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="updatetolelevel" id="updatetolelevel" class="button button-primary" value="Update role level">
                </p>
            </form>
        </div>
        <div class="bdpfep-app-wrapper-right">
            <div class="bdpfep-app">
                ' . $sPiramydDiagram . '
            </div>
        </div>
    </div>
    ';
}

function BDPFEP_fep_directory_table_columns($aColumns)
{
    $aFields = wpbdp_get_form_fields( 
        array( 'display_flags' => 'showconnect', 'association' => 'meta' ) 
    );

    $aResults['data'] = array();
    foreach ( $aFields as $sKey => $oData ) {
        $aColumns[$oData->get_id() . '|' . $oData->get_short_name()] = $oData->get_label();
    }
    return $aColumns;
}

function BDPFEP_fep_directory_table_column_content($sColumn, $aUser)
{
    $aPostStatus = array();
    $aPostStatus['publish'] = 'publish';
    $aArgs = array(
        'post_type' => 'wpbdp_listing',
        'post_status' => $aPostStatus,
        'posts_per_page' => 1,
        'author' => $aUser->ID
    );               
    
    $rQuery = new WP_Query($aArgs);
    if ( $rQuery->have_posts() ) {
        $iPostId = '';
        foreach ( $rQuery->posts as $iKey => $aPost ) {
            $iPostId = $aPost->ID;
        }
        if(strpos($sColumn, '|') != false){
            $aMetaFieldId = explode('|', $sColumn);

            $sPostMetaValue = get_post_meta( $iPostId, '_wpbdp[fields][' . $aMetaFieldId[0] . ']', true);
            //debug_msg($iPostId);
            //debug_msg($aMetaFieldId[0]);
            echo $sPostMetaValue;
        }
    }
}