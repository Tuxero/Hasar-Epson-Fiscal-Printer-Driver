<?
$txt = file_get_contents("comandos.txt");

$arr = explode("\n", $txt);

foreach($arr as $l) {
	$n_partes = count(explode(".",$l));
	list($cap,$subcap,$subscap,$comando) = explode(".", $l);
	if($cap==3 && $n_partes==4) {
		list($comando_real) = (explode(" - ", $comando));
		$comando_real = ltrim($comando_real);
	}
	
	$aux = explode("               3                 ", $l);
	$aux = explode("H", $aux[1]);
	
	if($ultimo_comando_real=="") { $ultimo_comando_real = $comando_real; }
	
	if($comando_real==$ultimo_comando_real && count($aux)==2) {
		$comms[$comando_real] = hexdec($aux[0]);
	}
	
	$ultimo_comando_real = $comando_real;
}

print_r($comms);
