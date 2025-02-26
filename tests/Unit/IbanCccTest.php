<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IbanCccTest extends TestCase
{
    public function test_valid_ccc()
    {
        // Comprovem un CCC correcte
        $this->assertTrue($this->validarCCC('20770024003102575766'));

        // CCC incorrecte (dígits de control erronis)
        $this->assertFalse($this->validarCCC('20770024003102575760'));

        // CCC amb lletres (no hauria de ser vàlid)
        $this->assertFalse($this->validarCCC('2077X024003102575766'));

        // CCC massa curt (menys de 20 dígits)
        $this->assertFalse($this->validarCCC('207700240031025757'));
    }

    public function test_valid_iban()
    {
        // Comprovem un IBAN correcte
        $this->assertTrue($this->validarIBAN('ES7620770024003102575766'));

        // IBAN incorrecte (dígits de control erronis)
        $this->assertFalse($this->validarIBAN('ES0020770024003102575766'));

        // IBAN amb lletres on no toca
        $this->assertFalse($this->validarIBAN('ES76X0770024003102575766'));

        // IBAN massa curt
        $this->assertFalse($this->validarIBAN('ES76207700240031025757'));
    }

    private function validarCCC($ccc)
    {
        // Eliminar espais
        $ccc = str_replace(' ', '', $ccc);

        // Ha de tenir exactament 20 dígits
        if (strlen($ccc) !== 20 || !ctype_digit($ccc)) {
            return false;
        }

        // Dividim el CCC en les seves parts
        $entitat = substr($ccc, 0, 4);
        $oficina = substr($ccc, 4, 4);
        $digitsControl = substr($ccc, 8, 2);
        $compte = substr($ccc, 10, 10);

        // Calculem els dígits de control correctes
        $dcCorrecte = $this->calcularDC($entitat, $oficina, $compte);

        return $digitsControl === $dcCorrecte;
    }

    private function calcularDC($entitat, $oficina, $compte)
    {
        // Pesos utilitzats per calcular els dígits de control
        $pesos = [1, 2, 4, 8, 5, 10, 9, 7, 3, 6];

        // Calcular DC1 (per l'entitat i l'oficina)
        $num1 = '00' . $entitat . $oficina;
        $suma = 0;
        for ($i = 0; $i < 10; $i++) {
            $suma += intval($num1[$i]) * $pesos[$i];
        }
        $dc1 = 11 - ($suma % 11);
        if ($dc1 == 10) $dc1 = 1;
        if ($dc1 == 11) $dc1 = 0;

        // Calcular DC2 (per al compte)
        $suma = 0;
        for ($i = 0; $i < 10; $i++) {
            $suma += intval($compte[$i]) * $pesos[$i];
        }
        $dc2 = 11 - ($suma % 11);
        if ($dc2 == 10) $dc2 = 1;
        if ($dc2 == 11) $dc2 = 0;

        return "{$dc1}{$dc2}";
    }

    private function validarIBAN($iban)
    {
        // Eliminar espais i guions
        $iban = str_replace([' ', '-'], '', $iban);

        // L'IBAN ha de tenir entre 15 i 34 caràcters
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        // Moure els 4 primers caràcters al final
        $iban = substr($iban, 4) . substr($iban, 0, 4);

        // Convertir lletres a números (A=10, B=11, ..., Z=35)
        $ibanNumeric = '';
        for ($i = 0; $i < strlen($iban); $i++) {
            $char = $iban[$i];
            if (ctype_alpha($char)) {
                $ibanNumeric .= (ord($char) - 55); // Converteix lletres en números
            } else {
                $ibanNumeric .= $char;
            }
        }

        // Comprovar si el número és divisible per 97
        return bcmod($ibanNumeric, '97') == '1';
    }

    public function test_forca_bruta_recuperar_ccc()
    {
        // CCC incomplet (últims 2 dígits ocults)
        $cccIncomplet = '207700240031025757**';

        // Intentem trobar els dígits correctes provant de 00 a 99
        $solucioTrobada = false;
        for ($i = 0; $i < 100; $i++) {
            // Afegim els dos dígits provant totes les combinacions
            $cccIntent = str_replace('**', str_pad($i, 2, '0', STR_PAD_LEFT), $cccIncomplet);
            
            // Si la combinació és vàlida, ho guardem
            if ($this->validarCCC($cccIntent)) {
                echo "\nPossible CCC original: $cccIntent\n";
                $solucioTrobada = true;
                break;
            }
        }

        // Assegurem-nos que almenys una combinació ha estat vàlida
        $this->assertTrue($solucioTrobada, "No s'ha pogut recuperar el CCC original.");
    }
}
