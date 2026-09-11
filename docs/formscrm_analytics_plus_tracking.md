# FormsCRM ↔ Analytics PLUS — atribución de contactos

**Objetivo:** que los contactos que FormsCRM crea vía API queden vinculados a la
visita que Analytics PLUS trackeó con su pixel en la misma web, para que aparezcan
atribuidos (origen/campaña/sesión) en Analytics PLUS.

---

## 1. Concepto clave

El identificador que une **contacto ↔ visita** en Analytics PLUS **no es el email ni
una cookie**: es el `visitor_uuid` que el pixel guarda en el **localStorage** del
navegador del visitante, bajo esta clave:

```
__<pixel_key>_visitor_uuid
```

- `pixel_key` = clave del pixel de esa cuenta/dominio.
- El valor es un UUID persistente que el pixel genera la primera vez que el visitante
  entra y reutiliza en visitas posteriores.

> ### ⚠️ Es `_visitor_uuid`, NO `_visitor_session_uuid`
>
> El pixel escribe **varias** claves con el mismo prefijo `__<pixel_key>_` y valores de
> aspecto muy parecido (base64 de random+timestamp). Solo una sirve:
>
> | Clave en localStorage                  | Qué es                              | ¿visitor_key2? |
> |----------------------------------------|-------------------------------------|----------------|
> | `__<pk>_visitor_uuid`                  | ID **persistente** del visitante    | ✅ **SÍ**      |
> | `__<pk>_visitor_session_uuid`          | ID de la **sesión** actual (rota)   | ❌ no          |
> | `__<pk>_visitor_session_event_uuid`    | ID del último evento                | ❌ no          |
> | `__<pk>_visitor_session_date`          | Fecha de la sesión                  | ❌ no          |
>
> Evidencia (código de Analytics PLUS):
> - `PixelTrack.php` guarda `visitor_uuid` en `websites_visitors.visitor_uuid`, y
>   `visitor_session_uuid` en otra tabla/columna (`visitors_sessions.session_uuid`).
> - El handler que cierra la atribución, `ApiClientify.php → tracking()`, busca
>   `db()->where('visitor_uuid', $vk)->get('websites_visitors')`. Si se manda el
>   `session_uuid` **no encuentra visitante y la conversión no se marca** (falla en
>   silencio, sin error visible).
> - El embed nativo de webforms de Clientify lee exactamente
>   `localStorage.getItem(\`__${pk}_visitor_uuid\`)`.
>
> El prefijo lo construye el pixel como `'__' + pixel_key + '_' + nombre`
> (`get_dynamic_var`), es decir con **guion bajo antes del nombre**:
> `__UB4AasTAiETndOzX_visitor_uuid`.

La atribución se cierra así (flujo del webform nativo de Clientify, que replicamos):

```
pixel guarda __<pixel_key>_visitor_uuid en localStorage
   → el formulario lee ese valor y lo envía junto al contacto (campo visitor_key2)
   → Clientify crea el vínculo ContactVisitorKey(contact, visitor_key)
   → Analytics PLUS marca esa visita con el contact_id (conversión)  → atribución
```

**Consecuencia para el plugin:** FormsCRM corre en el servidor (PHP), y desde el
servidor **NO se puede leer el localStorage** del navegador. Por tanto el valor debe
capturarse en **cliente (JavaScript)**, meterse en el formulario como campo oculto, y
que el plugin lo reenvíe en la petición a la API.

---

## 2. Cambios en el plugin FormsCRM

### 2.1 Cliente (JavaScript en la página del formulario)

Antes de enviar el formulario, resolver el `pixel_key` del dominio, leer el
`visitor_uuid` del localStorage y volcarlo (junto a los parámetros de campaña) en
campos ocultos del formulario.

