<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IbanCccTest extends TestCase
{
    public function test_valid_ccc()
    {
        echo "\n--- TEST VALIDACIÓ CCC ---\n";

        // Comprovem un CCC correcte
        $ccc = '20770024003102575766';
        echo "Provant CCC correcte: $ccc...\n";
        $resultat = $this->validarCCC($ccc);
        echo $resultat ? "✅ Correcte!\n" : "❌ Error!\n";
        $this->assertTrue($resultat, "ERROR: CCC correcte detectat com a incorrecte");

        // CCC incorrecte (dígits de control erronis)
        $ccc = '20770024003102575760';
        echo "Provant CCC incorrecte: $ccc...\n";
        $resultat = $this->validarCCC($ccc);
        echo !$resultat ? "✅ Correcte! CCC detectat com a invàlid\n" : "❌ Error!\n";
        $this->assertFalse($resultat, "ERROR: CCC incorrecte detectat com a vàlid");

        // CCC amb lletres (no hauria de ser vàlid)
        $ccc = '2077X024003102575766';
        echo "Provant CCC amb lletres: $ccc...\n";
        $resultat = $this->validarCCC($ccc);
        echo !$resultat ? "✅ Correcte! CCC amb lletres detectat com a invàlid\n" : "❌ Error!\n";
        $this->assertFalse($resultat, "ERROR: CCC amb lletres detectat com a vàlid");

        // CCC massa curt
        $ccc = '207700240031025757';
        echo "Provant CCC massa curt: $ccc...\n";
        $resultat = $this->validarCCC($ccc);
        echo !$resultat ? "✅ Correcte! CCC massa curt detectat com a invàlid\n" : "❌ Error!\n";
        $this->assertFalse($resultat, "ERROR: CCC massa curt detectat com a vàlid");

        echo "--- FI TEST VALIDACIÓ CCC ---\n";
    }

    public function test_valid_iban()
    {
        echo "\n--- TEST VALIDACIÓ IBAN ---\n";

        // Comprovem un IBAN correcte
        $iban = 'ES7620770024003102575766';
        echo "Provant IBAN correcte: $iban...\n";
        $resultat = $this->validarIBAN($iban);
        echo $resultat ? "✅ Correcte!\n" : "❌ Error!\n";
        $this->assertTrue($resultat, "ERROR: IBAN correcte detectat com a incorrecte");

        // IBAN incorrecte (dígits de control erronis)
        $iban = 'ES0020770024003102575766';
        echo "Provant IBAN incorrecte: $iban...\n";
        $resultat = $this->validarIBAN($iban);
        echo !$resultat ? "✅ Correcte! IBAN detectat com a invàlid\n" : "❌ Error!\n";
        $this->assertFalse($resultat, "ERROR: IBAN incorrecte detectat com a vàlid");

        echo "--- FI TEST VALIDACIÓ IBAN ---\n";
    }

    public function test_forca_bruta_recuperar_ccc()
    {
        echo "\n--- TEST FORÇA BRUTA CCC ---\n";

        $cccIncomplet = '207700240031025757**';
        $solucioTrobada = false;

        for ($i = 0; $i < 100; $i++) {
            $cccIntent = str_replace('**', str_pad($i, 2, '0', STR_PAD_LEFT), $cccIncomplet);

            if ($this->validarCCC($cccIntent)) {
                echo "✅ Possible CCC original trobat: $cccIntent\n";
                $solucioTrobada = true;
                break;
            }
        }

        if (!$solucioTrobada) {
            echo "❌ No s'ha trobat cap CCC vàlid amb els dos últims dígits ocults.\n";
        }

        $this->assertTrue($solucioTrobada, "No s'ha pogut recuperar el CCC original.");
        echo "--- FI TEST FORÇA BRUTA CCC ---\n";
    }

    private function validarCCC($ccc)
    {
        $ccc = str_replace(' ', '', $ccc);
        if (strlen($ccc) !== 20 || !ctype_digit($ccc)) return false;

        $entitat = substr($ccc, 0, 4);
        $oficina = substr($ccc, 4, 4);
        $digitsControl = substr($ccc, 8, 2);
        $compte = substr($ccc, 10, 10);

        return $digitsControl === $this->calcularDC($entitat, $oficina, $compte);
    }

    private function calcularDC($entitat, $oficina, $compte)
    {
        $pesos = [1, 2, 4, 8, 5, 10, 9, 7, 3, 6];

        $num1 = '00' . $entitat . $oficina;
        $suma = 0;
        for ($i = 0; $i < 10; $i++) {
            $suma += intval($num1[$i]) * $pesos[$i];
        }
        $dc1 = 11 - ($suma % 11);
        if ($dc1 == 10) $dc1 = 1;
        if ($dc1 == 11) $dc1 = 0;

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
        $iban = str_replace([' ', '-'], '', $iban);
        if (strlen($iban) < 15 || strlen($iban) > 34) return false;

        $iban = substr($iban, 4) . substr($iban, 0, 4);
        $ibanNumeric = '';
        foreach (str_split($iban) as $char) {
            $ibanNumeric .= ctype_alpha($char) ? ord($char) - 55 : $char;
        }
        return bcmod($ibanNumeric, '97') == '1';
    }
}
