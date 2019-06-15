<?php
namespace santilin\Churros\components;

/**
* @package churros
* @version 1.0
* @copyright 2017-2019 Santilín
* @author Santilín
*/

define('NIF_DNI', '1');
define('NIF_NIE', '2');
define('NIF_ENTIDADES_NUMERO', '3');
define('NIF_ENTIDADES_LETRA', '4');

class Nif
{
    public $nif;
    public $tipo;

    //Si se migra a PHP 5.6 se puede definir como array
    const REGEX_NIF_DNI = '((\d{8})([A-Z]{1}))';
    const REGEX_NIF_NIE = '(([X-Z]{1})(\d{7})([A-Z]{1}))';
    const REGEX_NIF_ENTIDADES_NUMERO = '(([ABCDEFGHJUV])(\d{7})([0-9]{1}))';
    const REGEX_NIF_ENTIDADES_LETRA = '(([NPQRSW])(\d{7})([A-Z]{1}))';

    public function __construct($nif, $tipo = null)
    {
        $this->nif = $nif;
        $this->tipo = $tipo;
    }

    /**
     * Devuelve la letra del DNI de un código de control.
     *
     * @param type $codigo
     *
     * @return bool|string
     */
    protected static function getDniLetterFromCode($codigo)
    {
        $letras = [
            'T', 'R', 'W', 'A', 'G', 'M', 'Y', 'F', 'P', 'D', 'X', 'B',
            'N', 'J', 'Z', 'S', 'Q', 'V', 'H', 'L', 'C', 'K', 'E',
        ];

        if ($codigo >= count($letras)) {
            return false;
        }

        return $letras[$codigo];
    }

