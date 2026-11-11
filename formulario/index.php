<?php
declare(strict_types=1);

session_start();

const API_BASE_URL = 'http://localhost:8080';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function apiRequest(string $method, string $path, ?array $payload = null): array
{
    $baseUrl = rtrim((string) (getenv('SPRING_API_URL') ?: API_BASE_URL), '/');
    $headers = [
        'Accept: application/json',
        'Connection: close',
    ];
    $options = [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'timeout' => 8,
        'ignore_errors' => true,
    ];

    if ($payload !== null) {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['error' => 'No se pudo preparar la información del empleado.'];
        }
        $headers[] = 'Content-Type: application/json';
        $options['header'] = implode("\r\n", $headers);
        $options['content'] = $json;
    }

    $context = stream_context_create(['http' => $options]);
    $body = @file_get_contents($baseUrl . $path, false, $context);
    $responseHeaders = $http_response_header ?? [];

    if ($body === false) {
        $lastError = error_get_last();
        $detail = is_array($lastError) ? ': ' . $lastError['message'] : '';
        return ['error' => 'No se pudo contactar al backend Spring' . $detail];
    }

    $status = 0;
    foreach ($responseHeaders as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
            $status = (int) $matches[1];
        }
    }
    if ($status === 0) {
        return ['error' => 'El backend Spring devolvió una respuesta HTTP inválida.'];
    }

    $data = json_decode($body, true);
    if ($status < 200 || $status >= 300) {
        $message = is_array($data)
            ? ($data['detail'] ?? $data['message'] ?? null)
            : null;
        return ['error' => is_string($message) ? $message : 'El backend rechazó la solicitud (HTTP ' . $status . ').'];
    }

    return ['data' => $data];
}

$form = [
    'nombre' => '',
    'correo' => '',
    'sexo' => '',
    'area_id' => '',
    'description' => '',
    'boletin' => '0',
    'rol_id' => '',
];
$success = '';
$error = '';

$areasResponse = apiRequest('GET', '/api/areas');
$rolesResponse = apiRequest('GET', '/api/roles');
$areas = $areasResponse['data'] ?? [];
$roles = $rolesResponse['data'] ?? [];
$optionsAvailable = isset($areasResponse['data'], $rolesResponse['data'])
    && is_array($areasResponse['data'])
    && is_array($rolesResponse['data']);

