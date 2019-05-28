<?php namespace santilin\Churros\helpers;

class FakerAddress extends \Faker\Provider\es_ES\Address
{
    protected static $state = [
        'La Coruña', 'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona', 'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Cuenca', 'Córdoba', 'Gerona', 'Granada', 'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares', 'Jaén', 'La Rioja', 'Las Palmas', 'León', 'Lérida', 'Lugo', 'Málaga', 'Madrid', 'Melilla', 'Murcia', 'Navarra', 'Orense', 'Palencia', 'Pontevedra', 'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Santa Cruz de Tenerife', 'Tarragona', 'Teruel', 'Toledo', 'Valencia', 'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza'];

	public function description()
	{
		return $this->generator->text();
	}

	public function es_nombrepropio()
	{
		return $this->generator->name();
	}

    public function es_codigo_postal()
    {
        return $this->generator->postcode();
    }

    public function es_direccion()
    {
        return $this->generator->streetAddress();
    }

    public function es_poblacion()
    {
        return $this->generator->city();
    }

	public function es_provincia()
    {
        return $this->generator->state();
    }

    public function es_cif_nif()
    {
        return $this->generator->dni();
    }

	public function iban()
    {
        return $this->generator->bankAccountNumber();
    }

    public function shortString($nchars)
    {
		return substr($this->generator->text(20),0,$nchars);
    }

    public function integer_big($digits = 16)
    {
		$ret = $this->generator->randomElement(["-",""]);
		if ($digits>0) {
			$ret .= $this->generator->randomDigitNotNull();
			for( $i=1; $i<$digits; ++$i) {
				$ret .= $this->generator->randomDigit();
			}
		}
		return $ret;
    }

	public function decimal($digits = 16, $decimals = 0)
    {
		$ret = $this->generator->randomElement(["-",""]);
		if ($ret=="-") {
            $digits -= 1;
        }
		return $ret . $this->integer_string($digits, $decimals);
    }

	public function integer_string($digits = 16, $decimals = 0)
    {
		$ret = "";
		if ($digits>0) {
			$ret .= $this->generator->randomDigitNotNull();
			for( $i=1; $i<$digits-2; ++$i) {
				$ret .= $this->generator->randomDigit();
			}
		}
		if( $decimals > 0 ) {
			$ret .= ".";
			for( $i=0; $i<$decimals; ++$i) {
				$ret .= $this->generator->randomDigit();
			}
		}
		return $ret;
    }

    public function bool()
    {
		return $this->generator->boolean();
	}

	public function tasaciones_clave()
	{
		return $this->generator->randomDigit()
			. $this->generator->randomLetter()
			. $this->generator->randomDigitNotNull();
	}

	public function euros()
	{
		return $this->generator->randomFloat(2);
	}

	public function float($decimals = 2)
	{
		return $this->generator->randomFloat($decimals);
	}

	public function str_random($digits)
	{
        return \str_random($digits);
    }

}

