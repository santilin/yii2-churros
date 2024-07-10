# Login yii2-usuario
- Contraseña (recordar) no está bien el orden.
- forgot: forbiden si estoy logueado como admin

# Telegram
- En modo desarrollo, enviar las notificaciones a otro grupo

# Sqlite3
- Crear issue en github PRAGMA

# ActionColumn
- Usar propiedad visible de los botones

# CrudController
* CrudController::pdf: Incluir css del módulo/modelo actual
- Duplicar: imágenes, aparece el check para borrar la imagen.
- update: Ya no aparece el check para elimiinar la imagen
- Behaviors: access-control tiene que ejecutarse antes que verb, para que no dé error si no tiene acceso.
- gridPerms: No pasarlo como parámetro independiente, porque debe poderse cambiarn en changeParams.

# Reports:
* Quitar (no definido) al imprimir
- AllReportFields: ver si ponerlo en otro lugar.
- fixColumnDefinitions: Añadir adhoc los campos relacionados.
- gui: ¿Añadir relación?
* Hacer uso de los model.Agente.report? o los permisos del Param?

# User
- ¿Opción para obligar a cambiar la contraseña?
- cache de permisos
- yii2usuario: alert falla cuando se redirige de bs4 a bs3 porque no espera un array aquí: /home/santilin/devel/-yii2base/vendor/2amigos/yii2-usuario/src/User/resources/views/shared/_alert.php
- Hay acceso a trivel.test/user/2
- Añadir opción visible en profile en resources ... _menu.php: yiiparam('user_profile');
- Si no hay rbac, que no aparezcan los menús relacionados con roles, etc.

# Adjuntos y subir ficheros
* Opciones
	- tabla de adjuntos.
	- behaviour
	- behaviour con múltiple

## UploadBehavior
* Fileuploadbehavior saveorigfname true
- cuando da error otro campo que no es el file, se pierde el nombre del fichero original

# SiteController
- Cuando va a site/error, botones o menú para volver a los módulos a los que tenga acceso la usuaria.
- Errores en formulario de Ajax. Que se quede en el formulario ajax y muestre el error.

# Widgets
* Anidar RercordViews con un static
* css: width = maxlength en integers, floats y dates
* Max-length: Tomar lo que se ha definido en la base de datos o capturar la excepción.
* Radio List: Ver cómo añadir el autofocus a cada item.
* Añadir layout table recordview
* Añadir showmore a form layout

## Inactivatable date
- Repensar el ActivatableInput
* Etiquetas según género del campo

## SearchDropDown
* Si un searchdropdown está disabled o readonly no mostrar el campo de búsqueda
* ¿Si hay dos coincidencias qué hacer?

## TableListView
* Quitar márgenes a pager.
* Centrar verticalmente summary.
- Añadir el símbolo del euro al input de los forms: bs4 input-group
- función extractHeaders
- Paginador no sale bien el layout/css. Añadir el pager como en simplelistview

## GridView
- layout admin, más compacto.
* GridView: Report : grupos: opciones para Mostrar encabezado, mostrar pie.
* Grid Enlaces en el joinmodels: filtro en el grid por ese valor?
* selectViews no funciona con pjax porque es un dropdown. Añadirlo dentro de un FORM
* Grid: cuando se filtra, poner "Filtrando 1-1 de s"
* BreadCrumbs: Index: Añadir el orden o el filtro ("filtrado") ("ordenado por")
* 2 gridviews: https://www.yiiframework.com/doc/guide/2.0/en/output-data-widgets#multiple-gridviews


# YII2
## Bugs

=== model.php:372
        return !$this->hasErrors();
    }

==== bs4/ActiveForm
getAttributeLabels called twice: breakpoint en
/home/santilin/devel/yii2base/vendor/yiisoft/yii2-bootstrap4/src/ActiveField.php:553

### Codeception
Test: CrudOfertaCest. Cuando redirige de update a view, no se borra la aplicación y el componente 'view' se queda con _isPageEnded se queda a true

/home/santilin/devel/yii2base/vendor/yiisoft/yii2/base/Controller.php
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = Yii::$app->getView();
        }

/home/santilin/devel/yii2base/vendor/yiisoft/yii2/base/Application.php
    public function getView()
    {
        return $this->get('view');
    }

/home/santilin/devel/yii2base/vendor/yiisoft/yii2/base/Module.php
	public function get($id, $throwException = true)
    {
        if (!isset($this->module)) {
            return parent::get($id, $throwException);
        }

/home/santilin/devel/yii2base/vendor/yiisoft/yii2/di/ServiceLocator.php
    public function get($id, $throwException = true)
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }

/home/santilin/devel/yii2base/vendor/yiisoft/yii2/web/View.php
    private function registerFile($type, $url, $options = [], $key = null)


## Reports
* Crear Reports.view, etc. (biosegura)
* Añadir más funciones sobre los campos: año, mes, día, cuenta, media, máximo, mínimo, etc.

## Configuración
- Configuración tema: Fijo, por usuario
- Configuración fecha: Fija, por usuario
- Configuración idioma: Fijo, por usuario


# Third-party
- Creación dinámica de etiquetas:
    - https://stackoverflow.com/questions/32731987/adding-new-list-options-refreshing-updated-list-dynamically
* Full-text-search: https://www.sqlite.org/fts3.html#section_8_2
* https://github.com/manuvarkey/kanboard-plugin-telegram
* html-to-rtf y .editorconfig https://github.com/github-grabaz/html-rtf-converter/blob/master/.editorconfig
* https://github.com/pavlm/yii2-stats-widget Time Stats widget
* Modelos: Estudiar softdeletes y lock. Faltan los behaviors
* Formulario de settings: file:///home/santilin/devel/yii2base/yii2-docs/guide-input-tabular-input.html
* https://phpstan.org/user-guide/getting-started
* Investigar estos widgets:
- https://www.yiiframework.com/extension/yii2-sortable-behavior
- https://www.yiiframework.com/extension/yii2-poll-widget
* Optimizacion sqlite3
https://stackoverflow.com/questions/1711631/improve-insert-per-second-performance-of-sqlite
* https://craftcms.com/blog/craft-5-beta-released?s=35
