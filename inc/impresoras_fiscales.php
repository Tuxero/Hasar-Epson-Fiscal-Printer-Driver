<?
class Hasar {
	var $con_direccion;
	var $comandos =   Array("HistoryCapacity" => 57,
							"DailyCloseByDate" => 58,
							"DailyCloseByNumber" => 59,
							"GetDailyReport" => 60,
							"SendFirstIVA" => 112,
							"NextIVATransmission" => 113,
							"OpenFiscalReceipt" => 64,
							"PrintFiscalText" => 65,
							"PrintLineItem" => 66,
							"LastItemDiscount" => 85,
							"GeneralDiscount" => 84,
							"ReturnRecharge" => 84,
							"ChargeNonRegisteredTax" => 97,
							"Perceptions" => 96,
							"Subtotal" => 67,
							"TotalTender" => 68,
							"CloseFiscalReceipt" => 69,
							"OpenNonFiscalReceipt" => 72,
							"OpenNonFiscalSlip" => 71,
							"PrintNonFiscalText" => 73,
							"CloseNonFiscalReceipt" => 74,
							"CutNonFiscalReceipt" => 75,
							"DNFHFarmacias" => 72,
							"DNFHReparto" => 105,
							"SetVoucherData1" => 106,
							"SetVoucherData2" => 107,
							"PrintVoucher" => 82,
							"SetDateTime" => 88,
							"GetDateTime" => 89,
							"SetHeaderTrailer" => 93,
							"GetHeaderTrailer" => 94,
							"SetCustomerData" => 98,
							"SetFantasyName" => 123,
							"WriteDisplay" => 178,
							"StatusRequest" => 42);
	var $seq_num=0;
	
	function mover_seq_num() {
		if($this->seq_num==0) { $this->seq_num = ord(file_get_contents("seq.num")); }
		
		$this->seq_num += 2;
		if($this->seq_num==(hexdec("7f")+1)) {
			$this->seq_num = hexdec("20");
		}
		
		file_put_contents("seq.num", chr($this->seq_num));
		
		return chr($this->seq_num);
	}
	function calcular_bcc($mensaje) {
		$suma = 0;
		foreach($mensaje as $m) {
			$aux = str_split($m);
			foreach($aux as $a) {
				$suma = $suma + (is_string($a)?ord($a):$a);
				
			}
		}
		
		return implode("", $mensaje).str_pad(dechex($suma),4,"0", STR_PAD_LEFT)."\r\n";
	}
	function estado() {
		$mensaje = array(chr(2),
					$this->mover_seq_num(),
					chr($this->comandos["StatusRequest"]),
					chr(3)
				);
		$ret .= $this->calcular_bcc($mensaje);
		
		return $this->procesar_ixbatch($ret);
	}
	function encabezado($razon_social,$direccion,$documento,$tipo_documento,$categoria_iva,$letra) {
		$_codigos_categorias_iva = array(1 => "I",
										 2 => "N",
										 4 => "E",
										 3 => "A",
										 5 => "C",
										 6 => "M",
										 7 => "T"
										 );
		$categoria_iva = $_codigos_categorias_iva[$categoria_iva];
		
		$mensaje = array(chr(2),
					$this->mover_seq_num(),
					chr($this->comandos["SetCustomerData"]),
					chr(hexdec("1c")),
					"$razon_social",
					chr(hexdec("1c")),
					"$documento",
					chr(hexdec("1c")),
					"$categoria_iva",
					chr(hexdec("1c")),
					"$tipo_documento",
					chr(hexdec("1c")),
					($this->con_direccion?"$direccion":" "),
					chr(3)
				);
		$ret .= $this->calcular_bcc($mensaje);
		
		$mensaje = array(chr(2),
						$this->mover_seq_num(),
						chr($this->comandos["OpenFiscalReceipt"]),
						chr(hexdec("1c")),
						"$letra",
						chr(hexdec("1c")),
						"T",
						chr(3));
		
		$ret .= $this->calcular_bcc($mensaje);

		return $ret;
	}
	
	function agrega_item($descripcion,$cantidad,$precio_unitario,$iva,$tipo_item="M") {
		$mensaje = array(chr(2),
						$this->mover_seq_num(),
						chr($this->comandos["PrintLineItem"]),
						chr(hexdec("1c")),
						"$descripcion",
						chr(hexdec("1c")),
						"$cantidad",
						chr(hexdec("1c")),
						"$precio_unitario",
						chr(hexdec("1c")),
						"$iva".chr(hexdec("1c")),
						$tipo_item,
						chr(hexdec("1c")),
						"0.0",
						chr(hexdec("1c")),
						"1",
						chr(hexdec("1c")),
						"B",
						chr(3));
		$ret = $this->calcular_bcc($mensaje);
		
		return $ret;
	}
	
	function pie($monto_pagado) {
		$mensaje = array(chr(2),
						$this->mover_seq_num(),
						chr($this->comandos["Subtotal"]),
						chr(hexdec("1c")),
						"P",
						chr(hexdec("1c")),
						"Subtotal",
						chr(hexdec("1c")),
						"0",
						chr(3));
		$ret .= $this->calcular_bcc($mensaje);
		
		$mensaje = array(chr(2),
						$this->mover_seq_num(),
						chr($this->comandos["TotalTender"]),
						chr(hexdec("1c")),
						"Contado",
						chr(hexdec("1c")),
						"$monto_pagado",
						chr(hexdec("1c")),
						"T",
						chr(hexdec("1c")),
						"0",
						chr(3));
		
		$ret .= $this->calcular_bcc($mensaje);
		
		$mensaje = array(chr(2),
						$this->mover_seq_num(),
						chr($this->comandos["CloseFiscalReceipt"]),
						chr(3));
		$ret .= $this->calcular_bcc($mensaje);
		
		return $ret;
	}
	
	function procesar_ixbatch($ticket) {
		$unid = uniqid();

		$entrada = CARPETA_ROOT."/ixbatch/".TIPO_CONTROLADORA."/tike_{$unid}___".str_replace("COM","",PUERTO_COM_CONTROLADORA)."___.txt";
		$salida = CARPETA_ROOT."/ixbatch/".TIPO_CONTROLADORA."/salida_$unid.txt";

		file_put_contents($entrada, $ticket);

		//$ex = CARPETA_ROOT."/ixbatch/".TIPO_CONTROLADORA."/ixbatchw.exe -p ".PUERTO_COM_CONTROLADORA." -i $entrada -o $salida -s 9600 -t";
		$ex = "cmd /c ".CARPETA_ROOT."/ixbatch/".TIPO_CONTROLADORA."/mibatch6.exe {$entrada}_____{$salida}";

		exec($ex);

		unlink($entrada);
		
		$ret = file_get_contents($salida);
		
		unlink($salida);
		
		return $ret;
	}
}

class Epson {
	function encabezado($razon_social,$direccion,$documento="",$tipo_documento="",$categoria_iva="",$letra="") {
		return "PONEENCABEZADO|1|$razon_social|
@PONEENCABEZADO|6|$direccion|
@TIQUEABRE|C|
";
	}
	
	function agrega_item($descripcion,$cantidad,$precio_unitario,$iva) {
		$iva = $iva/100;
		$aux = explode(".",$iva);
		$iva = ".".$aux[1];
		
		return "@TIQUEITEM|$descripcion|$cantidad|$precio_unitario|$iva|M|1|0|0|
";
	}
	
	function pie($monto_pagado="") {
		return "@TIQUESUBTOTAL|P|Subtotal
@TIQUECIERRA|T|
";
	}
}

