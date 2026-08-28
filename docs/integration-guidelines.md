# Guía para crear integraciones de FormsCRM con plugins de formularios

Este documento define **cómo debe construirse una nueva integración de formulario**
(Gravity Forms, WPForms, Ninja Forms, Elementor, CF7, JetFormBuilder, etc.) para que
sea coherente con el resto del plugin. Se basa en los patrones ya usados en
`includes/formscrm-library/` y en el análisis de un intento de integración de
**Ninja Forms** (issue [#122](https://github.com/closemarketing/formscrm/issues/122),
PR [#171](https://github.com/closemarketing/formscrm/pull/171)) que se fusionó y
posteriormente se revirtió (PR [#173](https://github.com/closemarketing/formscrm/pull/173)).

## Los dos principios rectores

1. **Lo más adaptada posible al formulario y a su interfaz.** El usuario no debe
   notar que FormsCRM es un plugin externo: la configuración tiene que vivir dentro
   de las pantallas propias del plugin de formularios, con sus propios componentes
   visuales.
2. **Integrarse al máximo con las funciones que ya ofrece el plugin de formularios.**
   Si el plugin ya resuelve un problema (mapeo de campos, merge tags, validación,
   reintentos, registro de envíos...), FormsCRM debe apoyarse en esa solución en
   lugar de reinventarla.

## 1. Usa el mecanismo de extensión nativo del plugin, no un hook genérico

Cada integración existente se registra a través del **sistema de extensión propio
de su plugin**, nunca mediante un hook genérico common a todos:

| Plugin | Mecanismo nativo usado |
|---|---|
| Gravity Forms | `GFAddOn` / Feed Add-On Framework (`class-gravityforms.php`, cargado en `gform_loaded`) |
| WPForms | `WPForms_Provider` — el mismo sistema de "Marketing Providers" que usa Mailchimp/Constant Contact (`class-wpforms.php`, cargado en `wpforms_loaded`) |
| Elementor Pro | `\ElementorPro\Modules\Forms\Classes\Action_Base`, registrado en `elementor_pro/init` |
| JetFormBuilder | `Jet_Form_Builder\Actions\Types\Base`, registrado vía `jet-form-builder/actions/register` |
| Contact Form 7 | Hooks propios de CF7: `wpcf7_editor_panels`, `wpcf7_before_send_mail` |

Al plantear una integración nueva, la primera pregunta es: **¿qué extension point
ofrece este plugin para "proveedores" o "acciones tras el envío"?** Si el plugin
tiene un concepto de "Action"/"Provider"/"Add-on" (como Ninja Forms con
`NF_Abstracts_Action`), FormsCRM debe registrarse ahí — no enganchar directamente
a un hook de "formulario enviado" genérico y reimplementar por nuestra cuenta el
listado de acciones, el guardado de ajustes, etc.

## 2. La configuración vive dentro de las pantallas del propio plugin

No se crea una pantalla de ajustes aparte para cada integración. La UI de conexión
al CRM se inserta:

- como una nueva pestaña/metabox en el editor del formulario (CF7: `wpcf7_editor_panels`),
- como una nueva "Acción" dentro del listado de acciones del constructor (JetFormBuilder, Ninja Forms),
- o como un "Provider" más en la pantalla de conexiones ya existente (WPForms).

El usuario configura FormsCRM exactamente donde configura cualquier otra
integración de ese plugin (Mailchimp, Zapier, etc.), con el mismo look & feel.

## 3. El mapeo de campos debe usar los selectores del propio formulario

Esto es lo más importante y donde más se suele fallar:

- **WPForms**: el mapeo se hace campo a campo con un `<select>` nativo de WPForms
  que lista los campos reales del formulario (`output_fields()`), igual que para
  cualquier otro proveedor de WPForms.
- **JetFormBuilder**: el mapeo se define en el propio editor visual de JFB
  (`fields_map`), usando los componentes de JS del constructor.
- **Contact Form 7**: usa los propios *mail-tags*/shortcodes de CF7 (`[nombre-campo]`)
  ya existentes en el formulario.

❌ **Anti-patrón (detectado en el intento de Ninja Forms, PR #171):** un
`<textarea>` libre donde el usuario escribe a mano líneas
`crm_field = {field:clave}`. Aunque Ninja Forms soporta *merge tags*, y el
`textarea` los admitía (`use_merge_tags => true`), obligar a escribir el nombre
exacto del campo de memoria en vez de ofrecer un selector con los campos reales
del formulario es un paso atrás respecto a como funcionan el resto de
integraciones y es propenso a errores de tecleo.

✅ Alternativa correcta para Ninja Forms: usar el propio *field mapping* de
Ninja Forms (los campos ya se pueden insertar como merge tag desde un botón/menú
del propio NF sobre un `textbox`, no en texto libre sin ayuda visual), o mejor
aún, construir la lista de campos del formulario (`Ninja_Forms()->form()->get(...)->get_fields()`)
y ofrecer un `select` por cada campo CRM, tal como hace WPForms.

## 4. Solo muestra los campos de conexión que aplican al CRM elegido

Cada CRM necesita credenciales distintas (URL, usuario/contraseña, API key,
Odoo DB...). Todas las integraciones existentes ocultan/muestran estos campos
según el CRM seleccionado, usando los helpers de `helpers-library-crm.php`:

- `formscrm_get_dependency_url()`
- `formscrm_get_dependency_username()`
- `formscrm_get_dependency_password()`
- `formscrm_get_dependency_apipassword()`
- `formscrm_get_dependency_apisales()`
- `formscrm_get_dependency_odoodb()`

CF7 los aplica en PHP (renderiza solo el campo que corresponde), WPForms/JetFormBuilder
los aplican en JS al cambiar el `select` de CRM. **Nunca renderices todos los
campos de conexión a la vez de forma estática** (URL, usuario, contraseña, API
password, API sales, Odoo DB, modo experto...) — es el error concreto del PR de
Ninja Forms revertido: mostraba las 9 opciones siempre visibles en el grupo
"advanced", sin depender del CRM elegido, saturando la pantalla de ajustes.

## 5. Reutiliza siempre las mismas piezas compartidas

No dupliques lógica ya resuelta en `helpers-functions.php` / `helpers-library-crm.php`:

- `formscrm_get_choices()` para el desplegable de CRMs.
- `formscrm_get_api_class( $crm_type )` para cargar la clase `CRMLIB_*` correspondiente.
- Las claves de ajustes estándar: `fc_crm_type`, `fc_crm_module`, `fc_crm_url`,
  `fc_crm_username`, `fc_crm_password`, `fc_crm_apipassword`, `fc_crm_apisales`,
  `fc_crm_odoodb`. Cualquier integración nueva debe producir un array de
  `$settings` con estas mismas claves para que funcione sin cambios con
  cualquier CRM ya soportado (built-in o externo vía filtros).
- `formscrm_check_url_crm()` para normalizar la URL del CRM antes de guardarla.

## 6. Nunca bloquees el envío del formulario ni pierdas el error

Principio de "Máxima Fiabilidad" (ver `AGENTS.md`): un fallo del CRM nunca debe
impedir que el formulario se envíe ni mostrar un error al usuario final. Además,
el error debe quedar **visible y accionable** para el administrador:

- Envuelve la llamada al CRM en `try { } catch ( Exception $e ) { }`.
- En caso de error, llama a `formscrm_alert_error( $crm_type, $message, $merge_vars, $url, $json, $form_info )`
  (o su alias `formscrm_debug_email_lead()`), pasando siempre `$form_info` con
  `form_type`, `form_type_title`, `form_id`, `form_name` y, si existe, `entry_id`.
  Esto es lo que alimenta la tabla de Error Log (`class-error-log.php`) y permite
  reenviar el lead manualmente. El intento de Ninja Forms llamaba a
  `formscrm_debug_email_lead()` sin `$form_info`, por lo que las entradas en el
  log quedaban sin poder identificar de qué formulario/envío de NF procedían.
- En caso de éxito, no interrumpas el flujo normal del plugin de formularios:
  simplemente registra el resultado (nota interna, log de depuración, etc.)
  usando el propio sistema de logging del plugin anfitrión si existe
  (`entry_meta` en WPForms, notas de NF, etc.).

## 7. Carga condicional y sin coste si el plugin no está activo

Sigue el patrón de `loader.php`:

```php
if ( is_plugin_active( 'ninja-forms/ninja-forms.php' ) && ! class_exists( 'FormsCRM_NinjaForms_Action' ) ) {
    add_action( 'plugins_loaded', function () {
        if ( class_exists( 'NF_Abstracts_Action' ) ) {
            require_once 'class-ninjaforms.php';
        }
    }, 20 );
}
```

- Comprueba `is_plugin_active()` antes de nada.
- Engánchate al hook de arranque propio del plugin (`gform_loaded`,
  `wpforms_loaded`, `elementor_pro/init`, `jet-form-builder/actions/register`,
  o `plugins_loaded` con prioridad suficiente) en vez de cargar la clase
  directamente en `loader.php`.
- No leas superglobales (`$_POST`, `$_REQUEST`) directamente salvo que el propio
  plugin anfitrión no te entregue ese dato en el array de la petición; si lo
  haces, sanitiza (`sanitize_text_field( wp_unslash( ... ) )`) y documenta por
  qué es necesario.

## 8. Checklist antes de abrir un PR de una integración nueva

- [ ] ¿Usa el extension point nativo del plugin (Action/Provider/Add-on), no un
      hook genérico "on submit"?
- [ ] ¿La configuración aparece dentro de las pantallas propias del plugin
      (editor de formulario, listado de acciones, conexiones), no en una página
      de ajustes aparte?
- [ ] ¿El mapeo de campos usa un selector con los campos reales del formulario
      (o el sistema de merge tags/shortcodes nativo), en vez de texto libre a
      mano?
- [ ] ¿Los campos de conexión (URL, usuario, password, API key, Odoo DB...) se
      muestran/ocultan según el CRM elegido usando los helpers
      `formscrm_get_dependency_*()`?
- [ ] ¿Reutiliza `formscrm_get_choices()`, `formscrm_get_api_class()` y las
      claves `fc_crm_*` estándar?
- [ ] ¿Un fallo del CRM nunca bloquea el envío del formulario ni se muestra al
      usuario final?
- [ ] ¿Todo error pasa por `formscrm_alert_error()` con `$form_info` completo
      (`form_type`, `form_id`, `form_name`, `entry_id` si existe)?
- [ ] ¿La clase/archivo solo se carga si el plugin está activo, enganchada a su
      hook de arranque propio?
- [ ] ¿Hay tests unitarios para cualquier helper nuevo (p. ej. parseo de mapeo
      de campos)?
- [ ] `composer lint` y `composer phpstan` pasan sin avisos nuevos.

## Referencia: caso de estudio — Ninja Forms (issue #122)

- **Issue**: [#122](https://github.com/closemarketing/formscrm/issues/122) pide
  soporte nativo para Ninja Forms.
- **PR #123** (draft, de Cursor) apuntaba a una rama base obsoleta
  (`122-support-for-ninja-forms-integration`) y quedó cerrado sin fusionar.
- **PR #171** rehizo la integración contra `trunk`, añadiendo
  `FormsCRM_NinjaForms_Action` (extiende correctamente `NF_Abstracts_Action`,
  el patrón nativo de NF) con carga condicional en `loader.php`. Se fusionó y
  se revirtió el mismo día siguiente mediante el **PR #173** (sin comentarios
  registrados que documenten el motivo exacto).
- Al revisar el código fusionado, los puntos que más se alejan de las
  convenciones del resto de integraciones (y candidatos más probables al
  motivo de la reversión) son los descritos en los puntos 3, 4 y 6 de esta
  guía: mapeo de campos por texto libre en vez de selector nativo, todos los
  campos de conexión visibles a la vez sin depender del CRM, y errores
  registrados sin `$form_info`.
- **Ninguno de estos problemas es bloqueante** para retomar la integración:
  la base (`NF_Abstracts_Action`, carga condicional, helper
  `formscrm_parse_field_mapping()`) es reutilizable. Antes de reabrir el PR,
  conviene aplicar el checklist de la sección 8, en particular sustituir el
  `textarea` de mapeo por un `select` por campo con las claves reales del
  formulario y aplicar los helpers `formscrm_get_dependency_*()` para mostrar
  solo los campos de conexión relevantes al CRM elegido.