```js
// 1) Resolver el pixel_key del dominio llamando a la URL de solicitud de Analytics PLUS:
//
//    https://analyticsplusdev.clientify.net/analytics_plus/apiclientify
//        ?request_type=get_pk_cached&domain=<DOMINIO_DE_LA_WEB>
//
//    (analyticsplusdev... es el entorno DEV; en producción usar el host de AP prod)
//
//    IMPORTANTE sobre `domain`: el backend hace parse_url() y usa el `host`, así que el
//    valor debe ir como URL con esquema (p.ej. https://www.misitio.com), NO el dominio
//    pelado (misitio.com daría "Error: Invalid URL format"). Se normaliza en servidor y
//    se le quita el www. Lo más simple: pasar window.location.origin.
const domain = window.location.origin;  // -> "https://www.misitio.com"
fetch('https://analyticsplusdev.clientify.net/analytics_plus/apiclientify?request_type=get_pk_cached&domain=' + encodeURIComponent(domain))
  .then(r => r.text())
  .then(pk => {
    if (!pk || pk.startsWith('Error')) return;   // respuesta es el pixel_key en texto plano

    // 2) Leer el visitor_uuid que dejó el pixel en localStorage
    const vk2 = window.localStorage.getItem('__' + pk + '_visitor_uuid');
    if (vk2) setHidden('visitor_key2', vk2);
  });

// 3) Parámetros de campaña desde la URL de aterrizaje / referrer (opcional pero recomendado)
const qs = new URLSearchParams(window.location.search);
['utm_source','utm_medium','utm_campaign','utm_content','utm_term'].forEach(k => {
  if (qs.get(k)) setHidden(k, qs.get(k));
});
if (qs.get('gclid'))  setHidden('gclid',  qs.get('gclid'));
if (qs.get('fbclid')) setHidden('fbclid', qs.get('fbclid'));
if (document.referrer) setHidden('referrer', document.referrer);

// helper: crea/actualiza un <input type=hidden> en el form de FormsCRM
function setHidden(name, value) {
  let el = document.querySelector(`[name="${name}"]`);
  if (!el) {
    el = document.createElement('input');
    el.type = 'hidden'; el.name = name;
    document.querySelector('form.formscrm').appendChild(el); // ajustar selector al form real
  }
  el.value = value;
}
```

Notas:
- **`get_pk_cached` es un endpoint PÚBLICO: no requiere token ni ninguna cabecera de
  autorización.** Despacha solo por `request_type` (sin auth previa) y responde con
  CORS `Access-Control-Allow-Origin: *`, por eso se puede llamar directamente desde el
  navegador. Es exactamente como lo consume ya el embed nativo de Clientify. No hay
  ningún secreto que compartir con los desarrolladores del plugin para esta llamada; el
  único token del flujo es el de la API de Clientify para el `POST /v2/contacts/`, que
  FormsCRM ya usa.
- `pixel_key` puede **hardcodearse** por sitio en la config del plugin en vez de
  llamar a `get_pk_cached` (una llamada menos). Recomendado hacerlo configurable.
- Alternativa aún más simple (sin llamada de red): buscar la clave directamente por
  sufijo, ya que el prefijo es siempre `__<pixel_key>_`:
  ```js
  const k = Object.keys(localStorage).find(k => k.endsWith('_visitor_uuid'));
  const vk2 = k ? localStorage.getItem(k) : null;
  ```
  Con esto `get_pk_cached` solo hace falta si hay más de un pixel en la misma web
  (caso raro) o si se quiere validar que el pixel encontrado es el de esa cuenta.
- El pixel puede tardar en escribir el localStorage; si el form se envía muy rápido
  puede no haber `visitor_uuid` todavía. Aceptable: si no hay valor, no se envía y el
  contacto simplemente no queda atribuido.
- Respetar opt-out: si el visitante hizo opt-out del pixel, no habrá `visitor_uuid`.

### 2.2 Servidor (PHP del plugin)

Mapear esos campos del formulario al payload que el plugin ya envía a la API de
creación de contacto. Campos a incluir (todos opcionales, enviar los que haya):

| Campo API            | Origen (campo oculto)      | Para qué sirve                                   |
|----------------------|----------------------------|--------------------------------------------------|
| `visitor_key2`       | `__<pixel_key>_visitor_uuid` | **Clave de atribución en Analytics PLUS**       |
| `utm_source`         | `utm_source`               | Atribución de origen en el CRM                    |
| `utm_medium`         | `utm_medium`               | "                                                 |
| `utm_campaign`       | `utm_campaign`             | "                                                 |
| `utm_content`        | `utm_content`              | "                                                 |
| `utm_term`           | `utm_term`                 | "                                                 |
| `gclid`              | `gclid`                    | Google Ads click id                               |
| `fbclid`             | `fbclid`                   | Facebook click id                                 |
| `referrer`           | `document.referrer`        | Web de procedencia                                |

El resto del payload (nombre, email, teléfono, owner/token…) no cambia.

---

## 3. Ejemplo de payload

```http
POST /v2/contacts/            (misma API/token que ya usa FormsCRM)
Content-Type: application/json
Authorization: Token <API_TOKEN>

{
  "first_name": "Juan",
  "last_name": "Pérez",
  "emails": [{ "email": "juan@example.com", "type": "work" }],

  "visitor_key2": "MTIzYWJjLi4uMjAyNS0wNy0wNg==",   // <-- valor de localStorage
  "utm_source": "google",
  "utm_medium": "cpc",
  "utm_campaign": "verano2025",
  "gclid": "Cj0KCQ...",
  "referrer": "https://www.google.com/"
}
```

---

## 4. Soporte en el backend de Clientify

