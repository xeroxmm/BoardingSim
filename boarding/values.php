<?php

function get_seats_by_airplane($case = FALSE){
    if(is_numeric($_REQUEST['aircrafttypx']) && $_REQUEST['aircrafttypx'] < 4 && $_REQUEST['aircrafttypx'] > 0) $typ = $_REQUEST['aircrafttypx'];    
    switch($typ){
        case '1':
            $out = array(
                    'lanes' => 1,
                    'seats' => 192,
                    'seats_left' => 3,
                    'seats_right' => 3,
                    'seats_middle' => 0,
                    'biz_seats' => 0,
                    'biz_lanes' => 0,
                    'typ' => 'B737'
                   );
        break;
        case '2':
            $out = array(
                    'lanes' => 1,
                    'seats' => 92,
                    'seats_left' => 2,
                    'seats_right' => 2,
                    'seats_middle' => 0,
                    'biz_seats' => 0,
                    'biz_lanes' => 0,
                    'typ' => 'ERJ-190'
                   );
        break;
        case '3':
            $out = array(
                    'lanes' => 1,
                    'seats' => 174,
                    'seats_left' => 3,
                    'seats_right' => 3,
                    'seats_middle' => 0,
                    'biz_seats' => 0,
                    'biz_lanes' => 0,
                    'typ' => 'A320'
                   );
        break;        
        default:
            $out = array(
                    'lanes' => 1,
                    'seats' => 180,
                    'seats_left' => 3,
                    'seats_right' => 2,
                    'seats_middle' => 0,
                    'biz_seats' => 0,
                    'biz_lanes' => 0,
                    'typ' => 'N/A'
                   );   
        break;        
    }
    if($case === FALSE) return($out);
    elseif(isset($out[$case])) return($out[$case]);
    else return(FALSE);
}

function passenger_split($fly_cat = 'biz'){
    if(!is_string($fly_cat)) return(FALSE);
    
    switch($fly_cat){
        case 'biz':
            return(array(
                    'biz' => 0.6,
                    'normal' => 0.4,
                    'old' => FALSE,
                    'child' => FALSE
                  ));    
        break;
        case 'norm':
            return(array(
                    'biz' => 0.3,
                    'normal' => 0.5,
                    'old' => 0.2,
                    'child' => FALSE
                  ));    
        break;
        case 'vacation':
            return(array(
                    'biz' => FALSE,
                    'normal' => 0.875,
                    'old' => 0.075,
                    'child' => 0.05
                  ));    
        break;
        case 'feeder':
            return(array(
                    'biz' => 0.15,
                    'normal' => 0.8,
                    'old' => 0.05,
                    'child' => FALSE
                  ));    
        break;
        case 'lowcost':
            return(array(
                    'biz' => FALSE,
                    'normal' => 0.85,
                    'old' => 0.10,
                    'child' => 0.05
                  ));    
        break;
        default:
            return FALSE;
    }
}