<?php
function init_charge_array() {
    global $charge;

    $charge = array(
        'seats' => 0,
        'seat_loading' => FALSE,
        'seats_used' => 0,
        'seats_available' => FALSE,
        'passenger_split' => array(
            'biz' => FALSE,
            'normal' => FALSE,
            'old' => FALSE,
            'child' => FALSE
        ),
        'seats_by_cat' => array(
            'biz' => array(
                'available' => FALSE,
                'used' => FALSE,
            ),
            'normal' => array(
                'available' => FALSE,
                'used' => FALSE,
            )
        ),
        'people' => array(
            'biz' => 0,
            'normal' => 0,
            'old' => 0,
            'child' => 0
        ),
        'with_boardingcard' => array(
            'biz' => 0,
            'normal' => 0,
            'old' => 0,
            'child' => 0
        )
    );
    return ($charge);
}

function init_group_array() {
    global $group;

    $group = array(
        'pre_ID' => -1,
        'list' => array(
            array(
                'size' => FALSE,
                'ID' => 1,
                'typ' => FALSE,
                'quantity' => array(
                    'biz' => 0,
                    'normal' => 0,
                    'old' => 0,
                    'child' => 0
                ),
                'persons' => array(),
                'assigned' => array(
                    'biz' => 0,
                    'normal' => 0,
                    'old' => 0,
                    'child' => 0
                ),
                'remaining' => 0,
                'power' => FALSE,
                'seating' => FALSE,
                'duty' => FALSE
            )
        )
    );
}

function init_persons_array() {
    global $persons;

    $persons = array(
        array(
            'ID' => FALSE,
            'typ' => FALSE,
            'v' => array(
                'norm' => FALSE,
                'in' => FALSE,
                'seat' => FALSE
            ),
            'group' => array(
                'set' => FALSE,
                'id' => FALSE,
                'duty' => FALSE,
                'power' => FALSE,
                'seating' => FALSE
            ),
            'baggage' => FALSE,
            'compfort' => FALSE,
            'seat_nr' => FALSE,
            'is_alpha' => FALSE
        )
    );

    return ($persons);
}

function init_seatsbo_array() {
    global $seatsbo;

    $seatsbo = array();

    return ($seatsbo);
}

function init_summary_array() {
    global $summary;

    $summary = array(
        'misc' => array(
            'typ' => 0,
            'boardingtyp' => BOARDINGTYP,
            'duration' => 0,
            'way' => PBBL
        ),
        'load' => array(
            'max_pax' => 0,
            'pax' => 0,
            'pax_normal' => 0,
            'pax_old' => 0,
            'pax_child' => 0,
            'pax_biz' => 0,
            'baggages' => 0,
            'groups' => 0
        ),
        'statistics' => array(
            'lfac_used' => 0,
            'lfac_calc' => 0,
            'bagfac' => 0,
            'baggage' => array(
                '0' => 0,
                '1' => 0,
                '2' => 0,
                '3' => 0,
                '4' => 0
            ),
            'v_all' => array(),
            'v_normal' => array(),
            'v_old' => array(),
            'v_child' => array(),
            'v_biz' => array(),
            'v_door_group' => array(
                'speed' => array()
            ),
            't_loading_bags' => array(),
            't_waiting' => array(),
            'conflicts' => array(),
            'group_size' => array(
                'ct' => 0,
                '1' => 0,
                '2' => 0,
                '3' => 0,
                '4' => 0,
                '5' => 0,
                '6' => 0,
                '7' => 0,
                '8' => 0,
                '9' => 0,
                '10' => 0
            )
        )
    );

    return ($summary);
}

function generate_waitingtimestamps($id) {
    global $wpdb;
    $sql2 = 'SELECT `waitingtime` FROM `' . $wpdb->prefix . 'boarding_waitingtime` WHERE `main_id` = ' . $id . ' LIMIT 20000;';

    $result = $wpdb->get_results($sql2);
    if (empty($result[0]->waitingtime)) return (FALSE);

    $sum = 0;
    $k   = 0;
    foreach ($result as $value) {
        $sum = $sum + $value;
        $k++;
    }
    return (array($sum, $sum / $k));
}

function generate_aircraft_factors($seats = 200, $charge_factor = 0.75, $boarding_typ = 'random', $fly_cat = 'norm') {
    if (!is_numeric($charge_factor) || !is_numeric($seats)) return (FALSE);

    init_charge_array();

    global $charge;

    $charge['seats']        = $seats;
    $charge['seat_loading'] = $charge_factor;
    if ($_REQUEST['speclf'] == 'non') $charge['seats_used'] = ($charge_factor == 1) ? $seats : intval($seats * ($charge_factor + ((rand(0, 99) < 50) ? 1 : -1) * rand(0, 1000) * 0.0001 * $charge_factor));
    else {
        if (is_numeric($_REQUEST['speclf']) && $_REQUEST['speclf'] >= 0.25 && $_REQUEST['speclf'] <= 1) {
            $charge['seats_used']   = round($_REQUEST['speclf'] * $seats, 0);
            $charge['seat_loading'] = $_REQUEST['speclf'];
        } else {
            $charge['seats_used'] = ($charge_factor == 1) ? $seats : intval($seats * ($charge_factor + ((rand(0, 99) < 50) ? 1 : -1) * rand(0, 1000) * 0.0001 * $charge_factor));
        }
    }
    $charge['seats_available'] = ($boarding_typ == 'random') ? $charge['seats_used'] : $charge['seats'];

    $charge['passenger_split'] = passenger_split($fly_cat);
    if ($_REQUEST['groups'] == 'nu') $charge['passenger_split']['child'] = FALSE;

    # charge['..._people'] wird in der Funktion calculate_seats berechnet und direkt in globale Variable $charge geschrieben,
    # da gegeneitige Abhängigkeiten durch Flug/Ziel-Typen enstehen

    calculate_seats($charge['passenger_split'], $charge['seats_used']);

    $remaining = $charge['seats_used'] - ($charge['people']['biz'] + $charge['people']['normal'] + $charge['people']['old'] + $charge['people']['child']);

    if ($remaining < 0) {
        echo 'Fehler in der Passagierberechnung';
        return (FALSE);
    }

    $charge['unassigned'] = $charge['people'];

    return ($charge);

}

function generate_alpha() {
    return ((rand(0, 100) > 10) ? FALSE : TRUE);
}

function generate_aircraft_plan() {
    $seats_align = get_seats_by_airplane();
    if (DEV) echo 'Beschrifte Sitzreihen ...<br />';
    switch ($seats_align['lanes']) {
        case 1:
            $sum = $seats_align['seats_left'] + $seats_align['seats_right'] + $seats_align['seats_middle'];
            if (($seats_align['seats'] % $sum) == 0) {
                if ($seats_align['seats_middle'] == 0) {
                    $a = max(array($seats_align['seats_left'], $seats_align['seats_right'], $seats_align['seats_middle']));
                    for ($i = 0; $i < (2 * $seats_align['seats'] / $sum); $i++) {
                        $z = 0;
                        if (($i + 2) % 2 == 0) {
                            for ($j = 'A'; $j != 'Z'; $j++) {
                                $out[$seats_align['seats_left'] . 'er'][$i][$z] = $j;
                                $z++;
                                if ($z > ($seats_align['seats_left'] - 1)) {
                                    $l = $j;
                                    $j = 'Y';
                                }
                            }
                        } else {
                            $temp = ord('s');
                            $z    = 0;
                            for ($j = ($temp + $seats_align['seats_right']); $j > $temp; $j--) {
                                $out[$seats_align['seats_right'] . 'er'][$i][$z] = chr($j);
                                $z++; #if($z > ($a - 1)) $j = 'Y';
                            }
                        }
                    }
                    if ($seats_align['seats_right'] == $seats_align['seats_left']) {
                        $out[$a . 'er']['available'] = $i;
                        $out[$a . 'er']['count']     = $i;
                        for ($i = 1; $i < 6; $i++) {
                            if (($a - $i) > 0) {
                                $out[($a - $i) . 'er']['available'] = 0;
                                $out[($a - $i) . 'er']['count']     = 0;
                            }
                        }
                    } else {
                        $out[$seats_align['seats_right'] . 'er']['available'] = $i / 2;
                        $out[$seats_align['seats_left'] . 'er']['count']      = $i / 2;
                        for ($i = 1; $i < 6; $i++) {
                            if (($a - $i) > 0 && ($a - $i) != $seats_align['seats_right'] && ($a - $i) != $seats_align['seats_left']) {
                                $out[($a - $i) . 'er']['available'] = 0;
                                $out[($a - $i) . 'er']['count']     = 0;
                            }
                        }
                    }
                    return ($out);
                } else {
                    #
                    echo 'aba';
                    #
                }
            } else {
                #
                echo 'beb';
                #
            }
            break;
    }
}

function generate_baggage($typ = 'random') {
    $random = rand(0, 99);

    switch ($typ) {
        case 'random': # random == lowcost-Carrier
            return (1);
            break;
        default:
            if ($random < 60) return (1);
            elseif ($random < 90) return (2);
            elseif ($random < 98) return (3);
            else return (4);
            break;
    }
}

