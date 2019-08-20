<?php namespace santilin\churros\fakers;

class Address extends \Faker\Provider\es_ES\Address
{
    protected static $state = [
        'La Coruña', 'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona', 'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Cuenca', 'Córdoba', 'Gerona', 'Granada', 'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares', 'Jaén', 'La Rioja', 'Las Palmas', 'León', 'Lérida', 'Lugo', 'Málaga', 'Madrid', 'Melilla', 'Murcia', 'Navarra', 'Orense', 'Palencia', 'Pontevedra', 'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Santa Cruz de Tenerife', 'Tarragona', 'Teruel', 'Toledo', 'Valencia', 'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza'];


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

	public function iban()
    {
        return $this->generator->bankAccountNumber();
    }

}

