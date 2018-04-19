<?php
/*
Plugin Name: Prozess-Simulation: Boarding
Description: N/A
Author: Martin Görner & Michael Werner
Version: 0.3
Author URI: http://martin-goerner.com/
Plugin URL: http://boarding.martin-goerner.com/
*/

if(function_exists(add_action)) add_action('wp_head', 'init_head_scripts');

if (!function_exists('_pre')) {
    function _pre($string){
            echo '<pre>';
            print_r($string);
            echo '</pre>';
    }
}


function _unset_boarding($string){
        switch($string){
            case 'all':
                # globals: $charge; $group; $persons;
                unset($GLOBALS['charge'], $GLOBALS['group'], $GLOBALS['persons'], $GLOBALS['seatsbo'],$GLOBALS['$summary']);
                echo 'initialisiere neu...<br />';
            break;
        }
}


function init_boarding_sim(){
    add_submenu_page( 'tools.php', 'Boarding-Sim', 'BSim', 'edit_themes', 'BSim', 'basis_boarding_sim' );
}

function init_head_scripts(){     
    echo '<script type="text/javascript">
            function spoil(id){
            if (document.getElementById) {
            var divid = document.getElementById(id);
            divid.style.display = (divid.style.display==\'block\'?\'none\':\'block\');
            } }
          </script>';

    if($_SERVER['REQUEST_URI'] == '/auswertung/auswertung-der-simulationsergebnisse'){
        echo '  <script type="text/javascript" src="http://www.google.com/jsapi"></script>
                <script type="text/javascript">
                  google.load(\'visualization\', \'1\', {packages: [\'corechart\']});
                </script>
                <script type="text/javascript">
                  function drawVisualization() {
                    // Create and populate the data table.
                    
                      '.report_func().'
                  
                  }
                  
            
                  google.setOnLoadCallback(drawVisualization);
                </script>';
    }
}

function basis_boarding_sim(){
    $out = '<div><br /><h2>Einstellungen f&uuml;r die Prozess-Simulation: Boarding</h2></div>';
    $max = 0;
    $c = 0.9;
    
    for($i = 0; $i < 10; $i++){
        $balken[$i] = 0;
    }
    
    require_once('statistics.php');
    for($i = 0; $i < 500; $i++){
        $faktor = 100 * function_triangular_distribution($c,function_triangular_random($c));
        if ($faktor < 11) $balken[0]++;
        elseif ($faktor < 21) $balken[1]++;
        elseif ($faktor < 31) $balken[2]++;
        elseif ($faktor < 41) $balken[3]++;
        elseif ($faktor < 51) $balken[4]++;
        elseif ($faktor < 61) $balken[5]++;
        elseif ($faktor < 71) $balken[6]++;
        elseif ($faktor < 81) $balken[7]++;
        elseif ($faktor < 91) $balken[8]++;
        else $balken[9]++;
        
    }
    
    foreach($balken as $value){
        $max = ($value > $max) ? $value : $max;
    }
    
    echo '<h2>Verteilung mit Dreieck-Verteilungs-Funktion auf Dichte-Funktion der selben</h2><div style="overflow: hidden;margin: 0 auto;">';
            for($i = 0; $i < 10; $i++){
                echo '<div style="float: left"><div style="width: 35px; height: '.$balken[$i].'px; margin: '.($max + 5 - $balken[$i]).'px 3px 5px 3px;  
                                  background-color: #900;"></div><div style="clear:both;color: #444; vertical-align: bottom; text-align: center;font-weight: bold; ">'.($i * 10 + 5).'%</div></div>';
                } 
    echo '</div>';
    
    for($i = 0; $i < 10; $i++){
        $balken[$i] = 0;
    }
    
    require_once('statistics.php');
    for($i = 0; $i < 500; $i++){
        #$faktor = 100 * function_triangular_distribution($c,function_triangular_distribution($c,(rand(0,10000) / 10000 )));
        $faktor = 100 * function_normal_random(1,0.2); 
        if ($faktor < -20) $balken[0]++;
        elseif ($faktor < 10) $balken[1]++;
        elseif ($faktor < 40) $balken[2]++;
        elseif ($faktor < 70) $balken[3]++;
        elseif ($faktor < 100) $balken[4]++;
        elseif ($faktor < 130) $balken[5]++;
        elseif ($faktor < 160) $balken[6]++;
        elseif ($faktor < 190) $balken[7]++;
        elseif ($faktor < 220) $balken[8]++;
        else $balken[9]++;
        
    }
    
    foreach($balken as $value){
        $max = ($value > $max) ? $value : $max;
    }
    
    echo '<h2>Verteilung mit der Normalfunktion der selben</h2><div style="overflow: hidden;margin: 0 auto;">';
            for($i = 0; $i < 10; $i++){
                echo '<div style="float: left"><div style="width: 35px; height: '.$balken[$i].'px; margin: '.($max + 5 - $balken[$i]).'px 3px 5px 3px;  
                                  background-color: #900;"></div><div style="clear:both;color: #444; vertical-align: bottom; text-align: center;font-weight: bold; ">'.($i * 10 + 5).'%</div></div>';
                } 
    echo '</div>';
    
    echo $out;
}

function check_blocksize($block = FALSE){
    if(!$block || !is_int(intval($block)) || $block < 1) return(1);
    elseif($block > 5) return(5);
    else return(intval($block));
}

function check_blocktime($time){
    if(!$time || $time < 10 || !is_integer(intval($time))) return(10);
    elseif($time > 600) return(600);
    else return(intval($time));
}

function check_boardingtyp($typ = FALSE){
    if(!$typ || $typ == 2) return('normal');
    elseif($typ = 1) return('blocked');
    elseif($typ = 3) return('random');
    else return('normal');
}

function create_wp_post($postsum = FALSE){
    if(!$postsum || !function_exists(wp_insert_post)) return(0);
    
    global $summary;
    
    $content = $postsum;
    $excerpt = 'Boarding-Sim-Parameter:<br />
                Boarding-Typ: '.$summary['misc']['boardingtyp'];
    if($summary['misc']['boardingtyp'] == 'blocked') $excerpt .= ' ( '.$_REQUEST['ctblocks'].' / '.$_REQUEST['bltime'].' )';
    $excerpt .= '<br />Sitzladefaktor: '.$summary['statistics']['lfac_calc'].'<br />Flugroutenart: '.$summary['misc']['flighttyp'].'<br />';
    $excerpt .= 'Flugzeugtyp: '.$summary['misc']['typ'];
    $excerpt .= 'Boardkarten auf Trimmung: [ ';
    $excerpt .= ($_REQUEST['trim'] == 'nu')?'X':'_';
    $excerpt .= ' ]<br />Reisegruppen: [ ';
    $excerpt .= ($_REQUEST['groups'] == 'nu')?'_':'X';
    $excerpt .= ' ]<br />Mit Sitzverteilungsanzeige: [ ';
    $excerpt .= ($_REQUEST['vis'] == 'nu')?'X':'_';
    $excerpt .= ']<br />Mit Passagierbr&uuml;cke: [ ';
    $excerpt .= ($_REQUEST['pbb'] == 'nu')?'X':'_';
    $excerpt .= ' ]';

    $slug = 'boarding-simulation-'.$summary['misc']['typ'].'-'.$summary['load']['pax'].'-pax-'.rand(12345678,987654321);
    
    $title = wp_strip_all_tags($summary['misc']['szname']);
    
    if($summary['statistics']['lfac_calc'] < .30) $slf = ' SLF < 30%';
    elseif($summary['statistics']['lfac_calc'] < .40) $slf = ' SLF < 40%';
    elseif($summary['statistics']['lfac_calc'] < .50) $slf = ' SLF < 50%';
    elseif($summary['statistics']['lfac_calc'] < .60) $slf = ' SLF < 60%';
    elseif($summary['statistics']['lfac_calc'] < .70) $slf = ' SLF < 70%';
    elseif($summary['statistics']['lfac_calc'] < .80) $slf = ' SLF < 80%';
    elseif($summary['statistics']['lfac_calc'] < .90) $slf = ' SLF < 90%';
    elseif($summary['statistics']['lfac_calc'] < 1) $slf = ' SLF < 100%';
    elseif($summary['statistics']['lfac_calc'] = 1) $slf = ' SLF = 100%';
    
    $trim = ($_REQUEST['trim'] == 'nu')? 'Boardkartenalgorithmus (Trimmung)' : 'Boardkartenalgorithmus (Fenster f&uuml;llen)';
    $groups = ($_REQUEST['groups'] == 'nu')? 'ohne Reisegruppen' : 'mit Reisegruppen';
    $show = ($_REQUEST['vis'] == 'nu') ? 'mit Visualisierung' : 'ohne Visualisierung';
    
    if(PBB && PBBL < 10) $pbb = 'PBB < 10m';
    elseif(PBB && PBBL < 20) $pbb = 'PBB < 20m';
    elseif(PBB && PBBL < 30) $pbb = 'PBB < 30m';
    elseif(PBB && PBBL < 40) $pbb = 'PBB < 40m';
    elseif(PBB && PBBL < 50) $pbb = 'PBB < 50m';
    elseif(PBB && PBBL < 60) $pbb = 'PBB < 60m';
    elseif(PBB && PBBL < 70) $pbb = 'PBB < 70m';
    elseif(PBB && PBBL < 80) $pbb = 'PBB < 80m';
    elseif(PBB && PBBL < 90) $pbb = 'PBB < 90m';
    elseif(PBB && PBBL < 100) $pbb = 'PBB < 100m';
    elseif(PBB && PBBL >= 100) $pbb = 'PBB >= 100m';
    elseif(!PBB) $pbb = 'ohne PBB';
    
    if($summary['misc']['duration'] < 500) $btime = 'Boardingzeit < 500s';
    elseif($summary['misc']['duration'] < 600) $btime = 'Boardingzeit < 600s';
    elseif($summary['misc']['duration'] < 700) $btime = 'Boardingzeit < 700s';
    elseif($summary['misc']['duration'] < 800) $btime = 'Boardingzeit < 800s';
    elseif($summary['misc']['duration'] < 900) $btime = 'Boardingzeit < 900s';
    elseif($summary['misc']['duration'] < 1000) $btime = 'Boardingzeit < 1000s';
    elseif($summary['misc']['duration'] < 1200) $btime = 'Boardingzeit < 1200s';
    elseif($summary['misc']['duration'] < 1400) $btime = 'Boardingzeit < 1400s';
    elseif($summary['misc']['duration'] < 1600) $btime = 'Boardingzeit < 1600s';
    elseif($summary['misc']['duration'] < 1800) $btime = 'Boardingzeit < 1800s';
    elseif($summary['misc']['duration'] < 2000) $btime = 'Boardingzeit < 2000s';
    elseif($summary['misc']['duration'] < 2500) $btime = 'Boardingzeit < 2500s';
    elseif($summary['misc']['duration'] > 2500) $btime = 'Boardingzeit > 2500s';
    
    $tags = array($slf,$summary['misc']['typ'],$trim,$groups,$pbb,$summary['misc']['flighttyp'],$btime);
    $user_ID = 2;
    $post = array(
      'mt_allow_comments' => 0,
      'mt_allow_pings' => 1,
      'wp_author_id' => $user_ID,
      'categories' => array('simulationsergebnisse'),
      'description' => $content,
      'mt_excerpt' => $excerpt,
      'wp_slug' => $slug,
      'title' => $title,
      'publish' => TRUE,
      'mt_keywords' => $tags,
      'post_type' => 'post'
    );
     
     require_once(ABSPATH . 'wp-includes/class-IXR.php');
            $rpc = new IXR_Client(get_bloginfo('url').'/xmlrpc.php');
            $status = $rpc->query(
                  'metaWeblog.newPost', // Methode
                  1,                // Blog ID, in der Regel 1
                  'Boardingsimulation',             // Benutzer
                  'ifltudresden2012',         // Passwort
                  $post,            // Post construct
                  true          // Veröffentlichen?
            );

     $id = intval($rpc->getResponse());
     $summary['misc']['wpid'] = $id;
     #_pre($rpc); die();
     return($id);  
}

function fill_database_with_values(){
    global $wpdb; global $summary;   
    if(!isset($wpdb->prefix)) return(0);
        $swait = 0;
    foreach($summary['statistics']['t_waiting'] as $value){
        $swait = $swait + $value;   
    }
    
    $mainbase = 'INSERT INTO `'.$wpdb->prefix.'boarding_main` (`ID`, `slf`, `boardingtime`, `pax`, `actyp`, `pbblenght`, `boardingtyp`, `boardingblocks`, `boardingblocktime`, `boardcardstrategie`, `swaitingtime`,`wordpress_id`, `time`)';
    $mainbase.= ' VALUES (NULL, '.$summary['statistics']['lfac_calc'].','.intval($summary['misc']['duration']).','.$summary['load']['pax'].',\''.get_seats_by_airplane('typ').'\','.PBBL.',\''.$summary['misc']['boardingtyp'].'\','.BLOCKCT;
    $mainbase.= ','.BLOCKTIME.','.(($_REQUEST['trim'] == 'nu')? 1 : 0).','.$swait.','.$summary['misc']['wpid'].', NOW());';
    
    if($wpdb->query($mainbase)) $result = $wpdb->get_results('SELECT `ID` FROM `'.$wpdb->prefix.'boarding_main` ORDER BY `time` DESC LIMIT 1;');
    else return(0);

    if(empty($result[0]->ID)) return(FALSE);
    
    $subbase = 'INSERT INTO `'.$wpdb->prefix.'boarding_speed` (ID, `main_id`, `speed`) VALUES';
    foreach($summary['statistics']['v_all'] as $value){
        $subbase .= '(NULL, '.$result[0]->ID.', '.$value.'),';
    }
    $subbase = substr($subbase,0,-1);
    
    if(!$wpdb->query($subbase)) return(FALSE);

    $subbase = 'INSERT INTO `'.$wpdb->prefix.'boarding_loadingtime` (ID, `main_id`, `loadingtime`) VALUES';
    foreach($summary['statistics']['t_loading_bags'] as $value){
        $subbase .= '(NULL, '.$result[0]->ID.', '.$value.'),';
    }
    $subbase = substr($subbase,0,-1);
    
    if(!$wpdb->query($subbase)) return(FALSE);
    
    $subbase = 'INSERT INTO `'.$wpdb->prefix.'boarding_waitingtime` (ID, `main_id`, `waitingtime`) VALUES';
    foreach($summary['statistics']['t_waiting'] as $value){
        $subbase .= '(NULL, '.$result[0]->ID.', '.$value.'),';
    }
    $subbase = substr($subbase,0,-1);
    
    if(!$wpdb->query($subbase)) return(FALSE);
}

function input_func( $atts ){
    if($_GET['hash'] == 'madifhnoewht9438bkfldhgbl' && $_GET['user'] == 'nflsdjhnfln093tnfgldfng0834ntg'){
    
    $_REQUEST['boarding'] = rand(1,2);
    if($_REQUEST['boarding'] == 1){
        $_REQUEST['ctblocks'] = rand(2,5);
        $_REQUEST['bltime'] = rand(10,40);   
    }    
    $_REQUEST['factor'] = (rand(3,10)/10 );
    $_REQUEST['speclf'] = 'non';
    $m = rand(0,4);
    $typus = array('norm','biz','vacation','feeder','lowcost');
    $_REQUEST['flycat'] = $typus[$m]; 
    $_REQUEST['aircrafttypx'] = rand(1,3);
    $_REQUEST['trim'] = (rand(0,10) < 8) ? 'nu' : 0;
    $_REQUEST['groups'] = (rand(0,1) == 1) ? 'nu' : 0;
    $_REQUEST['vis'] = 'nu';
    $_REQUEST['pbb'] = 'nu';
    if($_REQUEST['pbb'] == 'nu' && $_REQUEST['aircrafttypx'] != 1) $_REQUEST['pbbl'] = 50;
    else $_REQUEST['pbbl'] = rand(0,101);
    $_REQUEST['rounds'] = 5;
        
    start_simulation();    
    } else {
    $form = '<form action="'.$_SERVER['REQUEST_URI'].'" method="post"  enctype="multipart/form-data">
          <label><select name="boarding" size="1">
              <option value="1">Blockabfertigung (mit Sitzkarte)</option>
              <option value="2">Random-Abfertigung (mit Sitzkarte)</option>
            </select> - Boarding-Art</label>
            <br /><label><i>Wenn Blockabfertigung:</i></label> <label>Anzahl der Blöcke <input name="ctblocks" size="3" type="text" maxlenght="4" value="2" />
             mit Pause (in Sek.) zwischen den Blöcken: <input name="bltime" size="3" type="text" maxlenght="4" value="3" /></label><br />
           <hr />
           <label>
            <select name="factor" size="1">
              <option>0.3</option>
              <option>0.4</option>
              <option>0.5</option>
              <option>0.6</option>
              <option>0.7</option>
              <option>0.8</option>
              <option>0.9</option>
              <option>1.0</option>
            </select> - Sitzladefaktor</label><br/><label>bzw.: genauer Ladefaktor: <input type="text" name="speclf" value="non" maxlenght="6" size="6" /> - min: 0.25; max: 1
           
           </label><hr /><br />
           <label>
            <select name="flycat" size="1">
              <option value="norm">unspezifisch</option>
              <option value="biz">Gesch&auml;ftsreisende</option>
              <option value="vacation">Ferienflieger</option>
              <option value="feeder">Zubringer</option>
              <option value="lowcost">"Billig-Flieger"</option>
            </select> - Flugroutenart
           </label>
           <label>
            <select name="aircrafttypx" size="1">
              <option value="1">B737 - 800</option>
              <option value="2">Embraer ERJ-190</option>
              <option value="3">A320 - 200</option>
            </select> - Flugzeugart
           </label><br />
           <label><input type="checkbox" name="trim" value="nu" checked/> - Vergabe der Boardkarten ist auf Trimmung ausgerichtet</label><br />
           <label><input type="checkbox" name="groups" value="nu" checked/> - Nur Einzelreisende (keine Kindersimulation)</label><br />
           <label><input type="checkbox" name="vis" value="nu" checked/> - Visualisierung der Sitzkartenverteilung</label><br />
           <label><input type="checkbox" name="pbb" value="nu" checked/> - Nutze Passagierbrücke mit &nbsp; <input type="text" name="pbbl" size="6" maxlenght="4" value="50"/> Länge in m</label><br /> 
           <!--<label><input type="text" name="rounds" value="1" size="6" maxlenght="4"/> - Simulationsdurchläufe (max: 10)</label>--> 
            <input name="Speichern" type="submit" value="Simulieren" />            
            <input type="hidden" name="action" value="blog-rein" />
        </form>';    
    
    if($_REQUEST['action'] != 'blog-rein') $out = $form;
    else $out = start_simulation();    
    return $out;
    }
}

function install_db_boardingsim(){
    global $wpdb;
    $sql = "CREATE TABLE `" . $wpdb->prefix . "boarding_main` (
          `ID` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
          `slf` FLOAT NOT NULL ,
          `boardingtime` BIGINT NOT NULL ,
          `pax` BIGINT NOT NULL ,
          `actyp` VARCHAR(20) NOT NULL ,
          `pbblenght` BIGINT NOT NULL ,
          `boardingtyp` VARCHAR(20) NOT NULL,
          `boardingblocks` INT NOT NULL,
          `boardingblocktime` BIGINT NOT NULL,
          `boardcardstrategie` INT NOT NULL,
          `wordpress_id` BIGINT NOT NULL,
          `time` DATETIME NOT NULL
          ) ENGINE = MYISAM";
    $wpdb->query($sql);
    $sql = "CREATE TABLE `" . $wpdb->prefix . "boarding_speed` (
          `ID` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
          `main_id` BIGINT NOT NULL ,
          `speed` FLOAT NOT NULL 
          ) ENGINE = MYISAM";
    $wpdb->query($sql);
    $sql = "CREATE TABLE `" . $wpdb->prefix . "boarding_waitingtime` (
          `ID` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
          `main_id` BIGINT NOT NULL ,
          `waitingtime` FLOAT NOT NULL 
          ) ENGINE = MYISAM";
    $wpdb->query($sql);
    $sql = "CREATE TABLE `" . $wpdb->prefix . "boarding_loadingtime` (
          `ID` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
          `main_id` BIGINT NOT NULL ,
          `loadingtime` FLOAT NOT NULL 
          ) ENGINE = MYISAM";
    $wpdb->query($sql);
}