function generate_boarding($p = FALSE, $g = FALSE, $door = FALSE, $typ = 'normal') {
    if (!$p || !$g || !$door || !is_array($p) || !is_array($g) || !is_array($door)) return (FALSE);

    global $persons;
    global $group;
    global $charge;
    global $summary;
    global $seatsbo;

    $cabin = array();
    global $grouhist;
    $grouhist = '';

    $seatsbo = init_seatsbo_array();

    $persons = $p;
    $group   = $g;

    $boarded     = FALSE;
    $interval    = 0.4;
    $t           = $door[0]['time'];
    $stdist      = get_seats_by_airplane('seats_left') * 0.4 + 1; #m
    $rowdist     = 0.8;
    $a           = 0;
    $d           = 0; #_pre($door);#_pre($group[0]); _pre($persons); die();
    $speedrate   = round(4 / 9, 3);
    $loadingtime = 5;

    switch ($typ) {
        default: # Normales Boarden mit einem Eingang und sofort platzfindenden Personen
            while (!$boarded) {
                $t = $t + $interval;
                $v = 0;
                $a++;
                if ($a == 100000) {
                    echo $a . ' - ' . $v . ' mit Zeit: ' . $t . ' und v: ' . $v . '<br />';
                    _pre($cabin);
                    return (FALSE);
                }

                if (!isset($cabin[0])) {
                    $cabin[0]['status'] = 'walking';
                    $cabin[0]['x']      = 0.01;
                    $cabin[0]['ID']     = $door[$d]['person'];
                    $cabin[0]['in']     = $t;
                    $d++;
                } elseif (isset($door[$d]['time'])) {
                    if ($door[$d]['time'] <= $t && ($cabin[($d - 1)]['x'] > 0.4 || $cabin[($d - 1)]['x'] == FALSE)) {
                        $cabin[$d]['status'] = 'walking';
                        $cabin[$d]['x']      = 0.1;
                        $cabin[$d]['ID']     = $door[$d]['person'];
                        $cabin[$d]['in']     = $t;
                        $d++; # Problem, das Array wird mit falschem Count überschrieben...

                        $grouhist .= ' <span style="font-weight: bold;color:' . $group[$persons[$door[$d]['person']]['group']['group']['id']]['color'] . '">' . $persons[$door[$d]['person']]['group']['group']['id'] . '</span> ';

                        /**
                         * if($persons[$door[$d]['person']]['seat']['row'] > (64-16)) echo '<b><span style="color: #900">'.$persons[$door[$d]['person']]['group']['group']['id'].' </span></b>';
                         * elseif($persons[$door[$d]['person']]['seat']['row'] > (64-32)) echo '<b><span style="color: green">'.$persons[$door[$d]['person']]['group']['group']['id'].' </span></b>';
                         * elseif($persons[$door[$d]['person']]['seat']['row'] > (64-16-32)) echo '<b><span style="color: red">'.$persons[$door[$d]['person']]['group']['group']['id'].' </span></b>';
                         * else echo '<b><span style="color: grey">'.$persons[$door[$d]['person']]['group']['group']['id'].' </span></b>';;
                         * if($persons[$door[$d]['person']]['seat']['row'] > (64-16)) {
                         * $vkl++; #echo '<b>'.$persons[$door[$d]['person']]['seat']['row']. '</b> - ';
                         * }**/
                    }
                }

                foreach ($cabin as $key => $value) {
                    $m = 0;
                    if ($value['status'] != 'seated') { # Wenn Passagier nicht sitzt, schau, ob er sich bewegen kann...
                        if ($value['status'] != 'slipping' && $value['status'] != 'waiting' && $value['status'] != 'loading' && $value['status'] != 'hopping') { # Wenn Passagier sich nicht gerade einreiht, wartet oder Handgepäck verstaut
                            $min     = 9999;
                            $proofed = FALSE;
                            for ($i = 1; $i < ($key + 1); $i++) { # schaue, ob Vorgänger sitzen, bzw. wo die kleinste x-Kood ist
                                if (is_numeric($cabin[($key - $i)]['x'])) {
                                    if ($cabin[($key - $i)]['x'] < $min && $cabin[($key - $i)]['x'] > $value['x']) $min = $cabin[($key - $i)]['x'];
                                    $proofed = TRUE;
                                }
                            }
                            if (!$proofed) $min = 9000;
                            if ($min != 9999 && $min > ($value['x'] + $persons[$value['ID']]['compfort'] * 0.01 + round($interval * $persons[$value['ID']]['v']['in'], 3)
                                                        + 0.2 + rand(0, 200) * 0.005)) { # Wenn Platz ist, dann beende Schleife, und ziehe die Person um die Weite s.o. weiter
                                $cabin[$key]['x'] = $cabin[$key]['x'] + round($speedrate * $interval * $persons[$value['ID']]['v']['norm'], 3);
                                $prow             = ($persons[$value['ID']]['seat']['row'] % 2 == 0) ? (0.5 * $persons[$value['ID']]['seat']['row']) : (0.5 * ($persons[$value['ID']]['seat']['row'] - 1));
                                if ($cabin[$key]['x'] >= $prow * $rowdist + $stdist) { # Wenn er über das Ziel hinaus schießt, dann setze in auf positioniere ihn auf den 40cm vor der gewünschten Reihe
                                    $cabin[$key]['x'] = $prow * $rowdist + $stdist;

                                    $free = calculate_free_way_to_seat($persons[$value['ID']], $cabin);
                                    if (!$free) return (FALSE);
                                    $cabin[$key]['final']        = $free['final'];
                                    $cabin[$key]['outer']        = $free['outer'];
                                    $cabin[$key]['sc_seat']      = $persons[$value['ID']]['seat']['row'] . ' - ' . $persons[$value['ID']]['seat']['nr'];
                                    $cabin[$key]['sc_speed']     = round(1 / 3 * $persons[$value['ID']]['v']['norm'], 3);
                                    $cabin[$key]['sc_seatspeed'] = $persons[$value['ID']]['v']['seat'];
                                    $cabin[$key]['sc_baggage']   = $persons[$value['ID']]['baggage'];
                                    $cabin[$key]['sc_speedrate'] = $speedrate;

                                    if ($persons[$value['ID']]['baggage'] > 0) {
                                        $cabin[$key]['status']           = 'loading';
                                        $cabin[$key]['loading']['start'] = $t;
                                        $randomtime                      = function_weibull_distribution((3 + $persons[$value['ID']]['baggage']), 1.8, 15);
                                        #echo $randomtime.'<br />';
                                        $cabin[$key]['loading']['end'] = $t + $loadingtime + $randomtime - 0.001;
                                    } elseif ($free['status']) {
                                        $cabin[$key]['x']                                               = FALSE;
                                        $cabin[$key]['status']                                          = 'slipping';
                                        $cabin[$key]['seat']['now']                                     = $free['outer'];
                                        $seatsbo[$persons[$value['ID']]['seat']['row']][$free['final']] = $value['ID']; # Zwar kann es sein, dass bspw. der Mittelsitz benutzt wird und so der äußere Sitz erstmals belegt ist, aber der Passagier rutsch weiter vom Gang weg, daher nir final relevant ( für nachfolgende Passagiere)
                                        if ($free['outer'] == $free['final']) {
                                            $cabin[$key]['status']            = 'seated';
                                            $cabin[$key]['slipping']['start'] = $t;
                                            $cabin[$key]['slipping']['end']   = $t;
                                            $cabin[$key]['seated']['start']   = $t;
                                            $cabin[$key]['seated']['end']     = $t;
                                        } else {
                                            $cabin[$key]['slipping']['start'] = $t;
                                            $cabin[$key]['slipping']['end']   = $t + $free['left'] * $persons[$value['ID']]['v']['seat'] * $interval - 0.001;
                                        }
                                    } else {
                                        $cabin[$key]['status'] = 'waiting';
                                        $m                     = 0;
                                        foreach ($free['waiting_for'] as $wfkey => $wftemp) {
                                            $cabin[$wftemp]['status']           = 'hopping';
                                            $cabin[$wftemp]['hopping']['start'] = $t;
                                            $cabin[$wftemp]['hopping']['end']   = $t + ($free['outer'] - 1 - $wfkey) * $persons[$wftemp]['v']['seat'] * $interval - 0.001;
                                            $m++;
                                            $temps[]             = $wftemp;
                                            $fintime             = $t + ($free['outer'] - 1 - $wfkey) * $persons[$wftemp]['v']['seat'] + 6 + function_weibull_distribution(4, 1.9, 20) + 2 * $interval;
                                            $cabin[$wftemp]['x'] = $cabin[$key]['x'] + $rowdist / 2;
                                        }
                                        $x = 0;
                                        if ($m > 1) {
                                            $mint = 0;
                                            foreach ($temps as $tmpv) {
                                                if ($cabin[$tmpv]['hopping']['end'] > $mint + 4 * $interval) $mint = $cabin[$tmpv]['hopping']['end'] + 2 * $interval + 2 * $x * $interval;
                                                $x++; # $x ist wichtig, damit der ausensitzende mit einem Zeitstrafe belegt wird und so den innensitzenden wieder in die Reihe lässt, bevor er reinrutscht
                                            }
                                            $fintime = $mint;
                                        }
                                        $cabin[$key]['waiting']['start'] = $t;
                                        $cabin[$key]['waiting']['end']   = $fintime - 2 * $interval;
                                    }
                                }

                            }

                        } elseif ($value['status'] == 'loading') {
                            if ($value['loading']['end'] <= $t) {

                                $free = calculate_free_way_to_seat($persons[$value['ID']], $cabin);
                                if (!$free) return (FALSE);

                                if ($free['status']) {
                                    $cabin[$key]['x']                                               = FALSE;
                                    $cabin[$key]['status']                                          = 'slipping';
                                    $cabin[$key]['seat']['now']                                     = $free['outer'];
                                    $seatsbo[$persons[$value['ID']]['seat']['row']][$free['final']] = $value['ID'];
                                    if ($free['outer'] == $free['final']) {
                                        $cabin[$key]['status']            = 'seated';
                                        $cabin[$key]['slipping']['start'] = $t;
                                        $cabin[$key]['slipping']['end']   = $t;
                                        $cabin[$key]['seated']['start']   = $t;
                                        $cabin[$key]['seated']['end']     = $t;
                                    } else {
                                        $cabin[$key]['slipping']['start'] = $t;
                                        $cabin[$key]['slipping']['end']   = $t + $free['left'] * $persons[$value['ID']]['v']['seat'] * $interval - 0.001;
                                    }
                                } else {
                                    $cabin[$key]['status'] = 'waiting';
                                    $m                     = 0;
                                    foreach ($free['waiting_for'] as $wfkey => $wftemp) {
                                        $cabin[$wftemp]['status']           = 'hopping';
                                        $cabin[$wftemp]['hopping']['start'] = $t;
                                        $cabin[$wftemp]['hopping']['end']   = $t + ($free['outer'] - 1 - $wfkey) * $persons[$wftemp]['v']['seat'] * $interval - 0.001;
                                        $m++;
                                        $temps[]             = $wftemp;
                                        $fintime             = $t + ($free['outer'] - 1 - $wfkey) * $persons[$wftemp]['v']['seat'] + 6 + function_weibull_distribution(4, 1.9, 20) + 2 * $interval;
                                        $cabin[$wftemp]['x'] = $cabin[$key]['x'] + $rowdist / 2;
                                    }
                                    $x = 0;
                                    if ($m > 1) {
                                        $mint = 0;
                                        foreach ($temps as $tmpv) {
                                            if ($cabin[$tmpv]['hopping']['end'] > $mint + 4 * $interval) $mint = $cabin[$tmpv]['hopping']['end'] + 2 * $interval + 2 * $x * $interval;
                                            $x++; # $x ist wichtig, damit der ausensitzende mit einem Zeitstrafe belegt wird und so den innensitzenden wieder in die Reihe lässt, bevor er reinrutscht
                                        }
                                        $fintime = $mint;
                                    }
                                    $cabin[$key]['waiting']['start'] = $t;
                                    $cabin[$key]['waiting']['end']   = $fintime - 2 * $interval;
                                }
                            }
                        } elseif ($value['status'] == 'waiting') {
                            $free = calculate_free_way_to_seat($persons[$value['ID']], $cabin);
                            if ($value['waiting']['end'] <= $t) {
                                $cabin[$key]['x']                                                      = FALSE;
                                $cabin[$key]['status']                                                 = 'slipping';
                                $cabin[$key]['seat']['now']                                            = $free['outer'];
                                $seatsbo[$persons[$value['ID']]['seat']['row']][$cabin[$key]['final']] = $value['ID'];

                                # Es muss nicht erneut geprüft werden, ob jemandim Weg ist, da es kein "überholen" im Gang gibt...

                                if ($cabin[$key]['outer'] == $cabin[$key]['final']) {
                                    $cabin[$key]['status']            = 'seated';
                                    $cabin[$key]['slipping']['start'] = $t;
                                    $cabin[$key]['slipping']['end']   = $t;
                                    $cabin[$key]['seated']['start']   = $t;
                                    $cabin[$key]['seated']['end']     = $t;
                                } else {
                                    $cabin[$key]['slipping']['start'] = $t;
                                    $cabin[$key]['slipping']['end']   = $t + $free['left'] * $persons[$value['ID']]['v']['seat'] * $interval - 0.001;
                                }
                            }
                        } elseif ($value['status'] == 'slipping') { # Passagier rutscht rein...
                            if ($value['slipping']['end'] <= $t) {
                                $cabin[$key]['status']          = 'seated';
                                $cabin[$key]['seated']['start'] = $t;
                                $cabin[$key]['seated']['end']   = $t;
                            }
                        } elseif ($value['status'] == 'hopping') { # Passagier rutscht raus...
                            $free = calculate_free_way_to_seat($persons[$value['ID']], $cabin);
                            if ($value['hopping']['end'] <= $t) {
                                $cabin[$key]['x']                                                      = FALSE;
                                $cabin[$key]['status']                                                 = 'slipping';
                                $cabin[$key]['seat']['now']                                            = $free['outer'];
                                $seatsbo[$persons[$value['ID']]['seat']['row']][$cabin[$key]['final']] = $value['ID'];

                                # Es muss nicht erneut geprüft werden, ob jemandim Weg ist, da Wartezeit für durchrutschenden bereits bei free_way_to_seat-Funktion gesetzt wurde

                                if ($cabin[$key]['outer'] == $cabin[$key]['final']) {
                                    $cabin[$key]['status']            = 'seated';
                                    $cabin[$key]['slipping']['start'] = $t;
                                    $cabin[$key]['slipping']['end']   = $t;
                                    $cabin[$key]['seated']['start']   = $t;
                                    $cabin[$key]['seated']['end']     = $t;
                                } else {
                                    $cabin[$key]['slipping']['start'] = $t;
                                    $cabin[$key]['slipping']['end']   = $t + $free['left'] * $persons[$value['ID']]['v']['seat'] * $interval - 0.001;
                                }
                            }
                        }
                    } else {
                        $v++;
                    }
                    if ($v == $charge['seats_used']) $boarded = TRUE; # Wenn alle Plätze belegt sind, ist er fertig... :-)
                    if (isset($summary['statistics']['conflicts'][$key])) {
                        if ($summary['statistics']['conflicts'][$key] < $m) $summary['statistics']['conflicts'][$key] = $m;
                    } else {
                        $summary['statistics']['conflicts'][$key] = $m;
                    }
                } #$cabin_plot[] = $cabin;
            }
            break;
    }
    $cabin['duration']          = $t - $door[0]['time'];
    $cabin['boarding_end_time'] = $t;
    return (array($cabin));
}

function generate_checkin($boarding = 'normal', $persons = FALSE) {
    global $blocksize;
    $blocksize = 0;

    if ($boarding == 'random') return (TRUE);
    if ($persons === FALSE) return (FALSE);

    global $group;
    global $charge;

    if (DEV) echo 'Lade Bestuhlung des Luftfahrzeugs...<br />';

    $seating_plan = generate_aircraft_plan();

    $temp_groups = $group['list'];
    shuffle($temp_groups);

    if (DEV) echo 'Generiere Platzkarten f&uuml;r die Passagiere...<br />';

    foreach ($temp_groups as $key => $value) {
        $temp         = calculate_free_seat_surround($value['size'], $seating_plan);
        $seating_plan = $temp[1];
        if (!$temp[0]) return (FALSE);
        foreach ($temp[0] as $seats) {
            $temp_groups[$key]['seats'][] = $seats[0] . ' - ' . $seats[1];
            #if($temp[2] == 'diff_1') $temp_groups[$key]['diff'][] = 'diff';
            #if($temp[2] == 'diff_2') $temp_groups[$key]['diff2'][] = 'diff2';
            #if($temp[2] == 'diff_3') $temp_groups[$key]['diff3'][] = 'diff3';
        }
        if (isset($seating_plan['0er'])) die('NULLER erzeugt...');
    }

    if (VIZ_BOARDCARD) {
        # Visualisierung der Sitzkarten ... Ein sitz ist 5*5px - danach ein Platzhalter, ebenfalls 5*5px; border ist 1px; position muss absolut sein...
        global $group_vis;
        $anz       = count($temp_groups);
        $check     = array();
        $k         = 0;
        $group_vis = '<div style="margin: 0 auto;position: relative; top: 10px; width: 90%; display: block; overflow: hidden; min-height: 65px; border: 1px solid #888">';
        foreach ($temp_groups as $key => $value) {
            $color = '#';
            for ($i = 0; $i < 6; $i++) {
                $v     = intval(rand(6500000, 8000000) / 100000);
                $add   = ($v < 71) ? chr($v) : intval(abs($v - 80) * rand(0, 1000) / 1001);
                $color .= $add;
            }
            $temp_groups[$key]['color'] = $color;
            switch (get_seats_by_airplane('seats_left')) {
                case 3:
                    foreach ($value['seats'] as $temp) {
                        $set = explode(' - ', $temp, 2);
                        if (isset($check[$set[0]][$set[1]])) {
                            if ($check[$set[0]][$set[1]] === TRUE) die('Ueberbucht...');
                        }
                        $check[$set[0]][$set[1]] = TRUE;

                        if (($set[0] + 2) % 2 == 0) $left = $set[0] * 10;
                        else $left = ($set[0] - 1) * 10;
                        switch ($set[1]) {
                            case 'A':
                                $top = 10;
                                break;
                            case 'B':
                                $top = 15;
                                break;
                            case 'C':
                                $top = 20;
                                break;
                            case 'D':
                                $top = 34;
                                break;
                            case 'E':
                                $top = 39;
                                break;
                            case 'F':
                                $top = 44;
                                break;

                        }

                        $k++;
                        $group_vis .= '<div style="border: 1px solid #000; width:5px; height: 5px; position: absolute; top: ' . $top . 'px;left: ' . ($left + 5) . 'px; background-color: ' . $color . '"></div>';
                    }
                    break;
                case 2:
                    foreach ($value['seats'] as $temp) {
                        $set = explode(' - ', $temp, 2);
                        if (isset($check[$set[0]][$set[1]])) {
                            if ($check[$set[0]][$set[1]] === TRUE) die('Ueberbucht...');
                        }
                        $check[$set[0]][$set[1]] = TRUE;

                        if (($set[0] + 2) % 2 == 0) $left = $set[0] * 10;
                        else $left = ($set[0] - 1) * 10;
                        switch ($set[1]) {
                            case 'A':
                                $top = 10;
                                break;
                            case 'B':
                                $top = 15;
                                break;
                            case 'C':
                                $top = 29;
                                break;
                            case 'D':
                                $top = 34;
                                break;
                        }

                        $k++;
                        $group_vis .= '<div style="border: 1px solid #000; width:5px; height: 5px; position: absolute; top: ' . $top . 'px;left: ' . ($left + 5) . 'px; background-color: ' . $color . '"></div>';
                    }
                    break;
            }
        }
        #_pre($temp_groups);
        $group_vis .= '</div>';

    }

    if (BOARDINGTYP == 'blocked') {
        foreach ($seating_plan as $value) {
            $trem      = $value['count'] - $value['available'];
            $blocksize = $trem / BLOCKCT;
            if (!is_int($blocksize)) {
                $blocksize = intval($blocksize) + 1; # Einer mehr, als der abgeschnittene Wert...
            }
            break;
        }
    }
    return ($temp_groups);
}

