<?php 
function function_exponential_distribution($lambda = 0.3){
    $x = -1 * 1/$lambda * log(1 - (rand(10,9999900) / 10000000));
    return($x);
}
function function_poisson_distribution($lambda = 1,$k = 10){
    $F_lambda = array(); $sum = 0; $k_fak = 1;

    for($i = 0; $i < ($k + 1); $i++){
        $k_fak = ($i == 0) ? 1 : $k_fak * $i;    
        $sum = $sum + pow($lambda,$i)/$k_fak;         
        $F_lambda[$i]['value'] = exp(-1 * $lambda) + $sum;
        $F_lambda[$i]['k'];  
    }
    
    $random = rand(0,100000) / 100000;
    
    for($i = 0; $i < count($F_lambda); $i++){
        if($F_lambda[$i]['value'] < $random) return($F_lambda[$i]['k']);
    }
    
    return($k);    
}

function function_normal_random($my = 1.5,$sig = 0.5){
    
    $sig = sqrt($sig);
    $sum = 0;
    
    for($i = 0; $i < 12; $i++){
        $sum = $sum + (rand((100 + $i * 5),(1100 + $i *5)) - 100 + $i * 5) / 1000;
    }
    $s = ($sum - 6) * $sig + $my;
    
    return($s);
}

function function_triangular_random($prob = 0.9){
    $c = (is_numeric($prob) && $prob < 1) ? $prob : 0.9;
    
    $j = 2;
    
    for($i = 0; $i < $j; $i++){
        $x = rand(0,10000) / 10000;
        $y = rand(0,5000) / 10000;
        
        if($y > ((0.5 / $c) * $x) || $y > ((0.5 / ($c - 1) * $x - (0.5 / ($c - 1))))) $j++;
        else {
            return($x);
        }

    }    
}

function function_triangular_distribution($a = 0, $b = 1, $prob = 0.9,$y = 0.9){
    $c = (is_numeric($prob) && $prob < 1) ? $prob : 0.9;
    $c = ($c - $a) / ($b - $a);
    $y = (is_numeric($y) && $y < 1) ? $y : 0.9;
    
    $a = ($a > $b || $a > $c) ? 0 : $a;
    $b = ($b < $c) ? 1 : $b;
    
    $c = ($c > $b || $c < $a) ? 0.5 : $c;
    
    $value = ($y <= $c) ? (sqrt($y * ($b - $a) * ($c - $a)) + $a) : (-1 * (sqrt((1 - $y) * ($b - $a) * ($b - $c)) - $b));
    
    return($value);
       
}

function function_weibull_distribution($lambda = 1, $k = 2, $max = FALSE){
    $x = pow(-log(-1 * ((rand(10,9999900) / 10000000) - 1)), (1/$k)) * $lambda;    
    if(is_numeric($max) && $max !== FALSE){
        $x = ($x > $max) ? $max : $x;
    }
    return($x);   
}

function random_person_typ($person_by_fly_cat = 'norm'){
    switch($person_by_fly_cat){
        case 'norm': # keine spezifizierten Angaben, die Art der Passagiere ist Gleichverteilt mit 25er-Intervallen
            
            $random = rand(0,99);
            
            if($random < 25) return('normal');
            elseif($random < 50) return('biz');
            elseif($random < 75) return('child');
            else return('old');
                
        break;
        
        case 'biz':
            
            $biz_prob = 90;
            if(rand(0,1) > 0) return('biz');
            
            $random = rand(0,99);
            if($random < 50) return('norma');
            elseif($random < 65) return('child');
            else return('old');
            
        break;
        
        default:
            return(FALSE);    
        break;
    }    
}