<?
include("inc/impresoras_fiscales.php");

$_controladora_fiscal = new Hasar();

$ticket = $_controladora_fiscal->encabezado($nombre,$direccion,$documento,$tipo_doc,$iva, $letra_fac);

foreach($_["articulos"] as $art) {
	if($art["codigo"]) {
		$idarticulo = get_campo("articulos", $art["codigo"], "id", "codigo");
		
		$iva = get_campo("articulos", $art["codigo"], "tasa_iva", "codigo");
		
		$precio_unitario = get_campo("articulos", $art["codigo"], "precio_articulo(id)", "codigo");
		
		$idcategoria = get_campo("clientes", $_["idcliente"], "idcategoria");

		$descuento = get_campo("clientes_categorias", $idcategoria, "descuento");
		$precio_unitario = $precio_unitario*(1-($descuento/100));
		
		$precio_unitario = number_format($precio_unitario*(1-($art["descuento"]/100)), 2, ".", "");
			
		$aux_precio_unitario = $precio_unitario;
		$precio_sin_iva = round($aux_precio_unitario/(1+($iva/100)),3);
		
		while(round($precio_sin_iva*(1+($iva/100)),2)!=round($precio_unitario,2)) {
			
			$precio_sin_iva = round($aux_precio_unitario/(1+($iva/100)),3) + 0.001 * ((round($precio_sin_iva*(1+($iva/100)),3)<$precio_unitario)?1:-1);
			
		}
		
		$descripcion = substr(get_campo("articulos", $art["codigo"], "nombre", "codigo"),0,20);
		
		
		$ticket .= $_controladora_fiscal->agrega_item($descripcion, $art["cantidad"], $precio_sin_iva, $iva);
		
		$total += $precio_unitario*$art["cantidad"];
		
		$total_gravado += $precio_sin_iva;
	}
}

$intereses = $_["total_valores"]-$_["total_valores_sini"];
if($intereses>0) {
	$total += $intereses;
	
	$intereses = number_format($intereses / (1+(TASA_IVA_DESCUENTOS/100)),2, ".", "");
	$ticket .= $_controladora_fiscal->agrega_item("Intereses", 1, $descuento_monto, TASA_IVA_DESCUENTOS);
	
	$total_gravado += $intereses;
}

if($_["descuento_general"]) {
	if($_["idtipo_descuento"]=="%") {
		$descuento_tasa = $_["descuento_general"];
		$descuento_monto = $total * $descuento_tasa/100;
	} elseif($_["idtipo_descuento"]=="$") {
		$descuento_monto = $_["descuento_general"];
	}
	
	$total -= $descuento_monto;
	
	$descuento_monto = number_format($descuento_monto / (1+(TASA_IVA_DESCUENTOS/100)),2,".","");
	
	$ticket .= $_controladora_fiscal->agrega_item("Descuento", 1, $descuento_monto, TASA_IVA_DESCUENTOS, "m");
	
	$total_gravado -= $descuento_monto;
}

$ticket .= $_controladora_fiscal->pie($total);

$respuesta = $_controladora_fiscal->procesar_ixbatch($ticket);

$respuesta = $_controladora_fiscal->estado();
list($nada,$status_impresora,$status_fiscal,$ultimo_b_c,$status_auxiliar,$ultimo_a) = explode(chr(hexdec("1c")), $respuesta);