function generate_child_quan($max = 1) {
    $random = rand(0, 1000) / 1000;

    if ($max == 0) return (0);

    if ($random > (($max < 3) ? 1.1 : 0.9)) return (3);
    elseif ($random > (($max < 2) ? 1.1 : 0.7)) return (2);
    else return (1);
}

function generate_normal_quan($quan = 2, $rate = 3) {

    switch ($quan) {
        case 'normal':
            if ($rate == 0) return (FALSE);
            if ($_REQUEST['groups'] == 'nu') $anz = 1;
            else $anz = function_weibull_distribution(1.9, 1.2);
            $anz = ($anz < 1) ? 1 : $anz;
            $anz = ($anz > 10) ? 10 : $anz;
            $anz = ($anz > $rate) ? $rate : $anz;
            return (intval($anz));
            break;
        case 4:
            $min = 2;
            break;
        case 3:
            $min = 2;
            break;
        case 'biz':
            if ($rate == 0) return (FALSE);
            if ($_REQUEST['groups'] == 'nu') $anz = 1;
            else $anz = function_weibull_distribution(1.4, 1) + 1;
            $anz = ($anz < 1) ? 1 : $anz;
            $anz = ($anz > 10) ? 10 : $anz;
            $anz = ($anz > $rate) ? $rate : $anz;
            return (intval($anz));
            break;
        case 'old':
            $min = 0;
            break;
        default:
            $min = 1;
            break;
    }

    $max = ($rate > 5) ? 4 : ($rate > 3) ? 3 : 2.5;

    $max = ($_REQUEST['groups'] == 'nu') ? 0 : $max;
    $anz = intval(rand((1000 * $min), (1000 * $max)) / 1000 + 0.5);

    return ($anz);
}

function generate_old_quan($max = 1) {
    $random = rand(0, 1000) / 1000;

    if ($max == 0) return (0);
    if ($_REQUEST['groups'] == 'nu') return (1);

    if ($random > (($max < 4) ? 1.1 : 0.65)) return (4);
    elseif ($random > (($max < 3) ? 1.1 : 0.50)) return (3);
    elseif ($random > (($max < 2) ? 1.1 : 0.15)) return (2);
    else return (1);
}

function generate_gap_to_front() {
    return (rand(40, 80));
}

function generate_group() {
    global $group;
    global $charge;

    $z       = $group['pre_ID'] + 1;
    $trigger = (empty($group['list'][$z]['size']) || $group['list'][$z]['size'] === FALSE) ? 'new' : 'used';

    switch ($trigger) {
        case 'new':
            $group['list'][$z]['assigned'] = array('biz' => 0, 'normal' => 0, 'old' => 0, 'child' => 0);
            if ($charge['unassigned']['child'] > 0) {
                $quan_child = generate_child_quan($charge['unassigned']['child']);
                $rate       = -4 * $charge['unassigned']['child'] + $charge['unassigned']['normal'];

                $quan_normal = generate_normal_quan($quan_child, $rate);

                if ($charge['unassigned']['normal'] < $quan_normal) {
                    if ($charge['unassigned']['normal'] > 0) $quan_normal = $charge['unassigned']['normal'];
                    else {
                        $charge['unassigned']['normal'] = $charge['unassigned']['child'];
                        $charge['unassigned']['child']  = 0;
                        return (FALSE);
                    }
                }

                $charge['unassigned']['child']  = $charge['unassigned']['child'] - $quan_child;
                $charge['unassigned']['normal'] = $charge['unassigned']['normal'] - $quan_normal;

                $group['list'][$z]['size']               = $quan_normal + $quan_child;
                $group['list'][$z]['remaining']          = $group['list'][$z]['size'];
                $group['list'][$z]['ID']                 = $z;
                $group['list'][$z]['typ']                = 'child';
                $group['list'][$z]['quantity']['normal'] = $quan_normal;
                $group['list'][$z]['quantity']['child']  = $quan_child;
                $group['list'][$z]['quantity']['old']    = 0;
                $group['list'][$z]['quantity']['biz']    = 0;
                $group['list'][$z]['power']              = rand(0, intval(100 - rand(0, 100 * $group['list'][$z]['size']) / 100 * $quan_child));
                $group['list'][$z]['seating']            = rand(intval(rand(500, 1000) / 1000 * pow($quan_child, 4)), 100);
                $group['list'][$z]['duty']               = rand(0, 50);

                $group['list'][$z]['assigned']['child']++;

                return (array('typ' => 'child', 'group' => array('set' => TRUE, 'id' => $group['list'][$z]['ID'], 'duty' => $group['list'][$z]['duty'], 'power' => $group['list'][$z]['power'], 'seating' => $group['list'][$z]['seating'])));
            } elseif ($charge['unassigned']['old'] > 0) {
                $quan_old    = generate_old_quan($charge['unassigned']['old']);
                $rate        = -2 * $charge['unassigned']['old'] + $charge['unassigned']['normal'];
                $quan_normal = generate_normal_quan('old', $rate);

                if ($charge['unassigned']['normal'] < $quan_normal) {
                    if ($charge['unassigned']['normal'] > 0) $quan_normal = $charge['unassigned']['normal'];
                    else {
                        $charge['unassigned']['normal'] = $charge['unassigned']['old'];
                        $charge['unassigned']['old']    = 0;
                        return (FALSE);
                    }
                }

                $charge['unassigned']['old']    = $charge['unassigned']['old'] - $quan_old;
                $charge['unassigned']['normal'] = $charge['unassigned']['normal'] - $quan_normal;

                $group['list'][$z]['size']               = $quan_normal + $quan_old;
                $group['list'][$z]['remaining']          = $group['list'][$z]['size'];
                $group['list'][$z]['ID']                 = $z;
                $group['list'][$z]['typ']                = 'old';
                $group['list'][$z]['quantity']['normal'] = $quan_normal;
                $group['list'][$z]['quantity']['old']    = $quan_old;
                $group['list'][$z]['quantity']['child']  = 0;
                $group['list'][$z]['quantity']['biz']    = 0;
                $group['list'][$z]['power']              = rand(0, intval(100 - rand(0, 100 * $group['list'][$z]['size']) / 100 * 1.5 * $quan_old));
                $group['list'][$z]['seating']            = rand(intval(rand(500, 1000) / 1000 * pow($quan_old, 2)), 100);
                $group['list'][$z]['duty']               = rand(0, 50);

                $group['list'][$z]['assigned']['old']++;

                return (array('typ' => 'old', 'group' => array('set' => TRUE, 'id' => $group['list'][$z]['ID'], 'duty' => $group['list'][$z]['duty'], 'power' => $group['list'][$z]['power'], 'seating' => $group['list'][$z]['seating'])));
            } elseif ($charge['unassigned']['normal'] > 0) {
                $quan_normal = generate_normal_quan('normal', $charge['unassigned']['normal']);
                if ($quan_normal === FALSE) return (FALSE);
                $charge['unassigned']['normal'] = $charge['unassigned']['normal'] - $quan_normal;

                $group['list'][$z]['size']               = $quan_normal;
                $group['list'][$z]['remaining']          = $group['list'][$z]['size'];
                $group['list'][$z]['ID']                 = $z;
                $group['list'][$z]['typ']                = 'normal';
                $group['list'][$z]['quantity']['normal'] = $quan_normal;
                $group['list'][$z]['quantity']['old']    = 0;
                $group['list'][$z]['quantity']['child']  = 0;
                $group['list'][$z]['quantity']['biz']    = 0;
                $group['list'][$z]['power']              = rand(50, 100);
                $group['list'][$z]['seating']            = rand(0, 100);
                $group['list'][$z]['duty']               = rand(0, 50);

                $group['list'][$z]['assigned']['normal']++;

                return (array('typ' => 'normal', 'group' => array('set' => TRUE, 'id' => $group['list'][$z]['ID'], 'duty' => $group['list'][$z]['duty'], 'power' => $group['list'][$z]['power'], 'seating' => $group['list'][$z]['seating'])));
            } elseif ($charge['unassigned']['biz'] > 0) {
                $quan_biz = generate_normal_quan('biz', $charge['unassigned']['biz']);

                $charge['unassigned']['biz'] = $charge['unassigned']['biz'] - $quan_biz;

                $group['list'][$z]['size']               = $quan_biz;
                $group['list'][$z]['remaining']          = $group['list'][$z]['size'];
                $group['list'][$z]['ID']                 = $z;
                $group['list'][$z]['typ']                = 'biz';
                $group['list'][$z]['quantity']['biz']    = $quan_biz;
                $group['list'][$z]['quantity']['normal'] = 0;
                $group['list'][$z]['quantity']['old']    = 0;
                $group['list'][$z]['quantity']['child']  = 0;
                $group['list'][$z]['power']              = rand(0, 50);
                $group['list'][$z]['seating']            = rand(0, 50);
                $group['list'][$z]['duty']               = rand(0, 50);

                $group['list'][$z]['assigned']['biz']++;

                return (array('typ' => 'biz', 'group' => array('set' => TRUE, 'id' => $group['list'][$z]['ID'], 'duty' => $group['list'][$z]['duty'], 'power' => $group['list'][$z]['power'], 'seating' => $group['list'][$z]['seating'])));
            }
            break;
        case 'used':

            $group['list'][$z]['remaining']--;
            if ($group['list'][$z]['remaining'] <= 1) {
                $group['pre_ID']++;
            }

            if (($group['list'][$z]['assigned']['child'] - $group['list'][$z]['quantity']['child']) < 0) {
                $group['list'][$z]['assigned']['child']++;
                return (array('typ' => 'child', 'group' => array('set' => TRUE, 'id' => $group['list'][$z]['ID'], 'duty' => $group['list'][$z]['duty'], 'power' => $group['list'][$z]['power'], 'seating' => $group['list'][$z]['seating'])));
            } elseif (($group['list'][$z]['assigned']['normal'] - $group['list'][$z]['quantity']['normal']) < 0) {
                $group['list'][$z]['assigned']['normal']++;
                return (array('typ' => 'normal', 'group' => array('set' => TRUE, 'id' => $group['list'][$z]['ID'], 'duty' => $group['list'][$z]['duty'], 'power' => $group['list'][$z]['power'], 'seating' => $group['list'][$z]['seating'])));
            } elseif (($group['list'][$z]['assigned']['biz'] - $group['list'][$z]['quantity']['biz']) < 0) {
                $group['list'][$z]['assigned']['biz']++;
                return (array('typ' => 'biz', 'group' => array('set' => TRUE, 'id' => $group['list'][$z]['ID'], 'duty' => $group['list'][$z]['duty'], 'power' => $group['list'][$z]['power'], 'seating' => $group['list'][$z]['seating'])));
            } elseif (($group['list'][$z]['assigned']['old'] - $group['list'][$z]['quantity']['old']) < 0) {
                $group['list'][$z]['assigned']['old']++;
                return (array('typ' => 'old', 'group' => array('set' => TRUE, 'id' => $group['list'][$z]['ID'], 'duty' => $group['list'][$z]['duty'], 'power' => $group['list'][$z]['power'], 'seating' => $group['list'][$z]['seating'])));
            } else {
                return (FALSE);
            }
            break;
        default:
            echo 'Fehler bei Gruppengenerierung...<br />';
            break;
    }
}