    /**
     * Devuelve la letra del NIF de una entidad de un código de control.
     *
     * @param type $codigo
     *
     * @return bool|string Devuelve la letra de control del código o falso si el código no es correcto.
     */
    protected static function getEntityLetterFromCode($codigo)
    {
        // Al estar los índices de los arrays en base cero, el primer
        // elemento del array se corresponderá con la unidad del número
        // 10, es decir, el número cero.
        $letras = ['J', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        if ($codigo >= count($letras)) {
            return false;
        }

        return $letras[$codigo];
    }

    /**
     * Devuelve la longitud que tiene que tener un NIF.
     *
     * @return int Longitud de caracteres de un NIF
     */
    public static function stdLength()
    {
        // Tras la unificacion todos los NIF tanto de persona fisica como juridica son de 9 caracteres
        return 9;
    }

    /**
     * Elimina todos los caracteres no válidos.
     *
     * @return String Devuelve el nif solo con caracteres válidos.
     */
    public function clean()
    {
        $this->nif = self::sanitize($this->nif);

        return $this->nif;
    }

    /**
     * Elimina de caracteres no válidos la cadena que representa el NIF.
     *
     * @param String $nif Cadena que representa al NIF
     * @return String Devuelve un nif solo con letras y digitos
     */
    public static function sanitize($nif)
    {
        # Cambia a mayusculas y elimina los espacios del inicio
        $nif = ltrim(strtoupper($nif));
        # Elimina todos los caracteres que no sean numeros o letras
        return preg_replace('/[^a-zA-Z0-9]/', '', $nif);
    }

    /**
     * Devuelve el caracter de control del NIF
     *
     * @return String Caracter de control del NIF
     */
    protected function getControlChar()
    {
        return $this->nif[self::stdLength() - 1];
    }

    /**
     * Comprueba el tipo de NIF del objeto y lo almacena en la propiedad tipo.
     *
     * @return bool Devuelve verdadero si es un tipo conocido o falso si no.
     */
    protected function checkType()
    {
        $tipos = [
            ['tipo' => NIF_DNI, 'patron' => self::REGEX_NIF_DNI],
            ['tipo' => NIF_NIE, 'patron' => self::REGEX_NIF_NIE],
            ['tipo' => NIF_ENTIDADES_NUMERO, 'patron' => self::REGEX_NIF_ENTIDADES_NUMERO],
            ['tipo' => NIF_ENTIDADES_LETRA, 'patron' => self::REGEX_NIF_ENTIDADES_LETRA],
        ];

        foreach ($tipos as $tipo) {
            if (preg_match($tipo['patron'], $this->nif)) {
                $this->tipo = $tipo['tipo'];
                break;
            }
        }

        return $this->tipo;
    }

    /**
     * Transforma un NIE en DNI para poder normalizar su comprobación.
     *
     * @param type $nif
     *
     * @return type
     */
    protected function normalizeToDni()
    {
        $replace = [
            'X' => 0,
            'Y' => 1,
            'Z' => 2,
        ];

        return strtr($this->nif, $replace);
    }

    /**
     *  Comprueba si un NIF es correcto.
     *
     * @param string $this->nif
     * @param bool   $machine_format_only
     *
     * @return bool
     */
    public function verify()
    {

        // Limpiamos la cadena de caracteres no válidos
        $this->nif = $this->clean();

        // Comprobamos si tiene la longitud correcta
        if (strlen($this->nif) != self::stdLength()) {
            return false;
        }

        // Comprobamos el tipo de NIF
        if ($this->checkType() !== false) {
            if (!$this->verifyChecksum($this->tipo)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Comprueba si el NIF es correcto según su tipo.
     *
     * @param type $nif
     * @param type $type
     *
     * @return type
     */
    protected function verifyChecksum($type)
    {
        switch ($type) {
            case NIF_NIE:
                $tmp = new self($this->normalizeToDni(), $this->nif);
                return $tmp->verifyDniChecksum();
            case NIF_DNI:
                return $this->verifyDniChecksum();
            case NIF_ENTIDADES_NUMERO:
            case NIF_ENTIDADES_LETRA:
                return $this->verifyEntityChecksum();
        }

        return false;
    }

    /**
     * Comprueba si un NIF de tipo DNI es correcto.
     *
     * @return bool
     */
    protected function verifyDniChecksum()
    {
        // Todos los numeros menos la letra
        $nif_numero = substr($this->nif, 0, -1);
        $nif_numero = intval($nif_numero);

        $codigo_control = $nif_numero % 23;

        if ($this->getControlChar() == self::getDniLetterFromCode($codigo_control)) {
            return true;
        }

        return false;
    }

    /**
     * Comprueba si un NIF de tipo entidad es correcto.
     *
     * @staticvar string $characters
     *
     * @return bool
     */
    protected function verifyEntityChecksum()
    {
        $suma_pares = 0;
        $suma_impares = 0;
        // A continuación, la cadena debe tener 7 dígitos + el dígito de control.
        $digits = substr($this->nif, 1, 7);
        $digits_lenght = strlen($digits);

        // Como la longitud es fija se extrae la letra directamente
        $nif_letra = $this->nif[self::stdLength() - 1];

        for ($n = 0; $n <= $digits_lenght - 1; $n += 2) {
            if ($n < 6) {
                // Sumo las cifras pares del número que se corresponderá
                // con los caracteres 1, 3 y 5 de la variable $digits
                $suma_pares += intval($digits[$n + 1]);
            }
            // Multiplico por dos cada cifra impar (caracteres 0, 2, 4 y 6).
            $dobleImpar = 2 * intval($digits[$n]);
            // Acumulo la suma del doble de números impares.
            $suma_impares += ($dobleImpar % 10) + (integer) ($dobleImpar / 10);
        }

        // Sumo las cifras pares e impares.
        $suma_total = $suma_pares + $suma_impares;

        // Me quedo con la cifra de las unidades y se la resto a 10, siempre
        // y cuando la cifra de las unidades sea distinta de cero
        $suma_total = (10 - ($suma_total % 10)) % 10;
        // Devuelvo el Dígito de Control dependiendo del primer carácter
        // del NIF pasado a la función.
        $control_char = '';

        switch ($this->tipo) {
            case NIF_ENTIDADES_LETRA:
                $control_char = self::getEntityLetterFromCode($suma_total);
                break;
            case NIF_ENTIDADES_NUMERO:
                $control_char = chr(ord($suma_total));
                break;
            default:
                return false;
        }

        if ($this->getControlChar() == $control_char) {
            return true;
        }

        return false;
    }
}