function output_fin($id = FALSE){
    if(!$id || !is_int($id)){
        $out = 'Es ist ein Fehler w&auml;hrend der Berechnung aufgetreten...';
    } else {
        global $rtimes2; global $iloop;
        if($rtimes2 != 1) $xi = ' <i>(# '.$iloop.')</i>';
        $out = '<h1 style="text-align: center;font-size: 30px">Simulationsprozess'.$xi.' beendet</h1><p style="text-align: center">Die Simulation wurde durchgef&uuml;hrt und die dazugeh&ouml;rige Auswertung kann unter dem folgenden Link abgerufen werden:</p>
                <p style="text-align: center"><a href="'.get_permalink( $id ).'" title="Link zu: '.get_the_title($id).'" style="color: #900; font-size: 20px; text-decoration: underline">Link zur Simulationsauswertung</a></p>';
                
    }

echo $out;
}

function report_func(){
    global $wpdb; global $divin; require_once('generating.php');   
        
    $sql = 'SELECT `ID`,`slf`, `boardingtime`, `pax`, `wordpress_id`, `boardingtyp`, `actyp`, `pbblenght`, `swaitingtime`, `boardcardstrategie` AS btyp FROM `'.$wpdb->prefix.'boarding_main` WHERE `swaitingtime` > 5 LIMIT 20000';    
    $sql2 = 'SELECT `main_id`,`waitingtime` FROM `'.$wpdb->prefix.'boarding_waitingtime` WHERE `waitingtime` > 0 LIMIT 50000;';
    $result = $wpdb->get_results($sql);
    $result2 = $wpdb->get_results($sql2);

    if(empty($result[0]->slf)) $divin = '<div><p>Leider keine Daten vorhanden</p></div>';
    else {
        $z = array();    
        foreach($result as $value){
            for($i = 0; $i < 41; $i++){
                if($value->slf <= (0.2 + 0.02 * $i)){
                    if($value->pbblenght == 50 && $value->btyp == 1) $z[$value->boardingtyp][$i][] = ($value->boardingtime)/($value->pax);
                    if($value->actyp == 'B737' && $value->pbblenght == 50 && $value->btyp == 1) $b737[$value->boardingtyp][$i][] = $value->boardingtime;
                    if($value->actyp == 'A320' && $value->pbblenght == 50 && $value->btyp == 1) $a320[$value->boardingtyp][$i][] = $value->boardingtime;
                    if($value->actyp == 'ERJ-190' && $value->pbblenght == 50 && $value->btyp == 1) $erj[$value->boardingtyp][$i][] = $value->boardingtime;
                    
                    if($value->pbblenght == 50 && $value->btyp == 0) $zb[$value->boardingtyp][$i][] = ($value->boardingtime)/($value->pax);
                    if($value->actyp == 'B737' && $value->pbblenght == 50 && $value->btyp == 0) $b737b[$value->boardingtyp][$i][] = $value->boardingtime;
                    if($value->actyp == 'A320' && $value->pbblenght == 50 && $value->btyp == 0) $a320b[$value->boardingtyp][$i][] = $value->boardingtime;
                    if($value->actyp == 'ERJ-190' && $value->pbblenght == 50 && $value->btyp == 0) $erjb[$value->boardingtyp][$i][] = $value->boardingtime;
                    
                    
                    if($value->actyp == 'B737'  && $value->pbblenght == 0  && $value->btyp == 0) $g0[$value->boardingtyp][$i][] = $value->boardingtime;
                    elseif($value->actyp == 'B737'  && $value->pbblenght < 25 && $value->btyp == 0) $g24[$value->boardingtyp][$i][] = $value->boardingtime;
                    elseif($value->actyp == 'B737'  && $value->pbblenght < 50 && $value->btyp == 0) $g49[$value->boardingtyp][$i][] = $value->boardingtime;
                    elseif($value->actyp == 'B737'  && $value->pbblenght == 50 && $value->btyp == 0) $g50[$value->boardingtyp][$i][] = $value->boardingtime;
                    elseif($value->actyp == 'B737'  && $value->pbblenght < 75 && $value->btyp == 0) $g74[$value->boardingtyp][$i][] = $value->boardingtime;
                    elseif($value->actyp == 'B737'  && $value->pbblenght < 100 && $value->btyp == 0) $g99[$value->boardingtyp][$i][] = $value->boardingtime;
                    elseif($value->actyp == 'B737'  && $value->pbblenght > 99.99 && $value->btyp == 0) $g100[$value->boardingtyp][$i][] = $value->boardingtime;
                    
                    if($value->actyp == 'B737'  && $value->pbblenght == 50 && $value->btyp == 1) $waitingtime[$value->boardingtyp][$i][] = $value->swaitingtime;
                    if($value->actyp == 'B737'  && $value->pbblenght == 50 && $value->btyp == 0) $waitingtimeb[$value->boardingtyp][$i][] = $value->swaitingtime;
                    break;  
                }   
            }
        }
        # Berechnen des Durschnittswertes
        foreach($z as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $med[$key][$bkey] = $sum / $k;
            }
        }
        
        # Berechnen der B737er SLF-Boardingtime
        foreach($b737 as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $b737t[$key][$bkey] = $sum / $k;
            }
        }
        
        # Berechnen der A320er SLF-Boardingtime
        foreach($a320 as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $a320t[$key][$bkey] = $sum / $k;
            }
        }
        
        # Berechnen der ERJ 190er SLF-Boardingtime
        foreach($erj as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $erjt[$key][$bkey] = $sum / $k;
            }
        }
        
        # Berechnen des Durschnittswertes
        foreach($zb as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $medb[$key][$bkey] = $sum / $k;
            }
        }
        
        # Berechnen der B737er SLF-Boardingtime
        foreach($b737b as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $b737tb[$key][$bkey] = $sum / $k;
            }
        }
        
        # Berechnen der A320er SLF-Boardingtime
        foreach($a320b as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $a320tb[$key][$bkey] = $sum / $k;
            }
        }
        
        # Berechnen der ERJ 190er SLF-Boardingtime
        foreach($erjb as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $erjtb[$key][$bkey] = $sum / $k;
            }
        }
        
        # Berechnen des Gatewayeinflusses g0
        foreach($g0 as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $g[0][$key][$bkey] = $sum / $k;
            }
        }
        # Berechnen des Gatewayeinflusses g24
        foreach($g24 as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $g[24][$key][$bkey] = $sum / $k;
            }
        }
        # Berechnen des Gatewayeinflusses g49
        foreach($g49 as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $g[49][$key][$bkey] = $sum / $k;
            }
        }
        # Berechnen des Gatewayeinflusses g50
        foreach($g50 as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $g[50][$key][$bkey] = $sum / $k;
            }
        }
        # Berechnen des Gatewayeinflusses g74
        foreach($g74 as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $g[74][$key][$bkey] = $sum / $k;
            }
        }
        # Berechnen des Gatewayeinflusses g99
        foreach($g99 as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $g[99][$key][$bkey] = $sum / $k;
            }
        }
        # Berechnen des Gatewayeinflusses g100
        foreach($g100 as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $sum = 0; $k = 0;
                foreach($value as $vl){    
                    $sum = $sum + $vl;
                    $k++;
                }
                $g[100][$key][$bkey] = $sum / $k;
            }
        }
        
        #Berechne das Histogramm der Wartezeiten 
        foreach($waitingtime as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $tsum = 0; $k = 0;
                foreach($value as $vl){    
                    $tsum = $tsum + $vl;
                    $k++;
                }
                $twaitingtime[$key][$bkey] = $tsum / $k;
            }
        }
        
        #Berechne das Histogramm der Wartezeiten 
        foreach($waitingtimeb as $bkey=>$btyp){
            foreach($btyp as $key=>$value){
                $tsum = 0; $k = 0;
                foreach($value as $vl){    
                    $tsum = $tsum + $vl;
                    $k++;
                }
                $twaitingtimeb[$key][$bkey] = $tsum / $k;
            }
        }
        
        # Scriptoutput der BTime je Pax/SLF
        
        $scripoutput = 'var data = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Boarding\', \'Random-Boarding\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($med[$i]['blocked']))?'null': round($med[$i]['blocked'],2);    
            $b = (!isset($med[$i]['normal']))?'null' : round($med[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization\')).
                        draw(data, {curveType: "function",
                                    width: 500, height: 400,
                                    vAxis: {maxValue: 13},
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
        
        # Scriptoutput der BTime je Pax/SLF
        
        $scripoutput .= 'var datab = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Boarding\', \'Random-Boarding\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($medb[$i]['blocked']))?'null': round($medb[$i]['blocked'],2);    
            $b = (!isset($medb[$i]['normal']))?'null' : round($medb[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualizationb\')).
                        draw(datab, {curveType: "function",
                                    width: 500, height: 400,
                                    vAxis: {maxValue: 13},
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
        
        # Scriptoutput für BTIME je SLF / B737
        $scripoutput .= 'var data2 = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Boarding\', \'Random-Boarding\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($b737t[$i]['blocked']))?'null': round($b737t[$i]['blocked'],2);    
            $b = (!isset($b737t[$i]['normal']))?'null' : round($b737t[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization2\')).
                        draw(data2, {curveType: "function",
                                    width: 500, height: 400,
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
                            
                            # Scriptoutput für BTIME je SLF / B737
        $scripoutput .= 'var data2b = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Boarding\', \'Random-Boarding\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($b737tb[$i]['blocked']))?'null': round($b737tb[$i]['blocked'],2);    
            $b = (!isset($b737tb[$i]['normal']))?'null' : round($b737tb[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization2b\')).
                        draw(data2b, {curveType: "function",
                                    width: 500, height: 400,
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
        
        # Scriptoutput für BTIME je SLF / A320
        $scripoutput .= 'var data3 = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Boarding\', \'Random-Boarding\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($a320t[$i]['blocked']))?'null': round($a320t[$i]['blocked'],2);    
            $b = (!isset($a320t[$i]['normal']))?'null' : round($a320t[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization3\')).
                        draw(data3, {curveType: "function",
                                    width: 500, height: 400,
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
        
        # Scriptoutput für BTIME je SLF / A320
        $scripoutput .= 'var data3b = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Boarding\', \'Random-Boarding\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($a320tb[$i]['blocked']))?'null': round($a320tb[$i]['blocked'],2);    
            $b = (!isset($a320tb[$i]['normal']))?'null' : round($a320tb[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization3b\')).
                        draw(data3b, {curveType: "function",
                                    width: 500, height: 400,
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
        
        # Scriptoutput für BTIME je SLF / ERJ
        $scripoutput .= 'var data3x = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Boarding\', \'Random-Boarding\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($erjt[$i]['blocked']))?'null': round($erjt[$i]['blocked'],2);    
            $b = (!isset($erjt[$i]['normal']))?'null' : round($erjt[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization3x\')).
                        draw(data3x, {curveType: "function",
                                    width: 500, height: 400,
                                    vAxis: {minValue: 500},
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
       
       # Scriptoutput für BTIME je SLF / ERJ
        $scripoutput .= 'var data3xb = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Boarding\', \'Random-Boarding\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($erjtb[$i]['blocked']))?'null': round($erjtb[$i]['blocked'],2);    
            $b = (!isset($erjtb[$i]['normal']))?'null' : round($erjtb[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization3xb\')).
                        draw(data3xb, {curveType: "function",
                                    width: 500, height: 400,
                                    vAxis: {minValue: 500},
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
        
        # Scriptoutput für BTIME je SLF / B737 und Gatewayl
        $scripoutput .= 'var data4 = google.visualization.arrayToDataTable([
                      [\'x\', \'0m\', \'< 25m\', \'< 50m\', \'= 50m\', \'< 75m\', \'< 99m\', \'> 100m\', \'0m (block)\', \'< 25m (block)\', \'< 50m (block)\', \'= 50m (block)\', \'< 75m (block)\', \'< 99m (block)\', \'> 100m (block)\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($g[0][$i]['normal']))?'null': round($g[0][$i]['normal'],2);    
            $b = (!isset($g[24][$i]['normal']))?'null' : round($g[24][$i]['normal'],2);
            $c = (!isset($g[49][$i]['normal']))?'null' : round($g[49][$i]['normal'],2);
            $d = (!isset($g[50][$i]['normal']))?'null' : round($g[50][$i]['normal'],2);
            $e = (!isset($g[74][$i]['normal']))?'null' : round($g[74][$i]['normal'],2);
            $f = (!isset($g[99][$i]['normal']))?'null' : round($g[99][$i]['normal'],2);
            $gg = (!isset($g[100][$i]['normal']))?'null' : round($g[100][$i]['normal'],2);
            $ab = (!isset($g[0][$i]['blocked']))?'null': round($g[0][$i]['blocked'],2);    
            $bb = (!isset($g[24][$i]['blocked']))?'null' : round($g[24][$i]['blocked'],2);
            $cb = (!isset($g[49][$i]['blocked']))?'null' : round($g[49][$i]['blocked'],2);
            $db = (!isset($g[50][$i]['blocked']))?'null' : round($g[50][$i]['blocked'],2);
            $eb = (!isset($g[74][$i]['blocked']))?'null' : round($g[74][$i]['blocked'],2);
            $fb = (!isset($g[99][$i]['blocked']))?'null' : round($g[99][$i]['blocked'],2);
            $ggb = (!isset($g[100][$i]['blocked']))?'null' : round($g[100][$i]['blocked'],2);
            if($a != 'null' || $b != 'null' || $c != 'null' || $d != 'null' || $e != 'null' || $f != 'null' || $gg != 'null' || $ab != 'null' || $bb != 'null' || $cb != 'null' || $db != 'null' || $eb != 'null' || $fb != 'null' || $ggb != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.','.$c.','.$d.','.$e.','.$f.','.$gg.','.$ab.','.$bb.','.$cb.','.$db.','.$eb.','.$fb.','.$ggb.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization4\')).
                        draw(data4, {curveType: "none",
                                    width: 1000, height: 560,
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    vAxis: {maxValue: 10}}
                            );';
        
        # Scriptoutput für Wartezeit je SLF / B737
        $scripoutput .= 'var data5 = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Gangwartezeit\', \'Random-Gangwartezeit\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($twaitingtime[$i]['blocked']))?'null': round($twaitingtime[$i]['blocked'],2);    
            $b = (!isset($twaitingtime[$i]['normal']))?'null' : round($twaitingtime[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization5\')).
                        draw(data5, {curveType: "function",
                                    width: 500, height: 400,
                                    vAxis: {maxValue: 10},
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
        
        # Scriptoutput für Wartezeit je SLF / B737
        $scripoutput .= 'var data5b = google.visualization.arrayToDataTable([
                      [\'x\', \'Blocked-Gangwartezeit\', \'Random-Gangwartezeit\'],';
        
        for($i = 0; $i < 41; $i++){
            $a = (!isset($twaitingtimeb[$i]['blocked']))?'null': round($twaitingtimeb[$i]['blocked'],2);    
            $b = (!isset($twaitingtimeb[$i]['normal']))?'null' : round($twaitingtimeb[$i]['normal'],2);
            if($a != 'null' || $b != 'null') $scripoutput .= '['.(0.2+$i*0.02).','.$a.','.$b.'],';
        }
        $scripoutput = substr($scripoutput,0,-1);
        $scripoutput .= ']);
                  
                    // Create and draw the visualization.
                    new google.visualization.LineChart(document.getElementById(\'visualization5b\')).
                        draw(data5b, {curveType: "function",
                                    width: 500, height: 400,
                                    vAxis: {maxValue: 10},
                                    legend: {position: \'top\'},
                                    chartArea:{left:"15%",top:"15%",width:"70%",height:"70%"},
                                    series: [{color: \'black\', lineWidth: 0, pointSize: 2},{color: \'red\', lineWidth: 0, pointSize: 2}]}
                            );';
        
        $divin = '<div style="clear:both"><div style="float: left;width:48%"><h4>Durchschnittliche Boardingzeit je Passagier / SLF</h4><p style="text-align: center"><i>(PBB 50m - Konserative Kartenverteilung)</i></p><div id="visualization" style="width: 500px; height: 400px;"></div></div>';
        $divin .= '<div style="float:left; width: 48%"><h4>Durchschnittliche Boardingzeit je Passagier / SLF</h4><p style="text-align: center"><i>(PBB 50m - Freie Kartenverteilung)</i></p><div id="visualizationb" style="width: 500px; height: 400px;"></div></div></div>';
        
        $divin .= '<div style="clear:both"><div style="float: left;width:48%"><h4>B737 - Durchschnittliche Boardingzeit / SLF</h4><p style="text-align: center"><i>(PBB 50m - Konserative Kartenverteilung)</i></p><div id="visualization2" style="width: 500px; height: 400px;"></div></div>';
        $divin .= '<div style="float:left; width: 48%"><h4>B737</h4><p style="text-align: center"><i>(PBB 50m - Freie Kartenverteilung)</i></p><div id="visualization2b" style="width: 500px; height: 400px;"></div></div></div>';
        
        $divin .= '<div style="clear:both"><div style="float: left;width:48%"><h4>A320 - Durchschnittliche Boardingzeit / SLF</h4><p style="text-align: center"><i>(PBB 50m - Konserative Kartenverteilung)</i></p><div id="visualization3" style="width: 500px; height: 400px;"></div></div>';
        $divin .= '<div style="float:left; width: 48%"><h4>A320</h4><p style="text-align: center"><i>(PBB 50m - Freie Kartenverteilung)</i></p><div id="visualization3b" style="width: 500px; height: 400px;"></div></div></div>';
        
        $divin .= '<div style="clear:both"><div style="float: left;width:48%"><h4>ERJ-190 - Durchschnittliche Boardingzeit / SLF</h4><p style="text-align: center"><i>(PBB 50m - Konserative Kartenverteilung)</i></p><div id="visualization3x" style="width: 500px; height: 400px;"></div></div>';
        $divin .= '<div style="float:left; width: 48%"><h4>ERJ-190</h4><p style="text-align: center"><i>(PBB 50m - Freie Kartenverteilung)</i></p><div id="visualization3xb" style="width: 500px; height: 400px;"></div></div></div>';
        
        $divin .= '<h4>Einfluss der Gatewaylänge am Beispiel B737 auf die Boardingzeit</h4><div id="visualization4" style="width: 1000px; height: 560px;"></div>';
        $divin .= '<div style="clear:both"><div style="float: left;width:48%"><h4>Wartezeit im Gang mit Abhängigkeit zum SLF (B737)</h4><p style="text-align: center"><i>(PBB 50m - Konserative Kartenverteilung)</i></p><div id="visualization5" style="width: 500px; height: 400px;"></div></div>';
        $divin .= '<div style="float:left; width: 48%"><h4>Wartezeit im Gang mit Abhängigkeit zum SLF (B737)</h4><p style="text-align: center"><i>(PBB 50m - Freie Kartenverteilung)</i></p><div id="visualization5b" style="width: 500px; height: 400px;"></div></div></div>';
        return($scripoutput);    
    }    
    
    
        
    $out = '<h2>Auswertungsprotokoll der Boardingsimulationen</h2>';
    $out.= '<div><h3>Durchschnittliche Boardingzeit je Passagier / SLF (PBB 30 - 70m)</h3>';
    $out.= '';
    $out.= '</div>';    
}
function report_func_ct(){
    global $divin;
    
    $out = '';
    $out.= '<div>';
    $out.= $divin;
    $out.= '</div>';
    
    return($out);    
}
function start_simulation(){
    require_once('generating.php');
    require_once('statistics.php');
    require_once('values.php'); 

    $start = time(); $scripttime = microtime(1);
    $rtimes = (is_int(intval($_REQUEST['rounds']))) ?  ($_REQUEST['rounds'] > 0 && $_REQUEST['rounds'] < 5) ? intval($_REQUEST['rounds']) : 1 : 1;
    #if($rtimes == 50) error_reporting(E_ALL);
    $rtimes2 = $rtimes;
    define('DEV', ($_GET['developer'] == '1') ? TRUE : FALSE);
    define('VIZ_BOARDCARD', ($_REQUEST['vis'] == 'nu') ? TRUE : FALSE);
    define('PBB', ($_REQUEST['pbb'] == 'nu') ? (is_int(intval($_REQUEST['pbbl'])) && $_REQUEST['pbbl'] > 0) ? TRUE : FALSE : FALSE);
    define('PBBL', (is_int(intval($_REQUEST['pbbl']))) ? $_REQUEST['pbbl'] : 0);
    define('BLOCKCT', check_blocksize($_REQUEST['ctblocks']));
    define('BOARDINGTYP', check_boardingtyp($_REQUEST['boarding']));
    define('BLOCKTIME', check_blocktime($_REQUEST['bltime']));
    
    $boarding = ($_REQUEST['boarding'] == 3) ? 'random' : 'normal';
    #phpinfo(-1);
    global $charge; global $group; global $seatsbo; global $summary; global $iloop, $rtimes2;

    for($i = 0; $i < $rtimes; $i++){
        $iloop = $i + 1;         
        $seats = get_seats_by_airplane('seats');    
        if(DEV) echo (microtime(1) - $scripttime).'<br/>';
        
        generate_aircraft_factors($seats,$_REQUEST['factor'],$boarding,$_REQUEST['flycat']);    
        if(DEV) echo (microtime(1) - $scripttime).'<br/>';
               
        $persons = generate_persons($charge['seats'],$charge['seats_used']);
        if(!$persons) die('Fehler bei Buchung...'); if(DEV) echo (microtime(1) - $scripttime).'<br/>';
        
        $group = generate_checkin($boarding,$persons);
        if(!$group) die('Fehler bei Checkin'); if(DEV) echo (microtime(1) - $scripttime).'<br/>';
        
        # Passagierbrücken / Vorfeld-Transport wird abstrahiert als eine v-abhängige FUnktion...
        
        $ttemp = generate_timestamp_on_door($group,$persons);
        $doortimestamp = $ttemp[1]; $persons = $ttemp[0];
        if(!$doortimestamp) die('Fehler bei Zeitdauerberechnung (Gate to Door)'); if(DEV) echo (microtime(1) - $scripttime).'<br/>';
        
        $group = sort_group_array_keyID($group);
        if(!$group) die('FEHLER! - doppelter Gruppenkey vorhanden'); if(DEV) echo (microtime(1) - $scripttime).'<br/>';

        $boardingprss = generate_boarding($persons,$group,$doortimestamp,$boarding);
        if(!$boardingprss) die('Fehler bei Zeitdauerberechnung (Boarding)'); if(DEV) echo (microtime(1) - $scripttime).'<br/>';

        $summary = generate_summary($charge,$persons,$group,$boardingprss[0]);
        if(DEV) echo (microtime(1) - $scripttime).'<br/>';

        $wppost = generate_single_output($summary,(microtime(1)-$scripttime));
        if(DEV) echo (microtime(1) - $scripttime).'<br/>';

        $theid = create_wp_post($wppost);
        if($theid != 0) output_fin($theid);
        
        fill_database_with_values();         
        if((time()-$start) < 23 && $rtimes != 1) $rtimes++;
        elseif($rtimes == 1) $i = $rtimes + 5;
        else $i = $rtimes + 5;
        #_unset_boarding('all');
    }       
        /**
        if($charge['unassigned']['normal'] < 0) echo 'normal: '.$charge['unassigned']['normal'].'<br />';
        if($charge['unassigned']['biz'] < 0) echo 'biz: '.$charge['unassigned']['biz'].'<br />';
        if($charge['unassigned']['old'] < 0) echo 'old: '.$charge['unassigned']['old'].'<br />';
        if($charge['unassigned']['child'] < 0) echo 'child: '.$charge['unassigned']['child'].'<br /><br />';
        
    }**/
}


register_activation_hook(__FILE__,'install_db_boardingsim');
add_shortcode( 'foobar', 'input_func' );
add_shortcode( 'report', 'report_func_ct');

add_action( 'admin_menu', 'init_boarding_sim' );