function generate_single_output($summary = FALSE, $time = 'nicht verf&uuml;gbar') {
    global $group_vis;
    global $grouhist;
    if (!$summary) {
        $out = 'Fehler w&auml;hrend der Berechnung. Keine Ergebnisse verf&uuml;gbar';
    } else {
        #_pre($summary);
        $out = '<!--more--><h2 class="center">Auswertung f&uuml;r Boardingsimulation: ' . $summary['misc']['szname'] . '</h2>';
        $out .= '<div style="margin: 0 auto; width: 300px; padding: 5px;border: 1px solid #888">';
        if ($summary['misc']['typ'] == 'A320') $out .= '<img src="http://boarding.martin-goerner.com/wp-content/uploads/2012/07/A320-200-with-pass-br-flickr-CCL-redlegsfan21-300x225.jpg" title="A320 by flickr-CCL-redlegsfan21" alt="Bild eines A320 am Gate mit einer Brücke" />';
        elseif ($summary['misc']['typ'] == 'B737') $out .= '<img src="http://boarding.martin-goerner.com/wp-content/uploads/2012/07/B737-800-with-pass-br-flickr-CCL-simplyalex-300x191.jpg" title="B737 by flickr-CCL-simplyalex" alt="Bild einer B737 am Gate mit einer Brücke" />';
        elseif ($summary['misc']['typ'] == 'ERJ-190') $out .= '<img src="http://boarding.martin-goerner.com/wp-content/uploads/2012/07/erj190-flickr-ccl-syume-300x225.jpg" title="ERJ 190 by flickr-CCL-s.yume" alt="Bild einer Embraer ERJ-190 am Gate mit einer Brücke" />';
        $out .= '</div>';
        if ($_REQUEST['vis'] == 'nu' && strpos($group_vis, 'position: absolute;') !== FALSE) {
            $out .= '<div style="display: block; margin-bottom: 20px"><h4 class="center" style="text-decoration: underline; font-weight: bold; margin: 10px 0px 5px 0px">Darstellung der Sitzplatzvergabe nach Gruppen (Farbcodiert)</h4>';
            $out .= '<div style="border: 1px solid #DDD; background-color: #EEE; padding: 2px; width: 140px; margin: 0 auto; color: #888"><span style="cursor:pointer;" onclick="spoil(\'groupflow\');"><i>Gruppenfluss anzeigen</i></span></div>';
            $out .= $group_vis;


            $out .= '<div id="groupflow" style="padding: 5px 5px 0px 5px;border-bottom: 1px solid #888; border-left: 1px solid #888;border-right: 1px solid #888;width: 550px; margin: 10px auto;display:none;"><p style="font-size: 10px; text-align: center"><i>Personenfluss durch die Flugzeugt&uuml;r mit zugeordneter Gruppen-ID und Farbcode identisch oben</i></p><p>' . $grouhist . '</p></div>';


            $out .= '</div>';
        }
        $out .= '<div><h3 class="center" style="margin: 10px 0px 10px 0px"><b><u>Startparameter und sonstige Kenngr&ouml;&szlig;en</u></b></h3>';
        $out .= '<div style="width: 560px; margin: 0 auto"><table width=100% align=center><tbody><tr><td>PAX:</td><td width="50px">' . $summary['load']['pax'] . '</td><td>Passagiere Typ: <i>Business</i></td><td width="50px">' . $summary['load']['pax_biz'] . '</td><td>Maschinentyp:</td><td>' . $summary['misc']['typ'] . '</td></tr>';
        $out .= '<tr><td>Handgepäck:</td><td>' . $summary['load']['baggages'] . '</td><td>Passagiere Typ: <i>Normal</i></td><td>' . $summary['load']['pax_normal'] . '</td><td>PBB-Länge:</td><td>' . $summary['misc']['way'] . 'm</td></tr>';
        $out .= '<tr><td>Reisegruppen:</td><td>' . $summary['load']['groups'] . '</td><td>Passagiere Typ: <i>&Auml;lter</i></td><td>' . $summary['load']['pax_old'] . '</td><td>Boarding-Typ:</td><td>' . $summary['misc']['boardingtyp'] . '</td></tr>';
        $out .= '<tr><td>SLF:</td><td>' . ($summary['statistics']['lfac_calc'] * 100) . '%</td><td>Passagiere Typ: <i>Kind</i></td><td>' . $summary['load']['pax_child'] . '</td><td>Berechnungsdauer:</td><td>' . (round($time, 3) + 0.85) . 's</td></tr>';
        $out .= '</tbody></table></div>';
        $out .= '</div><hr style="margin: 10px 30px 10px 30px"/>';

        $out .= '<div><h1 class="center">boardingspezifische Daten</h1>';
        $out .= '<div style="width: 250px; border: 1px dashed #CCC; padding: 10px; margin: 10px auto">
                    <p style="font-size: 20px; margin-bottom: 0px; text-align: center">Dauer des Boarding</p>
                    <p style="font-size: 12px; margin-top: 0px;  text-align: center"><i>~ mit PBB-Weg ~</i></p>
                    <p style=" text-align: center; font-size: 35px;margin: 5px">~ ' . intval($summary['misc']['duration']) . ' s</p></div>';

        # Tabelle der Gepäckstücke;

        $out .= '<div style="width: 400px; margin: 20px auto 20px;padding: 10px">';
        $out .= '<table width=100% align=center style="text-align: center;border-collapse: collapse;border-style: hidden;"><tbody>
                    <tr><td style="border: 1px dotted #AAA; padding: 2px"><b>Gepäckst&uuml;cke / Person</b></td><td style="border: 1px dotted #AAA; padding: 2px" width=30px>0</td><td style="border: 1px dotted #AAA; padding: 2px" width=30px>1</td><td style="border: 1px dotted #AAA; padding: 2px" width=30px>2</td><td style="border: 1px dotted #AAA; padding: 2px" width=30px>3</td><td style="border: 1px dotted #AAA; padding: 2px" width=30px>4</td></tr>
                    <tr><td style="border: 1px dotted #AAA;padding: 2px"><b>H&auml;ufigkeit</b></td><td style="border: 1px dotted #AAA; padding: 2px">' . $summary['statistics']['baggage'][0] . '</td><td style="border: 1px dotted #AAA; padding: 2px">' . $summary['statistics']['baggage'][1] . '</td><td style="border: 1px dotted #AAA; padding: 2px">' . $summary['statistics']['baggage'][2] . '</td><td style="border: 1px dotted #AAA; padding: 2px">' . $summary['statistics']['baggage'][3] . '</td><td style="border: 1px dotted #AAA; padding: 2px">' . $summary['statistics']['baggage'][4] . '</td></tr>
                </tbody></table>';
        $out .= '</div>';

        # Histogramme

        $out .= '<div style="padding: 5px; border: 1px solid #888; overflow: hidden"><h4 class="center"><b>Histogramm der Passagier-Geschwindigkeit*</b></h4>';

        $p[0]  = 0;
        $p[1]  = 0;
        $p[2]  = 0;
        $p[3]  = 0;
        $p[4]  = 0;
        $p[5]  = 0;
        $p[6]  = 0;
        $p[7]  = 0;
        $p[8]  = 0;
        $p[9]  = 0;
        $p[10] = 0;
        $p[11] = 0;
        $p[12] = 0;
        $p[13] = 0;
        foreach ($summary['statistics']['v_all'] as $value) {
            if ($value < 1) $p[0]++;
            elseif ($value < 1.2) $p[1]++;
            elseif ($value < 1.4) $p[2]++;
            elseif ($value < 1.6) $p[3]++;
            elseif ($value < 1.8) $p[4]++;
            elseif ($value < 2) $p[5]++;
            elseif ($value < 2.2) $p[6]++;
            elseif ($value < 2.4) $p[7]++;
            elseif ($value < 2.6) $p[8]++;
            elseif ($value < 2.8) $p[9]++;
            elseif ($value < 3) $p[10]++;
            elseif ($value < 3.2) $p[11]++;
            elseif ($value < 3.4) $p[12]++;
            elseif ($value >= 3.4) $p[13]++;
        }
        $maxh = 250;
        $max  = max($p);
        $max  = ($max == 0) ? 1 : $max;
        $out  .= '<div style="height: ' . ($maxh + 5) . 'px; width: 617px; margin: 0 auto">';
        for ($i = 0; $i < 14; $i++) {
            $out .= '<div style="float: left">
                        <div style="clear:both; width: 40px; height:' . (1 + $maxh * $p[$i] / $max) . 'px; background-color: #900; margin:' . ($maxh * (1 - $p[$i] / $max)) . 'px 3px 0px 1px"></div>
                        <div style="text-align: center; border-top: 1px solid #666; padding-top: 5px">';
            if ($i < 13) $out .= ' < ' . (1 + $i * 0.2);
            else $out .= (1 + ($i - 1) * 0.2) . ' >';
            $out .= '</div></div>';
        }
        $out .= '</div>';
        $out .= '</div>';
        $out .= '<div style="padding: 10px 0px 10px 5px;width: 500px; margin: 0 auto; border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888">';
        $out .= '<table width=100% align=center style="text-align: center"><tbody>';
        for ($i = 0; $i < 5; $i++) {
            if ($i == 0) $out .= '<tr><td width=24%>< ' . number_format((1 + $i * 0.2), 2) . ' m/s</td><td width=9%>' . $p[$i] . '</td><td width=24%>' . number_format((1 + ($i + 4) * 0.2), 2) . ' - ' . number_format((1 + ($i + 5) * 0.2) - 0.01, 2) . ' m/s</td><td width=10%>' . $p[($i + 5)] . '</td><td width=24%>' . number_format((1 + ($i + 9) * 0.2), 2) . ' - ' . number_format((1 + ($i + 10) * 0.2) - 0.01, 2) . ' m/s</td>
                                <td width=9%>' . $p[($i + 10)] . '</td></tr>';
            elseif ($i == 4) $out .= '<tr><td width=24%>' . number_format((1 + ($i - 1) * 0.2), 2) . ' - ' . number_format((1 + $i * 0.2) - 0.01, 2) . ' m/s</td><td width=9%>' . $p[$i] . '</td>
                                    <td width=24%>' . number_format((1 + ($i + 4) * 0.2), 2) . ' - ' . number_format((1 + ($i + 5) * 0.2) - 0.01, 2) . ' m/s</td><td width=10%>' . $p[($i + 5)] . '</td><td width=24%></td><td width=9%></td></tr>';
            elseif ($i == 3) $out .= '<tr><td width=24%>' . number_format((1 + ($i - 1) * 0.2), 2) . ' - ' . number_format((1 + $i * 0.2) - 0.01, 2) . ' m/s</td><td width=9%>' . $p[$i] . '</td>
                                      <td width=24%>' . number_format((1 + ($i + 4) * 0.2), 2) . ' - ' . number_format((1 + ($i + 5) * 0.2), 2) . ' m/s</td><td width=10%>' . $p[($i + 5)] . '</td>
                                      <td width=24%>> ' . number_format((1 + ($i + 9) * 0.2), 2) . ' m/s</td><td width=9%>' . $p[($i + 10)] . '</td></tr>';
            else $out .= '<tr><td width=24%>' . number_format((1 + ($i - 1) * 0.2), 2) . ' - ' . number_format((1 + $i * 0.2) - 0.01, 2) . ' m/s</td><td width=9%>' . $p[$i] . '</td><td width=24%>' . number_format((1 + ($i + 4) * 0.2), 2) . ' - ' . number_format((1 + ($i + 5) * 0.2) - 0.01, 2) . ' m/s</td><td width=10%>' . $p[($i + 5)] . '</td><td width=24%>' . number_format((1 + ($i + 9) * 0.2), 2) . ' - ' . number_format((1 + ($i + 10) * 0.2) - 0.01, 2) . ' m/s</td>
                         <td width=9%>' . $p[($i + 10)] . '</td></tr>';
        }
        $out .= '</tbody></table><p style="margin: 0; text-align: center; color: #888"><i>*Faktor f&uuml;r Geschwindigkeit im Flugzeug: 4/9</i></p>';
        $out .= '</div>';

        # Beginn der zweier-Reihe

        $out .= '<div style="width:48%; float: left">';
        $out .= '<div style="border: 1px solid #888; margin: 10px 0px 10px 0px"><h4 class="center"><b>Histogramm der Normal-PAX-Geschwindigkeit</b></h4>';

        $p[0]  = 0;
        $p[1]  = 0;
        $p[2]  = 0;
        $p[3]  = 0;
        $p[4]  = 0;
        $p[5]  = 0;
        $p[6]  = 0;
        $p[7]  = 0;
        $p[8]  = 0;
        $p[9]  = 0;
        $p[10] = 0;
        $p[11] = 0;
        $p[12] = 0;
        $p[13] = 0;
        foreach ($summary['statistics']['v_normal'] as $value) {
            if ($value < 1) $p[0]++;
            elseif ($value < 1.2) $p[1]++;
            elseif ($value < 1.4) $p[2]++;
            elseif ($value < 1.6) $p[3]++;
            elseif ($value < 1.8) $p[4]++;
            elseif ($value < 2) $p[5]++;
            elseif ($value < 2.2) $p[6]++;
            elseif ($value < 2.4) $p[7]++;
            elseif ($value < 2.6) $p[8]++;
            elseif ($value < 2.8) $p[9]++;
            elseif ($value < 3) $p[10]++;
            elseif ($value < 3.2) $p[11]++;
            elseif ($value < 3.4) $p[12]++;
            elseif ($value >= 3.4) $p[13]++;
        }
        $maxh = 125;
        $max  = max($p);
        if ($max == 0) {
            $max = 1;
            $na  = TRUE;
        } else $na = FALSE;

        $out .= '<div style="position:relative; width: 290px; margin: 0 auto"><div style="height: ' . ($maxh + 2) . 'px; width: 255px; margin: 0 auto;border-left: 1px solid #888">';
        if ($na) $out .= '<div style="overflow:hidden;background-position: center center;background-image: url(http://boarding.martin-goerner.com/wp-content/uploads/2012/07/29.gif); background-repeat: no-repeat">';

        for ($i = 0; $i < 14; $i++) {
            $out .= '<div style="float: left">
                        <div style="clear:both; width: 15px; height:' . (1 + $maxh * $p[$i] / $max) . 'px; background-color: #900; margin:' . ($maxh * (1 - $p[$i] / $max)) . 'px 2px 0px 1px"></div>
                        <div style="border-top: 1px solid #666;">';
            $out .= '</div></div>';
        }
        if ($na) $out .= '</div>';
        $out .= '</div>';
        $out .= '<div style="position: absolute; top: -3px; left: -3px">' . $max . '</div></div>';
        $out .= '<div style="clear: both"><p style="font-size: 11px; text-align: center; color: #AAA"><i>Intervalle identisch Hauptgeschwindigkeits-Histogramm</i></p></div></div>';

        $out .= '<div style="border: 1px solid #888; margin: 10px 0px 10px 0px"><h4 class="center"><b>Histogramm der &Auml;lteren-PAX-Geschwindigkeit</b></h4>';

        $p[0]  = 0;
        $p[1]  = 0;
        $p[2]  = 0;
        $p[3]  = 0;
        $p[4]  = 0;
        $p[5]  = 0;
        $p[6]  = 0;
        $p[7]  = 0;
        $p[8]  = 0;
        $p[9]  = 0;
        $p[10] = 0;
        $p[11] = 0;
        $p[12] = 0;
        $p[13] = 0;
        foreach ($summary['statistics']['v_old'] as $value) {
            if ($value < 1) $p[0]++;
            elseif ($value < 1.2) $p[1]++;
            elseif ($value < 1.4) $p[2]++;
            elseif ($value < 1.6) $p[3]++;
            elseif ($value < 1.8) $p[4]++;
            elseif ($value < 2) $p[5]++;
            elseif ($value < 2.2) $p[6]++;
            elseif ($value < 2.4) $p[7]++;
            elseif ($value < 2.6) $p[8]++;
            elseif ($value < 2.8) $p[9]++;
            elseif ($value < 3) $p[10]++;
            elseif ($value < 3.2) $p[11]++;
            elseif ($value < 3.4) $p[12]++;
            elseif ($value >= 3.4) $p[13]++;
        }
        $maxh = 125;
        $max  = max($p);
        if ($summary['load']['pax_old'] == 0) {
            $max = 1;
            $na  = TRUE;
        } else $na = FALSE;

        $out .= '<div style="position:relative; width: 290px; margin: 0 auto"><div style="border-left: 1px solid #888;height: ' . ($maxh + 2) . 'px; width: 255px; margin: 0 auto">';
        if ($na) $out .= '<div style="overflow:hidden;background-position: center center;background-image: url(http://boarding.martin-goerner.com/wp-content/uploads/2012/07/29.gif); background-repeat: no-repeat">';
        if (!isset($max) || $max == 0) $max = 1;
        for ($i = 0; $i < 14; $i++) {
            $out .= '<div style="float: left">
                        <div style="clear:both; width: 15px; height:' . (1 + $maxh * $p[$i] / $max) . 'px; background-color: #900; margin:' . ($maxh * (1 - $p[$i] / $max)) . 'px 2px 0px 1px"></div>
                        <div style="text-align: center;font-size: 8px; border-top: 1px solid #666; padding-top: 5px">';
            $out .= '</div></div>';
        }
        if ($na) $out .= '</div>';
        $out .= '</div>';
        $out .= '<div style="position: absolute; top: -3px; left: -3px">' . $max . '</div></div>';
        $out .= '<div style="clear: both"><p style="font-size: 11px; text-align: center; color: #AAA"><i>Intervalle identisch Hauptgeschwindigkeits-Histogramm</i></p></div></div>';

        $out .= '</div>';

        $out .= '<div style="width:48%; float: right">';
        $out .= '<div style="border: 1px solid #888; margin: 10px 0px 10px 0px"><h4 class="center"><b>Histogramm der Business-PAX-Geschwindigkeit</b></h4>';

        $p[0]  = 0;
        $p[1]  = 0;
        $p[2]  = 0;
        $p[3]  = 0;
        $p[4]  = 0;
        $p[5]  = 0;
        $p[6]  = 0;
        $p[7]  = 0;
        $p[8]  = 0;
        $p[9]  = 0;
        $p[10] = 0;
        $p[11] = 0;
        $p[12] = 0;
        $p[13] = 0;
        foreach ($summary['statistics']['v_biz'] as $value) {
            if ($value < 1) $p[0]++;
            elseif ($value < 1.2) $p[1]++;
            elseif ($value < 1.4) $p[2]++;
            elseif ($value < 1.6) $p[3]++;
            elseif ($value < 1.8) $p[4]++;
            elseif ($value < 2) $p[5]++;
            elseif ($value < 2.2) $p[6]++;
            elseif ($value < 2.4) $p[7]++;
            elseif ($value < 2.6) $p[8]++;
            elseif ($value < 2.8) $p[9]++;
            elseif ($value < 3) $p[10]++;
            elseif ($value < 3.2) $p[11]++;
            elseif ($value < 3.4) $p[12]++;
            elseif ($value >= 3.4) $p[13]++;
        }
        $maxh = 125;
        $max  = max($p);
        if ($summary['load']['pax_biz'] == 0) {
            $max = 1;
            $na  = TRUE;
        } else $na = FALSE;

        $out .= '<div style="position:relative; width: 290px; margin: 0 auto"><div style="border-left: 1px solid #888;height: ' . ($maxh + 2) . 'px; width: 255px; margin: 0 auto">';
        if ($na) $out .= '<div style="overflow:hidden;background-position: center center;background-image: url(http://boarding.martin-goerner.com/wp-content/uploads/2012/07/29.gif); background-repeat: no-repeat">';

        for ($i = 0; $i < 14; $i++) {
            $out .= '<div style="float: left">
                        <div style="clear:both; width: 15px; height:' . (1 + $maxh * $p[$i] / $max) . 'px; background-color: #900; margin:' . ($maxh * (1 - $p[$i] / $max)) . 'px 2px 0px 1px"></div>
                        <div style="text-align: center;font-size: 8px; border-top: 1px solid #666; padding-top: 5px">';
            $out .= '</div></div>';
        }
        if ($na) $out .= '</div>';
        $out .= '</div>';
        $out .= '<div style="position: absolute; top: -3px; left: -3px">' . $max . '</div></div>';
        $out .= '<div style="clear: both"><p style="font-size: 11px; text-align: center; color: #AAA"><i>Intervalle identisch Hauptgeschwindigkeits-Histogramm</i></p></div></div>';
        $out .= '<div style="border: 1px solid #888; margin: 10px 0px 10px 0px"><h4 class="center"><b>Histogramm der Kinder-PAX-Geschwindigkeit</b></h4>';

        $p[0]  = 0;
        $p[1]  = 0;
        $p[2]  = 0;
        $p[3]  = 0;
        $p[4]  = 0;
        $p[5]  = 0;
        $p[6]  = 0;
        $p[7]  = 0;
        $p[8]  = 0;
        $p[9]  = 0;
        $p[10] = 0;
        $p[11] = 0;
        $p[12] = 0;
        $p[13] = 0;
        foreach ($summary['statistics']['v_child'] as $value) {
            if ($value < 1) $p[0]++;
            elseif ($value < 1.2) $p[1]++;
            elseif ($value < 1.4) $p[2]++;
            elseif ($value < 1.6) $p[3]++;
            elseif ($value < 1.8) $p[4]++;
            elseif ($value < 2) $p[5]++;
            elseif ($value < 2.2) $p[6]++;
            elseif ($value < 2.4) $p[7]++;
            elseif ($value < 2.6) $p[8]++;
            elseif ($value < 2.8) $p[9]++;
            elseif ($value < 3) $p[10]++;
            elseif ($value < 3.2) $p[11]++;
            elseif ($value < 3.4) $p[12]++;
            elseif ($value >= 3.4) $p[13]++;
        }
        $maxh = 125;
        $max  = max($p);
        if ($summary['load']['pax_child'] == 0) {
            $max = 1;
            $na  = TRUE;
        } else $na = FALSE;

        $out .= '<div style="position:relative; width: 290px; margin: 0 auto"><div style="border-left: 1px solid #888;height: ' . ($maxh + 2) . 'px; width: 255px; margin: 0 auto">';
        if ($na) $out .= '<div style="overflow:hidden;background-position: center center;background-image: url(http://boarding.martin-goerner.com/wp-content/uploads/2012/07/29.gif); background-repeat: no-repeat">';
        for ($i = 0; $i < 14; $i++) {
            $out .= '<div style="float: left">
                        <div style="clear:both; width: 15px; height:' . (1 + $maxh * $p[$i] / $max) . 'px; background-color: #900; margin:' . ($maxh * (1 - $p[$i] / $max)) . 'px 2px 0px 1px"></div>
                        <div style="text-align: center;font-size: 8px; border-top: 1px solid #666; padding-top: 5px"></div>';
            $out .= '</div>';
        }
        if ($na) $out .= '</div>';
        $out .= '</div>';
        $out .= '<div style="position: absolute; top: -3px; left: -3px">' . $max . '</div></div>';
        $out .= '<div style="clear: both"><p style="font-size: 11px; text-align: center; color: #AAA"><i>Intervalle identisch Hauptgeschwindigkeits-Histogramm</i></p></div></div>';
        $out .= '</div>';
        $out .= '</div>';
    }

    $out .= '<div style="clear:both"></div>';

    # Beginn der neuer neuen einer-Reihe
    if (PBB && $summary['load']['groups'] != $summary['load']['pax']) {
        $out .= '<div style="padding: 5px; border: 1px solid #888; overflow: hidden"><h4 class="center"><b>Histogramm der Gruppen-Geschwindigkeit in der PBB</b></h4>';

        $p[0]  = 0;
        $p[1]  = 0;
        $p[2]  = 0;
        $p[3]  = 0;
        $p[4]  = 0;
        $p[5]  = 0;
        $p[6]  = 0;
        $p[7]  = 0;
        $p[8]  = 0;
        $p[9]  = 0;
        $p[10] = 0;
        $p[11] = 0;
        $p[12] = 0;
        $p[13] = 0;
        foreach ($summary['statistics']['v_door_group']['speed'] as $value) {
            if ($value < 1) $p[0]++;
            elseif ($value < 1.2) $p[1]++;
            elseif ($value < 1.4) $p[2]++;
            elseif ($value < 1.6) $p[3]++;
            elseif ($value < 1.8) $p[4]++;
            elseif ($value < 2) $p[5]++;
            elseif ($value < 2.2) $p[6]++;
            elseif ($value < 2.4) $p[7]++;
            elseif ($value < 2.6) $p[8]++;
            elseif ($value < 2.8) $p[9]++;
            elseif ($value < 3) $p[10]++;
            elseif ($value < 3.2) $p[11]++;
            elseif ($value < 3.4) $p[12]++;
            elseif ($value >= 3.4) $p[13]++;
        }
        $maxh = 250;
        $max  = max($p);
        $max  = ($max == 0) ? 1 : $max;
        $out  .= '<div style="height: ' . ($maxh + 5) . 'px; width: 617px; margin: 0 auto">';
        for ($i = 0; $i < 14; $i++) {
            $out .= '<div style="float: left">
                        <div style="clear:both; width: 40px; height:' . (1 + $maxh * $p[$i] / $max) . 'px; background-color: #900; margin:' . ($maxh * (1 - $p[$i] / $max)) . 'px 3px 0px 1px"></div>
                        <div style="text-align: center; border-top: 1px solid #666; padding-top: 5px">';
            if ($i < 13) $out .= ' < ' . (1 + $i * 0.2);
            else $out .= (1 + ($i - 1) * 0.2) . ' >';
            $out .= '</div></div>';
        }
        $out .= '</div>';
        $out .= '</div>';
        $out .= '<div style="padding: 10px 0px 10px 5px;width: 500px; margin: 0 auto; border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888">';
        $out .= '<table width=100% align=center style="text-align: center"><tbody>';
        for ($i = 0; $i < 5; $i++) {
            if ($i == 0) $out .= '<tr><td width=24%>< ' . number_format((1 + $i * 0.2), 2) . ' m/s</td><td width=9%>' . $p[$i] . '</td><td width=24%>' . number_format((1 + ($i + 4) * 0.2), 2) . ' - ' . number_format((1 + ($i + 5) * 0.2) - 0.01, 2) . ' m/s</td><td width=10%>' . $p[($i + 5)] . '</td><td width=24%>' . number_format((1 + ($i + 9) * 0.2), 2) . ' - ' . number_format((1 + ($i + 10) * 0.2) - 0.01, 2) . ' m/s</td>
                                <td width=9%>' . $p[($i + 10)] . '</td></tr>';
            elseif ($i == 4) $out .= '<tr><td width=24%>' . number_format((1 + ($i - 1) * 0.2), 2) . ' - ' . number_format((1 + $i * 0.2) - 0.01, 2) . ' m/s</td><td width=9%>' . $p[$i] . '</td>
                                    <td width=24%>' . number_format((1 + ($i + 4) * 0.2), 2) . ' - ' . number_format((1 + ($i + 5) * 0.2) - 0.01, 2) . ' m/s</td><td width=10%>' . $p[($i + 5)] . '</td><td width=24%></td><td width=9%></td></tr>';
            elseif ($i == 3) $out .= '<tr><td width=24%>' . number_format((1 + ($i - 1) * 0.2), 2) . ' - ' . number_format((1 + $i * 0.2) - 0.01, 2) . ' m/s</td><td width=9%>' . $p[$i] . '</td>
                                      <td width=24%>' . number_format((1 + ($i + 4) * 0.2), 2) . ' - ' . number_format((1 + ($i + 5) * 0.2), 2) . ' m/s</td><td width=10%>' . $p[($i + 5)] . '</td>
                                      <td width=24%>> ' . number_format((1 + ($i + 9) * 0.2), 2) . ' m/s</td><td width=9%>' . $p[($i + 10)] . '</td></tr>';
            else $out .= '<tr><td width=24%>' . number_format((1 + ($i - 1) * 0.2), 2) . ' - ' . number_format((1 + $i * 0.2) - 0.01, 2) . ' m/s</td><td width=9%>' . $p[$i] . '</td><td width=24%>' . number_format((1 + ($i + 4) * 0.2), 2) . ' - ' . number_format((1 + ($i + 5) * 0.2) - 0.01, 2) . ' m/s</td><td width=10%>' . $p[($i + 5)] . '</td><td width=24%>' . number_format((1 + ($i + 9) * 0.2), 2) . ' - ' . number_format((1 + ($i + 10) * 0.2) - 0.01, 2) . ' m/s</td>
                         <td width=9%>' . $p[($i + 10)] . '</td></tr>';
        }
        $out .= '</tbody></table>';
        $out .= '</div>';
    }
    # Beginn der neuer Sitzplatzkonflikte

    $out .= '<div style="margin-top:15px; padding: 5px 5px 20px 5px; border: 1px solid #888; overflow: hidden"><h4 class="center"><b>Anzahl der Sitzplatzkonflikte / zugestiegenem Passagier</b></h4>';

    $ctp = count($summary['statistics']['conflicts']);

    $out .= '<div style="width: ' . (3 * $ctp + 10) . 'px; position:relative;padding: 0px 5px 0px 5px;margin: 0 auto; height: 151px; border-bottom: 1px solid #888; border-left: 1px solid #888">';

    for ($i = 0; $i < $ctp; $i++) {
        $out .= '<div style="float: left;width: 2px; margin: ' . (151 - 1 - $summary['statistics']['conflicts'][$i] * 75) . 'px 1px 0px 0px; background-color: #900; height: ' . (1 + $summary['statistics']['conflicts'][$i] * 75) . 'px"></div>';
    }
    $out .= '<div style="position: absolute;left: -13px; top: -3px">2</div>';
    $out .= '<div style="position: absolute;left: -13px; top: 72px">1</div>';
    $out .= '<div style="position: absolute;left: ' . intval((3 * $ctp - 10) / 4) . 'px; top: 155px">' . intval(($ctp) / 4) . '</div>';
    $out .= '<div style="position: absolute;left: ' . intval((3 * $ctp - 10) / 2) . 'px; top: 155px">' . intval(($ctp) / 2) . '</div>';
    $out .= '<div style="position: absolute;left: ' . intval(3 * (3 * $ctp - 10) / 4) . 'px; top: 155px">' . intval(3 * ($ctp) / 4) . '</div>';
    $out .= '<div style="position: absolute;left: ' . intval((3 * $ctp - 10) / 1) . 'px; top: 155px">' . intval(($ctp) / 1) . '</div>';
    $out .= '</div>';

    $out .= '</div>';

    #echo $out;die();
    return ($out);
}

