<?php
/**
 * Message translations.
 *
 * This file is automatically generated by 'yii message/extract' command.
 * It contains the localizable messages extracted from source code.
 * You may modify this file by translating the extracted messages.
 *
 * Each array element represents the translation (value) of a message (key).
 * If the value is empty, the message is considered as not translated.
 * Messages that no longer need translation will have their translations
 * enclosed between a pair of '@@' marks.
 *
 * Message string can be used with plural forms format. Check i18n section
 * of the guide for details.
 *
 * NOTE: this file must be saved in UTF-8 encoding.
 */
return [
	'View all files' => 'Ver todos los ficheros',
	'Edit all files' => 'Editar todos los ficheros',
	'{model} editor' => 'Editor de {model}',
	'{model} viewer' => 'Visor de {model}',
	'Their own {model} editor' => 'Editor de sus {model}',
	'Their own {model} viewer' => 'Visor de sus {model}',
	'permission created' => 'permiso creado',
	'permission updated' => 'permiso actualizado',
	'role created' => 'rol creado',
	'role updated' => 'rol actualizado',
	'Create' => 'Crear',
	'View' => 'Ver',
	'Edit' => 'Editar',
	'Delete' => 'Borrar',
	'List' => 'Listar',
	'Duplicate' => 'Duplicar',
	'Accesd denied to {esta} {title} because you are not the author' => 'Acceso denegado a {esta} {title} porque no eres el autor/a',
	'Access to all models of module {module}' => 'Acceso a todos los modelos del módulo {module}',
	'Access to {model_title} menu for {module_name} module' => 'Acceso al menú de {model_title} del módulo {module_name}',
	"Access to '{module}' module menu" => 'Acceso al menu del módulo {module}',
	"Access to '{module}' module site" => 'Acceso al sitio del módulo {module}',
	'their own {title_plural}' => 'sus propi{as} {title_plural}',

	'All' => 'Todo',
    'Show all' => 'Mostrar todo',
    'Paginate' => 'Paginar',
    'Summarize' => 'Sólo totales',
    'No {items} found.' => 'No se han encontrado {items}.',
    'Show only summary' => 'Mostrar solo los totales',
    'Show first page data' => 'Paginar los resultados',
    'Show all data' => 'Mostrar todos los resultados',
    'Error saving the record {title}' => 'Error guardando el registro {title}',
    'From \'{from}\' or until \'{until}\' dates are in invalid date format' => 'Las fechas desde: \'{from}\' o hasta: \'{until}\' no tienen un formato válido',

    'Active' => 'Activo',
    'Inactive' => 'Inactivo',
    '{inactive} since' => '{inactive} desde',
	"{La} {title} <a href=\"{model_link}\">{record_medium}</a> has been successfully created."
		=> "{La} {title} <a href=\"{model_link}\">{record_medium}</a> se ha creado correctamente.",
    '{La} {title} <a href="{model_link}">{record_medium}</a> has been successfully updated.'
		=> '{La} {title} <a href="{model_link}">{record_medium}</a> se ha actualizado correctamente.',
    '{La} {title} <strong>{record_long}</strong> has been successfully deleted.'
		=> '{La} {title} <strong>{record_long}</strong> se ha borrado correctamente.',
    '{La} {title} <a href="{model_link}">{record_medium}</a> has been successfully duplicated.'
		=> '{La} {title} <a href="{model_link}">{record_medium}</a> se ha duplicado correctamente.',
	'The action on {la} {title} <a href="{model_link}">{record_medium}</a> has been successful.' => 'La acción sobre {la} {title} <a href="{model_link}">{record_medium}</a> se ha realizado correctamente.',

	'Duplicating {title}: {record_short}' => 'Duplicando {title}: {record_short}',
	'Updating {title}: {record_short}' => 'Editando {title}: {record_short}',
	'Creating {title}' => 'Creando {title}',
    '{from-label} {from} can\'t be greater than {until-label} {until}' => '{from-label}: \'{from}\' no puede ser posterior a {until-label}: \'{until}\'',
	"The master record of {title} with '{id}' id does not exist" => "El registro maestro {title} de id '{id}' no existe",
    'You can\'t delete {la} {title} {record_medium} because you are not the author' => 'No puedes borrar {esta} {title} porque no l{a} has creado tú',
    "You can\'t print to pdf {la} {title} {record_medium}  because you are not the author" => 'No puedes imprimir a pdf {esta} {title} porque no l{a} has creado tú',
    'You can\'t update {la} {title} {record_medium}  because you are not the author' => 'No puedes modificar {esta} {title} porque no l{a} has creado tú',
    'You can\'t view {la} {title} {record_medium}  because you are not the author' => 'No puedes ver {esta} {title} porque no l{a} has creado tú',
    'Error deleting {la} {title} {record_medium}' => 'Error borrando {la} {title} {record_medium}',
    "{La} {title} <strong>{record_long}</strong> can't be deleted because it has related data"
		=> "No se puede borrar {la} {title} <strong>{record_long}</strong>  porque está en uso en otros ficheros",
	'{Esta} {title} is used in other files' => '{Esta} {title} se usa en algún otro fichero',
	'Create {title}' => 'Crear {title}',
	"The value '{value}' is not valid for {attribute}" => "El valor '{value}' no es válido para el campo {attribute}",

	'Report totals' => 'Totales del informe',
    'Totals' => 'Totales',
	'{search_model_name}: model not found in report "{record}"'
		=> '{search_model_name}: modelo no encontrado en el informe "{record}"',
	'The report "{record}" has been successfully saved'
		=> 'El informe "{record}" se ha guardado correctamente.',
	'The report "{record}" has definition errors'
		=> 'El informe "{record}" tiene errores en su definición.',
	'Not showing totals because not all the rows have been shown' =>
		'No se muestran los totales porque no se están mostrando todos los registros',

	'Unable to send email to {email}' => 'No se ha podido enviar el email a {email}',
	'Unable to send email to {email} and other {ndest} recipients' => 'No se ha podido enviar el email a {email} y a otros {ndest} destinatarios',

	'{model.title}: you have not access to this report' => '{model.title}: no tienes acceso a este informe',
    'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{{item}} other{{items}}}.' => 'Mostrando <b>{begin, number}-{end, number}</b> de <b>{totalCount, number}</b> {totalCount, plural, one{{item}} other{{items}}}.',
	'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b>' => 'Mostrando <b>{begin, number}-{end, number}</b> de <b>{totalCount, number}</b>',
	'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{item} other{items}}.' => 'Mostrando <b>{begin, number}-{end, number}</b> de <b>{totalCount, number}</b> {totalCount, plural, one{item} other{items}}.',
	'Total <b>{count, number}</b> {count, plural, one{item} other{items}}.' => 'Total <b>{count, number}</b> {count, plural, one{item} other{items}}.',

];