if (isset($areasResponse['error']) || isset($rolesResponse['error'])) {
    $error = $areasResponse['error'] ?? $rolesResponse['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $_) {
        if ($key === 'boletin') {
            $form[$key] = isset($_POST[$key]) && $_POST[$key] === '1' ? '1' : '0';
        } else {
            $form[$key] = trim((string) ($_POST[$key] ?? ''));
        }
    }

    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['csrf_token'], $csrfToken)) {
        $error = 'La sesión del formulario venció. Recarga la página e inténtalo de nuevo.';
    } elseif (!preg_match('/^[\p{L} ]{1,255}$/u', $form['nombre'])) {
        $error = 'El nombre es obligatorio y solo puede contener letras y espacios.';
    } elseif (!filter_var($form['correo'], FILTER_VALIDATE_EMAIL) || strlen($form['correo']) > 255) {
        $error = 'Escribe un correo electrónico válido.';
    } elseif (!in_array($form['sexo'], ['m', 'f'], true)) {
        $error = 'Selecciona una opción de sexo.';
    } elseif (!in_array($form['area_id'], array_map(
        static fn (array $area): string => (string) $area['id'],
        is_array($areas) ? $areas : []
    ), true)) {
        $error = 'Selecciona un área disponible.';
    } elseif ($form['description'] === '') {
        $error = 'La descripción es obligatoria.';
    } elseif (!in_array($form['rol_id'], array_map(
        static fn (array $rol): string => (string) $rol['id'],
        is_array($roles) ? $roles : []
    ), true)) {
        $error = 'Selecciona un rol disponible.';
    } elseif ($error === '') {
        $response = apiRequest('POST', '/api/empleados', [
            'nombre' => $form['nombre'],
            'correo' => $form['correo'],
            'sexo' => $form['sexo'],
            'area' => ['id' => (int) $form['area_id']],
            'boletin' => (int) $form['boletin'],
            'description' => $form['description'],
            'roles' => [['id' => (int) $form['rol_id']]],
        ]);

        if (isset($response['error'])) {
            $error = $response['error'];
        } else {
            $createdId = $response['data']['id'] ?? null;
            $success = $createdId === null
                ? 'Empleado creado correctamente.'
                : 'Empleado creado correctamente. ID: ' . (int) $createdId . '.';
            $form = [
                'nombre' => '',
                'correo' => '',
                'sexo' => '',
                'area_id' => '',
                'description' => '',
                'boletin' => '0',
                'rol_id' => '',
            ];
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear empleado</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="page">
        <section class="form-card" aria-labelledby="form-title">
            <header class="form-header">
                <span class="eyebrow">Gestión de personal</span>
                <h1 id="form-title">Crear empleado</h1>
                <p>Completa la información para registrar a un nuevo empleado.</p>
            </header>

            <?php if ($success !== ''): ?>
                <div class="notice notice-success" role="status"><?= escape($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="notice notice-error" role="alert"><?= escape($error) ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= escape((string) $_SESSION['csrf_token']) ?>">

                <div class="field">
                    <label for="nombre">Nombre completo</label>
                    <input
                        id="nombre"
                        name="nombre"
                        type="text"
                        placeholder="Nombre completo del empleado"
                        value="<?= escape($form['nombre']) ?>"
                        maxlength="255"
                        pattern="[\p{L} ]+"
                        title="Usa solo letras y espacios"
                        autocomplete="name"
                        required>
                </div>

                <div class="field">
                    <label for="correo">Correo electrónico</label>
                    <input
                        id="correo"
                        name="correo"
                        type="email"
                        placeholder="Correo electrónico"
                        value="<?= escape($form['correo']) ?>"
                        maxlength="255"
                        autocomplete="email"
                        required>
                </div>

                <fieldset class="field choice-field">
                    <legend>Sexo</legend>
                    <div class="choice-row">
                        <label class="choice-card">
                            <input type="radio" name="sexo" value="m" <?= $form['sexo'] === 'm' ? 'checked' : '' ?> required>
                            <span>Masculino</span>
                        </label>
                        <label class="choice-card">
                            <input type="radio" name="sexo" value="f" <?= $form['sexo'] === 'f' ? 'checked' : '' ?>>
                            <span>Femenino</span>
                        </label>
                    </div>
                </fieldset>

                <div class="field">
                    <label for="area_id">Área</label>
                    <select id="area_id" name="area_id" required>
                        <option value="">Selecciona un área</option>
                        <?php if (is_array($areas)): ?>
                            <?php foreach ($areas as $area): ?>
                                <option
                                    value="<?= (int) ($area['id'] ?? 0) ?>"
                                    <?= $form['area_id'] === (string) ($area['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= escape((string) ($area['nombre'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="description">Descripción</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Escribe una descripción del empleado"
                        required><?= escape($form['description']) ?></textarea>
                </div>

                <label class="newsletter-option">
                    <input type="checkbox" name="boletin" value="1" <?= $form['boletin'] === '1' ? 'checked' : '' ?>>
                    <span>Deseo recibir boletín</span>
                </label>

                <fieldset class="field choice-field">
                    <legend>Roles <span class="legend-hint">Selecciona uno</span></legend>
                    <div class="role-list">
                        <?php if (is_array($roles)): ?>
                            <?php foreach ($roles as $rol): ?>
                                <label class="choice-card role-card">
                                    <input
                                        type="radio"
                                        name="rol_id"
                                        value="<?= (int) ($rol['id'] ?? 0) ?>"
                                        <?= $form['rol_id'] === (string) ($rol['id'] ?? '') ? 'checked' : '' ?>
                                        required>
                                    <span><?= escape((string) ($rol['nombre'] ?? '')) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <button class="submit-button" type="submit" <?= !$optionsAvailable ? 'disabled' : '' ?>>
                    Crear empleado
                </button>
            </form>
        </section>
    </main>
</body>
</html>