function generate_persons($seats = 200, $pass = 180, $v_typ = 'norm', $group = 'norm', $baggage = 'norm', $compfort = 'norm', $charakter = 'norm', $seated = TRUE) {

    if (!is_numeric($pass) || !is_numeric($seats) || !is_bool($seated)) return (FALSE);

    $persons = init_persons_array();
    global $charge;
    global $summary;
    init_group_array();
    init_summary_array();
    global $group;

    for ($i = 0; $i < $pass; $i++) {
        $persons[$i]['ID'] = $i;
        $temp              = generate_group();
        if (!$temp) $i--;
        else {
            $persons[$i]['typ']   = $temp['typ'];
            $persons[$i]['group'] = $temp;
            $spd                  = generate_speed($persons[$i]['group']['typ']);
            if (!$spd) return (FALSE);
            $persons[$i]['v']['norm'] = round($spd, 3);
            $persons[$i]['v']['in']   = round(rand(60000, 80000) / 100000 * $spd, 3);
            $persons[$i]['v']['seat'] = rand(1, 3) / 2;
            $persons[$i]['baggage']   = generate_baggage(BOARDINGTYP);
            calculate_baggage_for_summary($persons[$i]['baggage']);
            $persons[$i]['compfort']                                          = generate_gap_to_front();
            $persons[$i]['is_alpha']                                          = generate_alpha();
            $group['list'][$persons[$i]['group']['group']['id']]['persons'][] = $i;
        }

    }

    $summary['load']['groups'] = $persons[($i - 1)]['group']['group']['id'] + 1;
    #_pre($persons); die();
    return ($persons);
}


