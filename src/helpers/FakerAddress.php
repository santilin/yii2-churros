<?php namespace santilin\Churros\helpers;

class FakerAddress extends \Faker\Provider\es_ES\Address
{
    protected static $state = [
        'La Coruña', 'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona', 'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Cuenca', 'Córdoba', 'Gerona', 'Granada', 'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares', 'Jaén', 'La Rioja', 'Las Palmas', 'León', 'Lérida', 'Lugo', 'Málaga', 'Madrid', 'Melilla', 'Murcia', 'Navarra', 'Orense', 'Palencia', 'Pontevedra', 'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Santa Cruz de Tenerife', 'Tarragona', 'Teruel', 'Toledo', 'Valencia', 'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza'];

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

    public function integer($digits = 16)
    {
		if( $digits == 1 ) {
			return $this->randomDigitNotNull();
		} else {
			return $this->decimal($digits);
		}
    }

    public function integer_unsigned($digits = 16)
    {
		if( $digits == 1 ) {
			return $this->randomDigitNotNull();
		} else {
			return $this->decimal_unsigned($digits);
		}
    }

	public function integer_small()
    {
		return $this->decimal(4);
    }

	public function integer_small_unsigned()
    {
		return $this->decimal_unsigned(4);
    }

	public function decimal($digits = 16, $decimals = 0)
    {
		assert($decimals < $digits);
		$ret = $this->generator->randomElement(["-",""]);
		if ($ret=="-") {
			$digits -= 1;
		}
		for( $i=1; $i<$digits; ++$i) {
			if( $i == $digits - $decimals && $decimals > 0 ) {
				$ret .= ".";
			}
			$ret .= $this->generator->randomDigit();
		}
		return $ret;
    }

	public function decimal_unsigned($digits = 16, $decimals = 0)
    {
		assert($decimals < $digits);
		$ret = "";
		for( $i=1; $i<$digits; ++$i) {
			if( $i == $digits - $decimals && $decimals > 0 ) {
				$ret .= ".";
			}
			$ret .= $this->generator->randomDigit();
		}
		return $ret;
    }

	/**
	 * string of numbers with exactly $digits chars
	 */
	public function integer_string($digits = 16, $decimals = 0)
    {
		assert($decimals < $digits);
		$ret = "";
		if ($digits>0) {
			$ret .= $this->generator->randomDigitNotNull();
			for( $i=1; $i<$digits-$decimals-2; ++$i) {
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

	public function str_random($digits)
	{
        return \str_random($digits);
    }

    public function null()
    {
		return null;
	}

	public function image_binary()
	{
		return "Image";
	}
}

