# FormsCRM - Tests Unitarios

## 📋 Índice

- [Instalación](#instalación)
- [Ejecutar Tests](#ejecutar-tests)
- [Tests de Notificaciones](#tests-de-notificaciones)
- [Cobertura de Tests](#cobertura-de-tests)

## 🚀 Instalación

### Requisitos Previos

- PHP 7.4 o superior
- Composer
- MySQL/MariaDB

### Instalar Dependencias

```bash
composer install
```

### Configurar WordPress Test Suite

```bash
composer test-install
```

Este comando instalará el entorno de pruebas de WordPress.

## ▶️ Ejecutar Tests

### Todos los Tests

```bash
composer test
```

### Tests Específicos

#### Tests de Notificaciones

```bash
composer test -- --filter NotificationsTest
```

#### Tests de Helpers

```bash
composer test -- --filter HelpersFunctionsTest
```

### Modo Debug

Para ejecutar tests con Xdebug activado:

```bash
composer test-debug -- --filter NotificationsTest
```

## 📧 Tests de Notificaciones

El archivo `tests/Unit/test-notifications.php` contiene tests completos para las funciones de notificación de errores.

### Funciones Testeadas

#### 1. `formscrm_debug_email_lead()`

**Tests incluidos:**

- ✅ Función existe
- ✅ Usa email personalizado cuando está configurado
- ✅ Usa email del administrador por defecto
- ✅ Asunto incluye nombre del sitio
- ✅ Cuerpo contiene nombre del CRM
- ✅ Cuerpo contiene información del formulario
- ✅ Cuerpo contiene información del sitio
- ✅ Cuerpo contiene datos del lead
- ✅ Cuerpo contiene detalles técnicos (URL y JSON)

**Ejemplo de ejecución:**

```bash
composer test -- --filter test_email_uses_custom_email_when_configured
```

#### 2. `formscrm_send_slack_notification()`

**Tests incluidos:**

- ✅ Función existe
- ✅ Retorna false cuando no hay webhook configurado
- ✅ Envía notificación cuando hay webhook configurado
- ✅ Incluye información del sitio
- ✅ Incluye información del formulario
- ✅ Incluye CRM y error
- ✅ Incluye vista previa de datos del lead (primeros 3 campos)
- ✅ Incluye URL de la API
- ✅ Usa color "danger" (rojo) para el mensaje
- ✅ Muestra contador "+N more" cuando hay más de 3 campos

**Ejemplo de ejecución:**

```bash
composer test -- --filter test_slack_sends_when_webhook_configured
```

### Test de Integración

- ✅ Verifica que email y Slack se envían cuando ambos están configurados

```bash
composer test -- --filter test_integration_both_email_and_slack_sent
```

## 📊 Cobertura de Tests

### Email Notifications

| Característica | Testeada |
|---------------|----------|
| Email personalizado | ✅ |
| Email por defecto | ✅ |
| Asunto con site name | ✅ |
| Información del sitio | ✅ |
| Información del formulario | ✅ |
| Datos del lead | ✅ |
| Detalles técnicos | ✅ |
| HTML formateado | ✅ |

### Slack Notifications

| Característica | Testeada |
|---------------|----------|
| Sin webhook configurado | ✅ |
| Con webhook configurado | ✅ |
| Información del sitio | ✅ |
| Información del formulario | ✅ |
| CRM y error | ✅ |
| Vista previa de lead | ✅ |
| URL de API | ✅ |
| Color del mensaje | ✅ |
| Formato compacto | ✅ |

## 🔧 Estructura de Tests

```
tests/
├── Unit/
│   ├── test-helpers-functions.php  # Tests de funciones helper
│   └── test-notifications.php      # Tests de notificaciones (NUEVO)
├── API/
│   └── test-clientify.php          # Tests de API Clientify
├── Forms/
│   └── test-contactform.php        # Tests de Contact Form 7
├── Data/
│   └── *.json                      # Datos de prueba
└── bootstrap.php                    # Bootstrap de tests
```

## 🎯 Datos de Prueba

Los tests utilizan datos ficticios pero realistas:

### Email de Prueba

```php
$data = array(
    array( 'name' => 'Name', 'value' => 'John Doe' ),
    array( 'name' => 'Email', 'value' => 'john@example.com' ),
    array( 'name' => 'Phone', 'value' => '+34 600 123 456' ),
);
```

### Configuración de Formulario

```php
$form_info = array(
    'form_type' => 'Gravity Forms',
    'form_id'   => '42',
    'form_name' => 'Contact Form',
    'entry_id'  => '12345',
);
```

### Webhook de Slack (Mock)

```php
$webhook_url = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';
```

## 🐛 Debugging

### Ver Output de Tests

```bash
composer test -- --filter NotificationsTest --debug
```

### Ver Detalles de Email Enviado

Los tests capturan el email usando `tests_retrieve_phpmailer_instance()`:

```php
$mailer = tests_retrieve_phpmailer_instance();
echo $mailer->get_sent()->body; // Ver contenido del email
```

### Ver Request a Slack

Los tests capturan el request HTTP:

```php
add_filter(
    'pre_http_request',
    function( $pre, $r, $url ) use ( &$http_request ) {
        $http_request = $r;
        // Examinar $http_request['body']
        return array(...);
    },
    20,
    3
);
```

## 📝 Añadir Nuevos Tests

### Template Básico

```php
public function test_nueva_funcionalidad() {
    // Arrange - Preparar datos.
    $crm = 'Holded';
    $error = 'Test error';
    $data = array(
        array( 'name' => 'Email', 'value' => 'test@example.com' ),
    );

    // Act - Ejecutar función.
    formscrm_debug_email_lead( $crm, $error, $data );

    // Assert - Verificar resultado.
    $mailer = tests_retrieve_phpmailer_instance();
    $this->assertStringContainsString( 'expected', $mailer->get_sent()->body );
}
```

## 🔍 Verificar Tests en CI/CD

Los tests se ejecutan automáticamente en GitHub Actions cuando se hace push o pull request.

Ver: `.github/workflows/phpunit.yml`

## 📚 Recursos

- [WordPress Testing Handbook](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WP_UnitTestCase API](https://developer.wordpress.org/reference/classes/wp_unittestcase/)

## ✅ Checklist antes de Commit

- [ ] Todos los tests pasan: `composer test`
- [ ] No hay errores de linting: `composer lint`
- [ ] PHPStan está limpio: `composer phpstan`
- [ ] Tests nuevos documentados
- [ ] README actualizado si es necesario

## 🤝 Contribuir

Para añadir nuevos tests:

1. Crea un nuevo archivo en `tests/Unit/` o añade a uno existente
2. Sigue la convención de nombres: `test_descripcion_clara`
3. Usa el patrón AAA (Arrange, Act, Assert)
4. Limpia el estado después de cada test (`tearDown`)
5. Ejecuta los tests localmente antes de commit

---

**Última actualización:** 2025-01-04
**Versión del plugin:** 4.0.7