function generate_speed($typ = 'norm') {
    switch ($typ) {
        case 'normal':
            $border = array(1.5, 2.5);
            $speed  = function_normal_random(2, 0.1);

            $speed = ($speed > $border[1]) ? $border[1] : ($speed < $border[0]) ? $border[0] : $speed;

            return ($speed);
            break;
        case 'biz':
            $border = array(2, 3.5);
            $speed  = function_normal_random(2.5, 0.08);

            $speed = ($speed > $border[1]) ? $border[1] : ($speed < $border[0]) ? $border[0] : $speed;

            return ($speed);
            break;
        case 'old':
            $border = array(1, 1.75);
            $speed  = function_normal_random(1.25, 0.1);

            $speed = ($speed > $border[1]) ? $border[1] : ($speed < $border[0]) ? $border[0] : $speed;

            return ($speed);
            break;
        case 'child':
            $border = array(1, 2.5);
            $speed  = function_normal_random(1.4, 0.08);

            $speed = ($speed > $border[1]) ? $border[1] : ($speed < $border[0]) ? $border[0] : $speed;

            return ($speed);
            break;
        default:
            return (1);
            break;
    }
}

function generate_summary($charge = FALSE, $persons = FALSE, $group = FALSE, $cabin = FALSE, $cabin_plot = FALSE) {
    global $doorspeed;
    global $summary;

    if ($cabin_plot) {
        _pre($cabin_plot);
        die();
    }
    global $wpdb;
    $r = $wpdb->get_results('SELECT `ID` FROM `' . $wpdb->prefix . 'posts` ORDER BY `ID` DESC LIMIT 1;');
    if ($_REQUEST['szname'] == '' || empty($_REQUEST['szname']) || trim($_REQUEST['szname']) == '') $szname = ' Nr. ' . $r[0]->ID . ': Lfz: ' . get_seats_by_airplane('typ') . ' - PAX: ' . $charge['seats_used'] . ' - ' . rand(1, 99999999);
    else $szname = $_REQUEST['szname'];
    $summary['misc']['szname'] = substr(preg_replace('![^0-9A-Z a-z]!', '', $szname), 0, 40);
    $summary['misc']['typ']    = get_seats_by_airplane('typ');

    if ($charge) {
        $summary['load']['max_pax']         = $charge['seats'];
        $summary['load']['pax']             = $charge['seats_used'];
        $summary['load']['pax_normal']      = $charge['people']['normal'];
        $summary['load']['pax_old']         = $charge['people']['old'];
        $summary['load']['pax_child']       = $charge['people']['child'];
        $summary['load']['pax_biz']         = $charge['people']['biz'];
        $summary['statistics']['lfac_used'] = $charge['seat_loading'];
        $summary['statistics']['lfac_calc'] = round($summary['load']['pax'] / $summary['load']['max_pax'], 3);
    }
    if ($persons) {
        $summary['load']['baggages'] = 0;
        $z                           = 0;
        $n                           = 0;
        $o                           = 0;
        $b                           = 0;
        $c                           = 0;
        foreach ($persons as $value) {
            $summary['load']['baggages']        = $summary['load']['baggages'] + $value['baggage'];
            $summary['statistics']['v_all'][$z] = $value['v']['norm'];
            if ($value['typ'] == 'normal') {
                $summary['statistics']['v_normal'][$n] = $value['v']['norm'];
                $n++;
            } elseif ($value['typ'] == 'old') {
                $summary['statistics']['v_old'][$o] = $value['v']['norm'];
                $o++;
            } elseif ($value['typ'] == 'child') {
                $summary['statistics']['v_child'][$c] = $value['v']['norm'];
                $c++;
            } elseif ($value['typ'] == 'biz') {
                $summary['statistics']['v_biz'][$b] = $value['v']['norm'];
                $b++;
            }
            $z++;
        }
        $summary['statistics']['bagfac'] = $summary['load']['baggages'] / $summary['load']['pax'];
    }
    if ($group) {
        foreach ($group as $value) {
            $summary['statistics']['group_size'][$value['size']]++;
            $summary['statistics']['group_size']['ct']++;
        }
    }
    if ($doorspeed) {
        $summary['statistics']['v_door_group']['speed'] = $doorspeed;
        $summary['statistics']['v_door_group']['min']   = min($doorspeed);
        $summary['statistics']['v_door_group']['max']   = max($doorspeed);
        $summary['statistics']['v_door_group']['ct']    = count($doorspeed);
    }
    if ($cabin) {
        #_pre($cabin);
        $summary['misc']['duration'] = $cabin['boarding_end_time'];
        $z                           = 0;
        foreach ($cabin as $key => $value) {
            if (!is_numeric($key)) continue;
            $summary['statistics']['t_loading_bags'][$z] = round($value['loading']['end'] - $value['loading']['start'], 3);
            $summary['statistics']['t_waiting'][$z]      = round($value['waiting']['end'] - $value['waiting']['start'], 3);
            $z++;
        }
    }

    #_pre($charge);_pre($summary);die();
    return ($summary);
}

function get_weighted_group_speed($array = FALSE, $persons = FALSE, $fa = 2) {
    if (!is_array($array) || !$array || !$persons) return (1);

    foreach ($array as $value) {
        $vls[] = $persons[$value]['v']['norm'];
    }
    usort($vls, "sort_major_to_minor");
    $z   = 1;
    $cz  = 0;
    $sum = 0;

    foreach ($vls as $value) {
        $sum = $sum + $value * $z;
        $z   = $z * $fa;
        $cz  = $cz + $z;
    }

    $v = $sum / ($cz / $fa);

    if (!is_numeric($v)) return (1);

    return (round($v, 3));

}

function generate_timestamp_on_door($group = FALSE, $persons = FALSE) {
    if (!$group || !$persons) return (FALSE);
    global $charge;
    global $doorspeed;
    global $door;

    $used_seats3 = pow($charge['seats_used'], 3);

    # Ordnen der Gruppen nach Seating
    $ttemp   = split_groups_by_size($group, $persons);
    $group   = $ttemp[0];
    $persons = $ttemp[1];

    $timestamp = 0;
    $c_time    = 0;
    $t         = 0;
    $j         = 0;
    $r         = 0;

    # Hier muss noch unterschieden werden, welcher Laufweg zugrunde gelegt wird und ob überholt werden kann
    #_pre($persons);

    switch (BOARDINGTYP) {
        case 'normal':
            usort($group, 'sort_group_by_seating');
            foreach ($group as $key => $value) {
                $z = 0;
                foreach ($value['persons'] as $person) {
                    $v        = ($z == 0) ? (0.5 - 0.5 * 0.5 * pow($j, 3) / $used_seats3) : 0.5;
                    $x        = round(function_exponential_distribution($v), 2);
                    $x        = ($z != 0 && $x > 6) ? 6 : $x;
                    $x        = ($x < 1.25) ? 1.25 : $x;
                    $time[$z] = ($x > 10) ? 10 : $x;
                    $c_time   = $c_time + $time[$z];
                    $z++;
                    $j++;
                }
                if ($z == 1 && PBB) {
                    $doorspeed[$r] = $persons[$value['persons'][0]]['v']['norm'];
                    $timedoor      = $c_time + PBBL / $persons[$value['persons'][0]]['v']['norm'];
                    if ($t != 0) $timedoor = ($timedoor < ($door[($t - 1)]['time'] + 1) && $t > 0) ? ($door[($t - 1)]['time'] + 1) : $timedoor;
                    $door[$t] = array('person' => $value['persons'][0], 'time' => round($timedoor, 3));
                    $t++;
                } elseif ($z > 1 && PBB) {
                    $v             = get_weighted_group_speed($value['persons'], $persons, 3);
                    $doorspeed[$r] = $v;
                    $timedoor      = $c_time + PBBL / $v;
                    if ($t != 0) $timedoor = ($timedoor < ($door[($t - 1)]['time'] + 1) && $t > 0) ? ($door[($t - 1)]['time'] + 1) : $timedoor;
                    foreach ($value['persons'] as $person) {
                        $door[$t] = array('person' => $person, 'time' => round($timedoor, 3));
                        $timedoor = $timedoor + 0.5;
                        $t++;
                    }
                } else {
                    foreach ($value['persons'] as $person) {
                        $timedoor = $c_time;
                        $door[$t] = array('person' => $person, 'time' => round($timedoor, 3));
                        $timedoor = $timedoor + 0.5;
                        $t++;
                    }
                }
                $r++;
            }
            break;

        case 'blocked':
            $tempgroup = array();
            global $blocksize;

            # 1. Reiheneinteilung kalkulieren ...
            foreach ($group as $key => $value) {
                $temp = explode(' - ', $value['seats'][0], 2);
                for ($i = BLOCKCT; $i > 0; $i--) {
                    if ($temp[0] >= $blocksize * ($i - 1)) {
                        $tempgroup[(BLOCKCT - $i)][] = $group[$key];
                        #echo 'Wenn '.$temp[0].' >= '.($blocksize * ($i-1)).' dann $tempgroup[('.(BLOCKCT - $i).')][] = $group['.$key.'];<br />';
                        break;
                    }
                }
            } # Nun steht in in jemden primary-Key die Blockgruppe drin...

            $blocktime = BLOCKTIME;
            $d         = 0;
            $r         = 0;
            $ctarray   = count($tempgroup);
            for ($i = 0; $i < $ctarray; $i++) {
                usort($tempgroup[$i], 'sort_group_by_seating');
                foreach ($tempgroup[$i] as $key => $value) {
                    $z = 0;
                    foreach ($value['persons'] as $person) {
                        $v        = ($z == 0) ? (0.5 - 0.5 * 0.5 * pow($j, 3) / $used_seats3) : 0.5;
                        $x        = round(function_exponential_distribution($v), 2);
                        $x        = ($z != 0 && $x > 6) ? 8 : $x;
                        $x        = ($x < 1.25) ? 1.25 : $x;
                        $time[$z] = ($x > 10) ? 10 : $x;
                        $c_time   = $c_time + $time[$z];
                        $z++;
                        $j++;
                    }
                    if ($z == 1 && PBB) {
                        $doorspeed[$r] = $persons[$value['persons'][0]]['v']['norm'];
                        $timedoor      = $c_time + PBBL / $persons[$value['persons'][0]]['v']['norm'] + $blocktime * $d;
                        if ($t != 0) $timedoor = ($timedoor < ($door[($t - 1)]['time'] + 1) && $t > 0) ? ($door[($t - 1)]['time'] + 1) : $timedoor;
                        $door[$t] = array('person' => $value['persons'][0], 'time' => round($timedoor, 3), 'blocktime' => $blocktime * $d, 'seat' => $persons[$value['persons'][0]]['seat']);
                        $t++;
                    } elseif ($z > 1 && PBB) {
                        $v             = get_weighted_group_speed($value['persons'], $persons, 3);
                        $doorspeed[$r] = $v;
                        $timedoor      = $c_time + PBBL / $v + $blocktime * $d;
                        if ($t != 0) $timedoor = ($timedoor < ($door[($t - 1)]['time'] + 1) && $t > 0) ? ($door[($t - 1)]['time'] + 1) : $timedoor;
                        foreach ($value['persons'] as $person) {
                            $door[$t] = array('person' => $person, 'time' => round($timedoor, 3), 'blocktime' => $blocktime * $d, 'seat' => $persons[$person]['seat']);
                            $timedoor = $timedoor + 0.5;
                            $t++;
                        }
                    } else {
                        foreach ($value['persons'] as $person) {
                            $timedoor = $c_time;
                            $door[$t] = array('person' => $person, 'time' => round($timedoor, 3));
                            $timedoor = $timedoor + 0.5;
                            $t++;
                        }
                    }
                    $r++;

                }
                $d++;
            }
            break;
    }
    #_pre($door); die();
    return (array($persons, $door));
}

