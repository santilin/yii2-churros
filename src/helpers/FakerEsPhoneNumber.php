<?php namespace santilin\churros\helpers;

class FakerEsPhoneNumber extends \Faker\Provider\es_ES\PhoneNumber
{
	// Redefinido para que no haya formatos más largos de 12 caracteres
    protected static $formats = array(
        '+349########',
        '9########',
        '+346########',
        '6########',
    );
}