> **Estado a 2026-07-31: implementado pero NO desplegado.** El soporte vive en la rama
> `CTF-formscrm-visitor-key2` (commit `8815983` — `contacts/serializers.py`, lee
> `visitor_key2` de `request.data` en `create_contact`). Comprobado que **no está en
> `main`, `staging` ni `clientify2.0`**, así que hoy la API pública ignora el campo (no
> falla: el contacto se crea, simplemente sin atribución). **Hay que mergear y desplegar
> antes de validar E2E con el plugin.**

La API pública de contactos (`POST /v1/contacts/` y `POST /v2/contacts/`) **acepta el
campo `visitor_key2`**. Al recibirlo en el body de la creación de contacto, el backend
crea el vínculo `ContactVisitorKey` y dispara el pixel de Analytics PLUS que marca la
visita con el `contact_id` (conversión atribuida) — exactamente el mismo flujo que el
webform nativo.

Notas para el plugin:
- El campo se llama **`visitor_key2`** (no `visitor_key`; `visitor_key` a secas dispara
  el analytics antiguo, no PLUS).
- Funciona igual en **v1 y v2** (comparten el mismo serializer). Enviarlo en el mismo
  POST que ya crea el contacto; no requiere ninguna llamada adicional.
- El procesamiento es **asíncrono e idempotente**: reenviar el mismo `visitor_key2` no
  duplica el vínculo. Si el valor va vacío o no se envía, el contacto se crea igual, sin
  atribución.

> El único requisito por parte del plugin es **incluir `visitor_key2` en el payload**
> (sección 2 y 3), con el valor de `__<pixel_key>_visitor_uuid`.

Limitaciones conocidas del soporte back:
- Solo se procesa en **creación** de contacto (`create_contact`). Un `PATCH/PUT` sobre un
  contacto existente **no** procesa `visitor_key2`. Si FormsCRM reenvía un email ya
  existente, la atribución se aplica solo si esa petición pasa por el flujo de creación
  (dedup por email incluido); si el plugin decide actualizar en vez de crear, no habrá
  atribución.
- Si el `visitor_key2` ya estaba vinculado a **otro** contacto, se mantiene el vínculo
  original (`get_or_create`) y no se dispara el pixel de nuevo.

---

## 4.bis Comprobaciones ya hechas (2026-07-31)

| Comprobación | Resultado |
|---|---|
| `GET ...?request_type=get_pk_cached&domain=https://bivarclinic.pt` | `200` → `UB4AasTAiETndOzX` (texto plano, `text/html`) |
| Mismo con `https://www.bivarclinic.pt` | `200` → mismo pixel (el back quita el `www.`) |
| Cabeceras CORS | `Access-Control-Allow-Origin: *`, `Methods: GET, OPTIONS` → llamable desde JS de navegador, sin token |
| Dominio no registrado | `200` con cuerpo `Error: domain not found` (⚠️ **el error viaja con HTTP 200**: hay que comprobar el cuerpo, no el status) |
| Dominio sin esquema (`misitio.com`) | `Error: Invalid URL format` → mandar siempre `window.location.origin` |
| Caché del endpoint | pixel cacheado 24 h por dominio (`get_pk_<host>`); si se cambia el pixel de un dominio, purgar con `request_type=clear_pk_cache&domain=...` |
| Host de Analytics PLUS | `analyticsplusdev.clientify.net/analytics_plus/apiclientify` es el que usa **también producción** (`settings/production.py`), pese al `dev` del nombre |
| Clave localStorage correcta | `__<pk>_visitor_uuid` (ver aviso de la sección 1) |
| Back acepta `visitor_key2` | Código OK, **pendiente de merge/deploy** (sección 4) |

---

## 5. Cómo verificar

1. Entrar a la web (con el pixel instalado) desde una URL con UTMs, p.ej.
   `?utm_source=test&utm_medium=cpc`.
2. En la consola del navegador: `localStorage.getItem('__<pixel_key>_visitor_uuid')`
   debe devolver un valor.
3. Enviar el formulario de FormsCRM.
4. En el request de creación de contacto, confirmar que viaja `visitor_key2` con ese
   mismo valor.
5. En Clientify: el contacto creado debe tener un `ContactVisitorKey` asociado y, en
   Analytics PLUS, la visita de ese `visitor_uuid` debe quedar marcada con el
   `contact_id` (conversión atribuida).

Trampa habitual al depurar: si el valor enviado fue el `visitor_session_uuid`, los pasos
1-4 se ven perfectos (el POST viaja con `visitor_key2`, el contacto se crea, el
`ContactVisitorKey` se crea) y **solo falla el paso 5**: la fila de `websites_visitors`
nunca recibe el `contact_id` porque no existe ningún visitante con ese uuid.