function calculate_baggage_for_summary($int = FALSE) {
    if (!$int || !is_int($int) || $int > 4) return (0);
    global $summary;
    $summary['statistics']['baggage'][$int]++;
    return (1);
}

function calculate_free_seat_surround($size = 1, $seating_plan = FALSE) {
    if ($seating_plan === FALSE) die('FEHLER in calculate_free_seat_surround-FUNKTION...');

    $need            = $size;
    $first_group_key = FALSE;

    # die größte Reihe finden

    $max  = 0;
    $rmax = max(get_seats_by_airplane('seats_left'), get_seats_by_airplane('seats_right'), get_seats_by_airplane('seats_middle')) + 1;
    for ($i = 1; $i < $rmax; $i++) {
        $max = (is_numeric($seating_plan[$i . 'er']['available']) && $seating_plan[$i . 'er']['available'] > 0) ? $i : $max;
    }
    if ($max == 0) die('Fehler, da zuviele Sitze');

    $seats    = array();
    $k        = 0;
    $restrikt = ($_REQUEST['trim'] == 'nu') ? TRUE : FALSE;

    if (intval($size / $max) == 0 && $seating_plan[$size . 'er']['available'] > 0 && $restrikt) {
        foreach ($seating_plan[$size . 'er'] as $key => $value) {
            if ($value !== FALSE && is_numeric($key)) {
                $seating_plan[$size . 'er']['available']--;


                for ($i = 0; $i < $size; $i++) {
                    $seats[] = array($key, $seating_plan[$size . 'er'][$key][$i]);
                }
                $seating_plan[$size . 'er'][$key] = FALSE;

                return (array($seats, $seating_plan));

            }
        }
    } elseif (intval($size / $max) == 0 && $seating_plan[($size + 1) . 'er']['available'] > 0 && $restrikt) {
        foreach ($seating_plan[($size + 1) . 'er'] as $key => $value) {
            if ($value !== FALSE && is_numeric($key)) {
                $seating_plan[($size + 1) . 'er']['available']--;

                for ($i = 0; $i < $size; $i++) {
                    $seats[] = array($key, $seating_plan[($size + 1) . 'er'][$key][$i]);
                }
                $seating_plan['1er'][$key][0] = $seating_plan[($size + 1) . 'er'][$key][$i];
                $seating_plan['1er']['available']++;
                $seating_plan[($size + 1) . 'er'][$key] = FALSE;

                return (array($seats, $seating_plan));

            }
        }
    } elseif (intval($size / $max) == 0 && $seating_plan[($size + 2) . 'er']['available'] > 0 && ($size + 2) <= $max && $restrikt) {
        foreach ($seating_plan[($size + 2) . 'er'] as $key => $value) {
            if ($value !== FALSE && is_numeric($key)) {
                $seating_plan[($size + 2) . 'er']['available']--;

                for ($i = 0; $i < $size; $i++) {
                    $seats[] = array($key, $seating_plan[($size + 2) . 'er'][$key][$i]);
                }
                $seating_plan['2er'][$key][0] = $seating_plan[($size + 2) . 'er'][$key][$i];
                $seating_plan['2er'][$key][1] = $seating_plan[($size + 2) . 'er'][$key][($i + 1)];
                $seating_plan['2er']['available']++;
                $seating_plan[($size + 2) . 'er'][$key] = FALSE;

                return (array($seats, $seating_plan));

            }
        }
    } else {
        $diff = intval(($size - 0.01) / $max) + 1 - $seating_plan[$max . 'er']['available']; # Mögliche Sitzkonfigurationen ...
        if ($diff < 0 && $restrikt) { # Alle passen rein... # Problem :  !!! Es wird bei einer oder zwei personen eine neue 3er-Reihe aufgemacht...

            foreach ($seating_plan[$max . 'er'] as $key => $value) {
                if ($value !== FALSE && is_numeric($key)) {

                    if ($need > $max) {
                        for ($i = 0; $i < $max; $i++) {
                            $seats[] = array($key, $seating_plan[$max . 'er'][$key][$i]);
                            $need--;
                            if ($i == 0 && $first_group_key === FALSE) $first_group_key = $key;
                        }

                        $seating_plan[$max . 'er']['available']--;
                        $seating_plan[$max . 'er'][$key] = FALSE;
                    } else {
                        $z = $need;
                        if ($max - $z < 0) die('Fehler bei Checkin - zuviele Passagiere auf letzter Reihe ihrer Gruppe...');
                        if ($max - $z == 0) { # Sonderfall, der Rest der Gruppe passt auch auf eine Max-Reihe
                            for ($i = 0; $i < $z; $i++) {
                                $seats[] = array($key, $seating_plan[$max . 'er'][$key][$i]);
                                $need--;
                            }

                            $seating_plan[$max . 'er']['available']--;
                            $seating_plan[$max . 'er'][$key] = FALSE;
                        } else {
                            $zz   = $z + 1;
                            $near = FALSE;

                            if ($seating_plan[$z . 'er']['available'] > 0 || $seating_plan[$zz . 'er']['available'] > 0) {
                                if ($first_group_key !== FALSE && is_numeric($first_group_key)) {

                                    $a = $key - $first_group_key + 2;
                                    $b = $key - $first_group_key + 1;
                                    $c = $key - $first_group_key;
                                    $d = $key - $first_group_key - 1;
                                    $e = $key - $first_group_key + 2;
                                } else {
                                    $a = 3;
                                    $b = 2;
                                    $c = 1;
                                    $d = -1;
                                    $e = -2;
                                }
                                foreach ($seating_plan[$z . 'er'] as $key2 => $value2) {
                                    if (($key2 == $key - $a || $key2 == $key - $b || $key2 == $key - $c || $key2 == $key - $d || $key2 == $key - $e || $key2 == $key - 3 || $key2 == $key - 2 || $key2 == $key - 1 || $key2 == $key + 1 || $key2 == $key + 2) && is_numeric($key2) && $value2 !== FALSE && !$near) {
                                        for ($i = 0; $i < $z; $i++) {
                                            $seats[] = array($key2, $seating_plan[$z . 'er'][$key2][$i]);
                                            $need--;
                                        }

                                        $seating_plan[$z . 'er']['available']--;
                                        $seating_plan[$z . 'er'][$key2] = FALSE;
                                        $near                           = TRUE;
                                        break 1;
                                    }
                                }
                                if ($seating_plan[$zz . 'er']['available'] > 0 && ($zz < $max) && !$near) {
                                    foreach ($seating_plan[$zz . 'er'] as $key2 => $value2) {
                                        if (($key2 == $key - $a || $key2 == $key - $b || $key2 == $key - $c || $key2 == $key - $d || $key2 == $key - $e || $key2 == $key - 3 || $key2 == $key - 2 || $key2 == $key - 1 || $key2 == $key + 1 || $key2 == $key + 2) && is_numeric($key2) && $value2 !== FALSE && !$near) {
                                            for ($i = 0; $i < $z; $i++) {
                                                $seats[] = array($key2, $seating_plan[$zz . 'er'][$key2][$i]);
                                                $need--;
                                            }

                                            $seating_plan['1er'][$key2][0] = $seating_plan[$zz . 'er'][$key2][$i];

                                            $seating_plan[$zz . 'er']['available']--;
                                            $seating_plan[$zz . 'er'][$key2] = FALSE;
                                            $seating_plan['1er']['available']++;
                                            $near = TRUE;
                                            break 1;
                                        }
                                    }
                                }
                            } else {
                                for ($i = 0; $i < $z; $i++) {
                                    $seats[] = array($key, $seating_plan[$max . 'er'][$key][$i]);
                                    $need--;
                                }
                                for ($j = 0; $j < ($max - $z); $j++) {
                                    $seating_plan[($max - $z) . 'er'][$key][$j] = $seating_plan[$max . 'er'][$key][($i + $j)];
                                }

                                $seating_plan[$max . 'er']['available']--;
                                $seating_plan[$max . 'er'][$key] = FALSE;
                                $seating_plan[($max - $z) . 'er']['available']++;

                            }
                            if ($need > 0) {
                                if ($near) die($size . '-' . $need . 'Fehler...');
                                for ($i = 0; $i < $z; $i++) {
                                    $seats[] = array($key, $seating_plan[$max . 'er'][$key][$i]);
                                    $need--;
                                }
                                for ($j = 0; $j < ($max - $z); $j++) {
                                    $seating_plan[($max - $z) . 'er'][$key][$j] = $seating_plan[$max . 'er'][$key][($i + $j)];
                                }
                                $seating_plan[$max . 'er']['available']--;
                                $seating_plan[$max . 'er'][$key] = FALSE;
                                $seating_plan[($max - $z) . 'er']['available']++;
                            }
                        }
                        return (array($seats, $seating_plan)); #Noch die DIffs rausnehmen
                    }
                }
            }
        } else { # Es kann nicht jeder auf einer 3er-Reihe sitzen

            $do = TRUE;
            $i  = 0;
            while ($do) {
                if ($seating_plan[($max - $i) . 'er']['available'] > 0) {
                    foreach ($seating_plan[($max - $i) . 'er'] as $key => $value) {
                        if ($value !== FALSE && is_numeric($key)) {

                            if ($need > ($max - $i)) {
                                for ($j = 0; $j < ($max - $i); $j++) {
                                    $seats[] = array($key, $seating_plan[($max - $i) . 'er'][$key][$j]);
                                    $need--;
                                }
                                $seating_plan[($max - $i) . 'er']['available']--;
                                $seating_plan[($max - $i) . 'er'][$key] = FALSE;
                            } else {
                                $k = $need;
                                for ($j = 0; $j < $k; $j++) {
                                    $seats[] = array($key, $seating_plan[($max - $i) . 'er'][$key][$j]);
                                    $need--;
                                }
                                $seating_plan[($max - $i) . 'er']['available']--;
                                if ($max - $i - $k > 0) {
                                    for ($z = 0; $z < ($max - $i - $k); $z++) {
                                        $seating_plan[($max - $i - $k) . 'er'][$key][$z] = $seating_plan[($max - $i) . 'er'][$key][($z + $j)];
                                    }
                                    $seating_plan[($max - $i - $k) . 'er']['available']++;
                                    $seating_plan[($max - $i) . 'er'][$key] = FALSE;
                                    if ($need < 1) return (array($seats, $seating_plan, 'diffx'));
                                } else {
                                    $seating_plan[($max - $i) . 'er'][$key] = FALSE;
                                    if ($need < 1) return (array($seats, $seating_plan, 'diffx'));
                                }
                            }
                        }
                    }
                    $i++;
                    $do = ($max - $i < 1) ? FALSE : TRUE;

                } else {
                    $i++;
                    $do = ($max - $i < 1) ? FALSE : TRUE;
                }
            }
            _pre($seating_plan);
            die('Keine Plätze übrig');
        }
    }

    return (0);
}

