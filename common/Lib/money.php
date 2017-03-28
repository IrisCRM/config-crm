<?php
function semantic($i,&$words,&$fem,$f){
	$_1_2[1]="одна "; $_1_2[2]="две ";
 
	$_1_19[1]="один "; $_1_19[2]="два "; $_1_19[3]="три "; $_1_19[4]="четыре "; $_1_19[5]="пять "; $_1_19[6]="шесть "; $_1_19[7]="семь "; $_1_19[8]="восемь "; $_1_19[9]="девять "; $_1_19[10]="десять ";
 
	$_1_19[11]="одиннацать "; $_1_19[12]="двенадцать "; $_1_19[13]="тринадцать "; $_1_19[14]="четырнадцать "; $_1_19[15]="пятнадцать ";
	$_1_19[16]="шестнадцать "; $_1_19[17]="семнадцать "; $_1_19[18]="восемнадцать "; $_1_19[19]="девятнадцать ";
 
	$des[2]="двадцать "; $des[3]="тридцать "; $des[4]="сорок "; $des[5]="пятьдесят "; $des[6]="шестьдесят "; $des[7]="семьдесят "; $des[8]="восемдесят "; $des[9]="девяносто ";
 
	$hang[1]="сто "; $hang[2]="двести "; $hang[3]="триста "; $hang[4]="четыреста "; $hang[5]="пятьсот ";
	$hang[6]="шестьсот "; $hang[7]="семьсот "; $hang[8]="восемьсот ";$hang[9]="девятьсот ";

    $words="";
    $fl=0;
    if($i >= 100){
        $jkl = intval($i / 100);
        $words.=$hang[$jkl];
        $i%=100;
    }
    if($i >= 20){
        $jkl = intval($i / 10);
        $words.=$des[$jkl];
        $i%=10;
        $fl=1;
    }
    switch($i){
        case 1: $fem=1; break;
        case 2:
        case 3:
        case 4: $fem=2; break;
        default: $fem=3; break;
    }
    if( $i ){
        if( $i < 3 && $f > 0 ){
            if ( $f >= 2 ) {
                $words.=$_1_19[$i];
            }
            else {
                $words.=$_1_2[$i];
            }
        }
        else {
            $words.=$_1_19[$i];
        }
    }
}
 
 
function num2str($L, $p_valuta=0, $up=1) {
	$namerub[0][1]="тенге "; 	$namerub[0][2]="тенге "; 	$namerub[0][3]="тенге ";
	$namerub[1][1]="рубль "; 	$namerub[1][2]="рубля "; 	$namerub[1][3]="рублей ";
	$namerub[2][1]="евро "; 	$namerub[2][2]="евро "; 	$namerub[2][3]="евро ";
	$namerub[3][1]="доллар "; 	$namerub[3][2]="доллара "; 	$namerub[3][3]="долларов ";
	$namerub[4][1]=" "; 		$namerub[4][2]=" "; 		$namerub[4][3]=" ";


	$nametho[1]="тысяча "; 		$nametho[2]="тысячи "; 		$nametho[3]="тысяч ";
	$namemil[1]="миллион "; 	$namemil[2]="миллиона "; 	$namemil[3]="миллионов ";
	$namemrd[1]="миллиард "; 	$namemrd[2]="миллиарда "; 	$namemrd[3]="миллиардов ";

	$kopeek[0][1]="тиын "; 		$kopeek[0][2]="тиын "; 		$kopeek[0][3]="тиын ";
	$kopeek[1][1]="копейка "; 	$kopeek[1][2]="копейки "; 	$kopeek[1][3]="копеек ";
	$kopeek[2][1]="цент "; 		$kopeek[2][2]="цента "; 	$kopeek[2][3]="центов ";
	$kopeek[3][1]="цент "; 		$kopeek[3][2]="цента "; 	$kopeek[3][3]="центов ";
	$kopeek[4][1]=""; 			$kopeek[4][2]=""; 		$kopeek[4][3]="";

    $s=" ";
    $s1=" ";
    $s2=" ";
    //$kop=intval( ( $L*100 - intval( $L )*100 ));
	$L = number_format((float)str_replace(' ', '', $L), 2, '.', '');
	$out = explode('.', $L);
	$kop = ($out[1] == '') ? 0 : (int)$out[1];
	
    $L=intval($L);
    if($L>=1000000000){
        $many=0;
        semantic(intval($L / 1000000000),$s1,$many,3);
        $s.=$s1.$namemrd[$many];
        $L%=1000000000;
    }
 
    if($L >= 1000000){
        $many=0;
        semantic(intval($L / 1000000),$s1,$many,2);
        $s.=$s1.$namemil[$many];
        $L%=1000000;
        if($L==0){
            $s.=$namerub[$p_valuta][3];
        }
    }
 
    if($L >= 1000){
        $many=0;
        semantic(intval($L / 1000),$s1,$many,1);
        $s.=$s1.$nametho[$many];
        $L%=1000;
        if($L==0){
            $s.=$namerub[$p_valuta][3];
        }
    }
 
    if($L != 0){
        $many=0;
        semantic($L,$s1,$many,0);
        $s.=$s1.$namerub[$p_valuta][$many];
    }
 
	if ($p_valuta != 4) {
	    if($kop > 0){
	        $many=0;
	        semantic($kop,$s1,$many,1);
	        $s.=$s1.$kopeek[$p_valuta][$many];
	    }
	    else {
	        $s.=" 00 ".$kopeek[$p_valuta][3];
	    }
	}

    //Убрать первый и последний пробел 
    $s = trim($s);
    if ($up) {
    	$s = iris_strtoupper($s);
    }
    
    return $s;
}
?>