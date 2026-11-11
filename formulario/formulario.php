<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/api.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
$options = formOptions();
$areas = $options['areas']['data'] ?? [];
$roles = $options['roles']['data'] ?? [];
$optionsAvailable = isset($options['areas']['data'], $options['roles']['data'])
    && is_array($areas)
    && is_array($roles);

if (!$optionsAvailable) {
    $error = $options['areas']['error'] ?? $options['roles']['error'] ?? 'No se pudieron cargar las opciones.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = readEmployeeForm();
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['csrf_token'], $csrfToken)) {
        $error = 'La sesión del formulario venció. Recarga la página e inténtalo de nuevo.';
    } elseif ($optionsAvailable && ($validationError = isValidEmployeeForm($form, $areas, $roles)) !== '') {
        $error = $validationError;
    } elseif ($error === '') {
        $response = apiRequest('POST', '/api/empleados', employeePayload($form));
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

            <nav class="page-actions" aria-label="Navegación">
                <a class="secondary-button" href="lista-empleados.php">Ver empleados</a>
            </nav>

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