function calculate_free_way_to_seat($person = FALSE, $cabin = FALSE) {
    if (!$person || !$cabin) return (FALSE);
    global $seatsbo;
    global $persons;
    global $group;#_pre($person); die();

    $temp = get_seats_by_airplane();
    $seat = $person['seat'];
    $l    = 0;

    # Bestimmen, in welcher Reihe der Platz ist
    for ($i = 0; $i < $temp['seats_left']; $i++) {
        $seattemp[$i] = chr($i + 65);
        if ($seattemp[$i] == $seat['nr']) {
            $l      = 999;
            $seatid = $i;
            $final  = $i + 1;
            $outer  = $temp['seats_left'];
            $window = 'A';
        }
    }
    if ($l != 999) {
        # Nachfolgende Schleife läuft rückwärts, um den Fensterplatz bei Key-0 zu platzieren
        unset($seattemp);
        for ($j = 0; $j < $temp['seats_right']; $j++) {
            $seattemp[($temp['seats_right'] - $j - 1)] = chr($i + 65 + $j);
            if ($seattemp[($temp['seats_right'] - $j - 1)] == $seat['nr']) {
                $final  = $j + 1;
                $seatid = $temp['seats_right'] - $j - 1;
                $outer  = $temp['seats_right'];
                $window = chr($i + 65 + $temp['seats_right'] - 1);
            }
        }
        ksort($seattemp);
    }

    if ($window != $seat['nr']) {
        foreach ($group[$person['group']['group']['id']]['seats'] as $key => $value) {
            $temp = explode(' - ', $value, 2);
            if ($temp[0] == $seat['row']) {
                foreach ($seattemp as $tkey => $tmp) {
                    if ($seatid < $tkey && $tmp == $temp[1] && !isset($seatsbo[$seat['row']][$tkey]) && $persons[$group[$person['group']['group']['id']]['persons'][$key]]['typ'] != 'child' && $tmp != $seat['nr']) {
                        $temp_old_seat  = $seat;
                        $temp_new_seat  = array('row' => $temp[0], 'nr' => $tmp);
                        $temp_the_pid   = $person['ID'];
                        $temp_the_o_pid = $group[$person['group']['group']['id']]['persons'][$key];

                        # GruppenArray wieder auf Vordermann bringen .. (getauschte Plätze)
                        foreach ($group[$person['group']['group']['id']]['persons'] as $ttk => $ttkvalue) {
                            if ($ttkvalue == $temp_the_pid) $group[$person['group']['group']['id']]['persons'][$ttk] = 'N';
                            if ($ttkvalue == $temp_the_o_pid) $group[$person['group']['group']['id']]['persons'][$ttk] = 'O';
                        }
                        foreach ($group[$person['group']['group']['id']]['persons'] as $ttk => $ttkvalue) {
                            if ($ttkvalue == 'N') $group[$person['group']['group']['id']]['persons'][$ttk] = $temp_the_o_pid;
                            if ($ttkvalue == 'O') $group[$person['group']['group']['id']]['persons'][$ttk] = $temp_the_pid;
                        }

                        # Personen-Sitzplatz auf Vordermann bringen
                        $persons[$temp_the_pid]['seat']   = $temp_new_seat;
                        $persons[$temp_the_o_pid]['seat'] = $temp_old_seat;

                        break 2;
                    }
                }
            }
        }
    }

    if (!isset($seatsbo[$seat['row']])) {
        $out['status'] = TRUE;
        $out['outer']  = $outer;
        $out['left']   = $outer - $final;
        $out['final']  = $final;
    } else {
        $r = TRUE;
        foreach ($seatsbo[$seat['row']] as $key => $value) {
            if ($key > $final) { # Dann sitzt jemand im Weg
                $r             = FALSE;
                $out['status'] = FALSE;
                $out['outer']  = $outer;
                $out['left']   = $outer - $final + 1;
                $out['final']  = $final;
                foreach ($cabin as $tkey => $tvalue) {
                    if ($tvalue['ID'] == $value) {
                        $out['waiting_for'][] = $tkey;
                        break 1;
                    }
                }

            }
        }
        if ($r) {
            $out['status'] = TRUE;
            $out['outer']  = $outer;
            $out['left']   = $outer - $final;
            $out['final']  = $final;
        }
    }
    return ($out);

}

function calculate_seats($split = array('biz' => 0.5, 'normal' => 0.3, 'old' => FALSE, 'child' => FALSE), $seats = 200) {
    global $charge;

    $seats = array('available' => $seats, 'init' => $seats);

    $split = (!is_array($split)) ? array('biz' => 0.5, 'normal' => 0.3, 'old' => FALSE, 'child' => FALSE) : $split;
    arsort($split);

    #($_GET['developer'] == 1) ? _pre($split) : 0;

    $seats['sum'] = 0;

    foreach ($split as $key => $value) { # Für jedes Element des Array Split, gebe den zugeordneten Wert und den Schlüssel aus
        if ($value) {
            $a = $value - 0.5 * $value;
            $b = (($value + 0.5 * $value) > 1) ? 1 : ($value + 0.5 * $value);
            $c = $value;

            $y = rand(0, 1000000) / 1000000;

            $due = (!is_bool($value)) ? function_triangular_distribution($a, $b, $c, $y) : FALSE;

            $charge['people'][$key] = ($due === FALSE) ? (intval((rand(0, 100) / 100) * (($seats['available'] >= 0) ? $seats['available'] : 0))) : (intval($due * $seats['init']));

            $seats['sum']       = $seats['sum'] + $charge['people'][$key];
            $seats['available'] = $seats['init'] - $seats['sum'];
        }
    }

    if ($seats['sum'] > $seats['init']) {
        $i       = 0;
        $maxrand = 0;
        foreach ($split as $key => $value) {
            $temp                = (1 - $value) * 100;
            $newdue[$i]['value'] = $temp;
            $newdue[$i]['key']   = $key;
            $maxrand             = $maxrand + $temp;
            $i++;
        }

        for ($i = 0; $i < $seats['sum'] - $seats['init']; $i++) {
            $rand = rand(0, $maxrand);
            if ($rand > intval($maxrand - $newdue[3]['value'])) ($charge['people'][$newdue[3]['key']] > 0) ? $charge['people'][$newdue[3]['key']]-- : $i--;
            elseif ($rand > intval($maxrand - $newdue[3]['value'] - $newdue[2]['value'])) ($charge['people'][$newdue[2]['key']] > 0) ? $charge['people'][$newdue[2]['key']]-- : $i--;
            elseif ($rand > intval($maxrand - $newdue[3]['value'] - $newdue[2]['value'] - $newdue[1]['value'])) ($charge['people'][$newdue[1]['key']] > 0) ? $charge['people'][$newdue[1]['key']]-- : $i--;
            else ($charge['people'][$newdue[0]['key']] > 0) ? $charge['people'][$newdue[0]['key']]-- : $i--;
        }
    } elseif ($seats['sum'] < $seats['init']) {
        $i       = 0;
        $maxrand = 0;
        foreach ($split as $key => $value) {
            if ($value) {
                $temp                = $value * 100;
                $newdue[$i]['value'] = $temp;
                $newdue[$i]['key']   = $key;
                $maxrand             = $maxrand + $temp;
                $i++;
            }
        }

        $a = (isset($newdue[0]['value'])) ? $newdue[0]['value'] : 0;
        $b = (isset($newdue[1]['value'])) ? $newdue[1]['value'] : 0;
        $c = (isset($newdue[2]['value'])) ? $newdue[2]['value'] : 0;
        $d = (isset($newdue[3]['value'])) ? $newdue[3]['value'] : 0;

        for ($i = 0; $i < $seats['init'] - $seats['sum']; $i++) {
            $rand = rand(0, $maxrand);
            if ($rand > intval($maxrand - $a)) $charge['people'][$newdue[0]['key']]++;
            elseif ($rand > intval($maxrand - $a - $b)) $charge['people'][$newdue[1]['key']]++;
            elseif ($rand > intval($maxrand - $a - $b - $c)) $charge['people'][$newdue[2]['key']]++;
            else {
                if ($d == 0) $charge['people'][$newdue[0]['key']]++;
                else $charge['people'][$newdue[3]['key']]++;
            }
        }
    }

    $charge['seats_used'] = ($seats['init'] != ($charge['people']['biz'] + $charge['people']['normal'] + $charge['people']['old'] + $charge['people']['child'])) ? 0 : $seats['init'];

    #($_GET['developer'] == 1) ? _pre($seats)._pre($charge) : 0;

    return ($charge);
}

function select_person_typ() {
    global $charge;

    $diff_child = $charge['people']['child'] - $charge['with_boardingcard']['child'];
    /**
     * if($diff_child > 0){
     * if($ch)
     * }
     **/
    return (FALSE);
}

function split_groups_by_size($group = FALSE, $persons = FALSE) {
    if (!$group || !$persons) return (FALSE);

    $grsize = count($group);
    $add    = 0;

    foreach ($group as $key => $value) {
        foreach ($value['persons'] as $ktemp => $temp) {
            $stemp                         = explode(' - ', $value['seats'][$ktemp], 2);
            $persons[$temp]['seat']['row'] = $stemp[0];
            $persons[$temp]['seat']['nr']  = $stemp[1];
        }
        if ($value['size'] > 3) {
            $split = (rand(0, 999) < $value['size'] * 100) ? TRUE : FALSE;
            if (!$split || $value['typ'] == 'child') continue;
            # Split mit maximal 3 Untergruppen...
            if ($value['size'] % 2 == 0) {
                $typ = rand(0, 99);
                if ($typ < 30 && $value['size'] > 7) {
                    $ct['typ'] = 3;
                    $ct['anz'] = 3;
                    $ct['rst'] = $value['size'] - 2 * $ct['anz'];
                } elseif ($typ < 60 && $value['size'] > 5) {
                    $ct['typ'] = 2;
                    $ct['anz'] = $value['size'] - 4;
                    $ct['rst'] = $value['size'] - $ct['anz'];
                } else {
                    $ct['typ'] = 2;
                    $ct['anz'] = $value['size'] / 2;
                    $ct['rst'] = $ct['anz'];
                }
            } else {
                $typ = rand(0, 99);
                if ($typ < 30 && $value['size'] > 8) {
                    $ct['typ'] = 3;
                    $ct['anz'] = 3;
                    $ct['rst'] = $value['size'] - 2 * $ct['anz'];
                } elseif ($typ < 60 && $value['size'] > 5) {
                    $ct['typ'] = 2;
                    $ct['anz'] = $value['size'] - 3;
                    $ct['rst'] = $value['size'] - $ct['anz'];
                } else {
                    $ct['typ'] = 2;
                    $ct['anz'] = $value['size'] - 3;
                    $ct['rst'] = $value['size'] - $ct['anz'];
                }
            }
            $ctseats = count($value['seats']);
            $sadd    = 1;
            $ctpers  = count($value['persons']);

            for ($i = 1; $i < $ct['typ']; $i++) {
                $newgroup[$add]['assigned']['normal'] = 0;
                $newgroup[$add]['assigned']['old']    = 0;
                $newgroup[$add]['assigned']['biz']    = 0;
                $newgroup[$add]['size']               = 0;
                for ($j = 0; $j < $ct['anz']; $j++) {
                    if ($value['assigned']['normal'] > 0) {
                        $value['assigned']['normal']--;
                        $newgroup[$add]['assigned']['normal']++;
                    } elseif ($value['assigned']['old'] > 0) {
                        $value['assigned']['old']--;
                        $newgroup[$add]['assigned']['old']++;
                    } elseif ($value['assigned']['biz'] > 0) {
                        $value['assigned']['biz']--;
                        $newgroup[$add]['assigned']['biz']++;
                    } else die('Problem - Kindergruppe soll gespalten werden');

                    $group[$key]['size']--;
                    $newgroup[$add]['size']++;
                    $newgroup[$add]['seats'][]   = $value['seats'][($ctseats - $sadd)];
                    $newgroup[$add]['persons'][] = $value['persons'][($ctpers - $sadd)];
                    unset($group[$key]['seats'][($ctseats - $sadd)]);
                    unset($group[$key]['persons'][($ctpers - $sadd)]);
                    $sadd++;
                }


                $newgroup[$add]['ID']       = $value['ID'];
                $newgroup[$add]['typ']      = $value['typ'];
                $newgroup[$add]['quantity'] = $value['quantity'];
                $newgroup[$add]['power']    = $value['power'];
                $newgroup[$add]['duty']     = $value['duty'];
                $newgroup[$add]['SUB']      = 1;
                $newgroup[$add]['seating']  = abs($value['seating'] + ((rand(0, 1000) > 499) ? -1 : 1) * rand(0, 40));

                $add++;
            }
        }
    }

    if (isset($newgroup[0])) {
        if (is_array($newgroup)) $group = array_merge($group, $newgroup);
    }

    return (array($group, $persons));
}

function sort_group_array_keyID($group = FALSE) {
    if (!$group || !is_array($group)) return (FALSE);

    $temp = array();

    foreach ($group as $key => $value) {
        if (isset($temp[$value['ID']])) return (FALSE);
        $temp[$value['ID']] = $value;
    }
    ksort($temp);

    return ($temp);
}

function sort_group_by_seating($a, $b) {
    if ($a['seating'] == $b['seating']) {
        return 0;
    }
    return ($a['seating'] < $b['seating']) ? -1 : 1;
}

function sort_major_to_minor($a, $b) {
    if ($a == $b) {
        return 0;
    }
    return ($a > $b) ? -1 : 1;

}
